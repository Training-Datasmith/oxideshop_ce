<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Core\Database\Adapter\Doctrine;

use Doctrine\DBAL\Connection_Exception;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Transaction_Isolation_Level;
use InvalidArgumentException;
use Ox_Exception;
use Oxid_Esales\Eshop\Core\Database\Adapter\Database_Interface;
use Oxid_Esales\Eshop\Core\Exception\Database_Connection_Exception;
use Oxid_Esales\Eshop\Core\Exception\Database_Error_Exception;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Connection_Factory_Interface;
use PDOException;
use stdClass;
/**
 * The doctrine implementation of our database.
 *
 * @deprecated since v6.5.0 (2019-09-24);
 *             Use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
 */
class Database implements Database_Interface
{
    private const MYSQL_DUPLICATE_KEY_ERROR_CODE = 1062;
    protected $connection_parameters = [];
    protected $connection;
    /**
     * @var array Map strings used in the shop to Doctrine constants
     */
    protected $transaction_isolation_level_map = ['READ UNCOMMITTED' => Transaction_Isolation_Level::READ_UNCOMMITTED, 'READ COMMITTED' => Transaction_Isolation_Level::READ_COMMITTED, 'REPEATABLE READ' => Transaction_Isolation_Level::REPEATABLE_READ, 'SERIALIZABLE' => Transaction_Isolation_Level::SERIALIZABLE];
    public function set_connection_parameters(array $connection_parameters): void
    {
        if (array_key_exists('default', $connection_parameters)) {
            $this->connection_parameters = $connection_parameters['default'];
        }
    }
    public function connect(): void
    {
        try {
            $connection = Container_Facade::get(Connection_Factory_Interface::class)->create();
            $this->set_connection($connection);
            $this->ensure_connection_is_established($connection);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    public function force_master_connection(): void
    {
        if (is_null($this->connection)) {
            $this->connect();
        }
    }
    public function force_slave_connection(): void
    {
        if (is_null($this->connection)) {
            $this->connect();
        }
    }
    public function close_connection(): void
    {
        $this->connection->close();
        gc_collect_cycles();
    }
    protected function set_connection($connection)
    {
        $this->connection = $connection;
    }
    public function get_one($query, $parameters = [])
    {
        if ($this->does_statement_produce_output($query)) {
            try {
                return $this->get_connection()->fetch_one($query, $parameters);
            } catch (Dbal_Exception|PDOException $exception) {
                $exception = $this->convert_exception($exception);
                $this->handle_exception($exception);
            }
        } else {
            Registry::get_logger()->warning('Given statement does not produce output and was not executed', [debug_backtrace()]);
        }
        return false;
    }
    public function get_row($query, $parameters = [])
    {
        try {
            $result_set = $this->select($query, $parameters);
            $result = $result_set->fields;
        } catch (Database_Error_Exception $exception) {
            $this->log_exception($exception);
            $result = [];
        } catch (PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->log_exception($exception);
            $result = [];
        }
        if ($result === false) {
            return [];
        }
        return $result;
    }
    public function quote_identifier($string)
    {
        $string = trim(str_replace('`', '', $string));
        try {
            $result = $this->get_connection()->quote_identifier($string);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        return $result;
    }
    public function quote($value)
    {
        if (!is_scalar($value) && $value !== null) {
            return false;
        }
        try {
            return $this->get_connection()->quote((string) $value);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    public function quote_array($array): array
    {
        return array_map($this->quote(...), $array);
    }
    public function start_transaction(): void
    {
        try {
            $this->get_connection()->begin_transaction();
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    public function commit_transaction(): void
    {
        try {
            $this->get_connection()->commit();
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    public function rollback_transaction(): void
    {
        try {
            $this->get_connection()->roll_back();
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    public function set_transaction_isolation_level($level)
    {
        $level = strtoupper((string) $level);
        if (!array_key_exists($level, $this->transaction_isolation_level_map)) {
            throw new InvalidArgumentException('Transaction isolation level is invalid');
        }
        return $this->get_connection()->set_transaction_isolation($this->transaction_isolation_level_map[$level]);
    }
    public function execute($query, $parameters = [])
    {
        return $this->execute_update($query, $parameters);
    }
    public function select($query, $parameters = []): \Oxid_Esales\Eshop_Community\Core\Database\Adapter\Doctrine\Result_Set
    {
        $this->check_if_sql_is_read_only($query);
        try {
            $parameters = $this->ensure_parameters_with_integer_keys_start_with_one($parameters);
            $statement = $this->get_connection()->prepare($this->check_for_multiple_queries($query, $parameters));
            foreach ($parameters as $key => $value) {
                $statement->bind_value($key, $value);
            }
            return new Result_Set($statement);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    /**
     * @throws InvalidArgumentException
     */
    private function check_if_sql_is_read_only($query): void
    {
        $check = ltrim((string) $query, " \t\n\r\x00\v(");
        if (!(stripos($check, 'select') === 0 || stripos($check, 'show') === 0)) {
            throw new InvalidArgumentException('Function is only for read operations select or show');
        }
    }
    private function check_for_multiple_queries($query, array $parameters): string
    {
        if ($parameters !== [] || strrpos((string) $query, ';', -1) === false) {
            return $query;
        }
        $queries = preg_split('~(\"[^\\\\"]*\"|' . "\\'[^\\\\']*\\'|\\'.+\\'|`[^\\`]*`)(*SKIP)(*F)|(?<=;)(?![ ]*\$)~", (string) $query);
        if (count($queries) > 1) {
            Registry::get_logger()->error('More than one query within one statement', [$query]);
        }
        return $queries[0];
    }
    public function select_limit(string $query, $row_count = -1, $offset = 0, $parameters = [])
    {
        /**
         * Parameter validation.
         * At the moment there will be no InvalidArgumentException thrown on non numeric values as this may break
         * too many things.
         */
        if (!is_numeric($row_count) || !is_numeric($offset)) {
            trigger_error('Parameters rowCount and offset have to be numeric in DatabaseInterface::selectLimit(). ' . 'Please fix your code as this error may trigger an exception in future versions of OXID eShop.', E_USER_DEPRECATED);
        }
        if (0 > $offset) {
            throw new InvalidArgumentException('Argument $offset must not be smaller than zero.');
        }
        /**
         * Cast the parameters limit and offset to integer in in order to avoid SQL injection.
         */
        $row_count = (int) $row_count;
        $offset = (int) $offset;
        $limit_clause = '';
        if ($row_count >= 0 && $offset >= 0) {
            $limit_clause = "LIMIT {$row_count} OFFSET {$offset}";
        }
        return $this->select($query . " {$limit_clause} ", $parameters);
    }
    public function get_col($query, $parameters = [])
    {
        $this->check_if_sql_is_read_only($query);
        $result = [];
        try {
            $result = $this->get_connection()->fetch_first_column($query, $parameters);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        return $result;
    }
    public function execute_update($query, $parameters = [], $types = [])
    {
        try {
            return $this->get_connection()->execute_statement($query, $parameters, $types);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
    }
    protected function get_connection()
    {
        return $this->connection;
    }
    /**
     * @deprecated
     * @internal
     */
    public function get_public_connection()
    {
        return $this->connection;
    }
    private function does_statement_produce_output($query): bool
    {
        return in_array($this->get_first_command_in_statement($query), ['SELECT', 'EXECUTE', 'GET', 'SHOW', 'CHECKSUM', 'DESCRIBE', 'EXPLAIN', 'HELP']);
    }
    protected function convert_exception(\Exception $exception)
    {
        $message = $exception->get_message();
        $code = $exception->get_code();
        $exception_class = Database_Error_Exception::class;
        switch (true) {
            case $exception instanceof Dbal_Exception\Connection_Exception:
            // ConnectionException will be mapped to DatabaseConnectionException::class
            case $exception instanceof Connection_Exception:
            /**
             * Doctrine does not recognise "SQLSTATE[HY000] [2003] Can't connect to MySQL server on 'mysql.example'"
             * as a connection error, as the error code 2003 is simply not treated in
             * Doctrine\DBAL\Driver\AbstractMySQLDriver::convertException.
             * We fix this here.
             */
            // ConnectionException will be mapped to DatabaseConnectionException::class
            // no break
            case is_a($exception->get_previous(), '\Exception') && in_array($exception->get_previous()->get_code(), ['2003']):
                $exception_class = Database_Connection_Exception::class;
                break;
            case $exception instanceof Dbal_Exception:
                /**
                 * Doctrine passes the message and the code of the PDO Exception, which would break backward
                 * compatibility as it uses SQLSTATE error code (string),
                 * but the shop used to the (My)SQL errors (int)
                 * See http://php.net/manual/de/class.pdoexception.php For details and discussion.
                 * Fortunately we can access PDOException and recover the original SQL error code and message.
                 */
                /** @var $pdoException PDOException */
                $pdo_exception = $exception->get_previous();
                if ($pdo_exception instanceof PDOException) {
                    $code = $this->convert_error_code($pdo_exception->error_info[1]);
                    $message = $pdo_exception->error_info[2];
                }
                break;
            case $exception instanceof PDOException:
                /**
                 * The shop uses the (My)SQL errors (int) in the error code,
                 * but $pdoException uses SQLSTATE error code (string)
                 * See http://php.net/manual/de/class.pdoexception.php For details and discussion.
                 * Fortunately in some cases we can access PDOException and recover the original SQL error.
                 */
                $code = $this->convert_error_code($exception->error_info[1]);
                $message = $exception->error_info[2];
                /** In case the original code (int) cannot be recovered, code is set to 0 */
                if (!is_integer($code)) {
                    $code = 0;
                }
                break;
        }
        /** @var oxException $convertedException */
        $converted_exception = new $exception_class($message, $code, $exception);
        return $converted_exception;
    }
    protected function handle_exception(Standard_Exception $exception): never
    {
        throw $exception;
    }
    protected function log_exception(\Exception $exception)
    {
        /** The exception has to be converted into an instance of oxException in order to be logged like this */
        $exception = $this->convert_exception($exception);
        Registry::get_logger()->error($exception->get_message(), [$exception]);
    }
    public function get_all($query, $parameters = [])
    {
        try {
            $result = $this->get_connection()->fetch_all_associative($query, $parameters);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        if ($this->does_statement_produce_output($query)) {
            return $result;
        }
        Registry::get_logger()->warning('Given statement does not produce an output', [debug_backtrace()]);
        return [];
    }
    public function get_last_insert_id()
    {
        try {
            $last_insert_id = $this->get_connection()->last_insert_id();
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        return $last_insert_id;
    }
    /**
     * @return \stdClass[]
     */
    public function meta_columns($table): array
    {
        $database_name = $this->get_connection()->get_database();
        $query = "SELECT\n              COLUMN_NAME AS `Field`,\n              COLUMN_TYPE AS `Type`,\n              IS_NULLABLE AS `Null`,\n              COLUMN_KEY AS `Key`,\n              COLUMN_DEFAULT AS `Default`,\n              EXTRA AS `Extra`,\n              COLUMN_COMMENT AS `Comment`,\n              CHARACTER_SET_NAME AS `CharacterSet`,\n              COLLATION_NAME AS `Collation`\n            FROM information_schema.COLUMNS\n            WHERE\n              TABLE_SCHEMA = '{$database_name}'\n              AND\n              TABLE_NAME = '{$table}'\n            ORDER BY ORDINAL_POSITION ASC";
        try {
            $columns = $this->get_connection()->fetch_all_associative($query);
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        $result = [];
        foreach ($columns as $column) {
            $type = $this->get_meta_column_value_by_key($column, 'Type');
            $field = $this->get_meta_column_value_by_key($column, 'Field');
            $null = $this->get_meta_column_value_by_key($column, 'Null');
            $key = $this->get_meta_column_value_by_key($column, 'Key');
            $default = $this->get_meta_column_value_by_key($column, 'Default');
            $extra = $this->get_meta_column_value_by_key($column, 'Extra');
            $comment = $this->get_meta_column_value_by_key($column, 'Comment');
            $character_set = $this->get_meta_column_value_by_key($column, 'CharacterSet');
            $collation = $this->get_meta_column_value_by_key($column, 'Collation');
            if ($default !== null) {
                // MariaDB puts quotes around default values:
                $default = trim($default, "'");
            }
            $type_information = explode('(', (string) $type);
            $type_name = trim($type_information[0]);
            $item = new stdClass();
            $item->name = $field;
            $item->type = $type_name;
            $item->not_null = 'no' === strtolower((string) $null);
            $item->primary_key = strtolower((string) $key) == 'pri';
            $item->auto_increment = strtolower((string) $extra) == 'auto_increment';
            $item->binary = str_contains(strtolower((string) $type), 'blob');
            $item->unsigned = str_contains(strtolower((string) $type), 'unsigned');
            $item->has_default = is_null($default) || $default === '' ? false : true;
            if ($item->has_default) {
                $item->default_value = $default;
            }
            /**
             * These variables were set only when there was a value in the previous implementation with ADOdb Lite.
             * We do it the same way here for compatibility.
             */
            [$max_length, $scale] = $this->get_column_max_length_and_scale($column, $item->type);
            if (-1 !== $max_length) {
                $item->max_length = (string) $max_length;
            } else {
                $item->max_length = $max_length;
            }
            if (-1 !== $scale) {
                $item->scale = (string) $scale;
            } else {
                $item->scale = null;
            }
            /** Unset has_default and default_value for binary types */
            if ($item->binary) {
                unset($item->has_default, $item->default_value);
            }
            /** Additional properties not found in ADODB lite */
            $item->comment = $comment;
            $item->character_set = $character_set;
            $item->collation = $collation;
            /**
             * ADODB lite properties not implemented
             *
             * @todo: implement the enums property for SET and ENUM fields
             */
            // $item->enums
            if (array_key_exists('Field', $column)) {
                $result[$item->name] = $item;
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }
    public function is_rollback_only()
    {
        try {
            $is_rollback_only = $this->connection->is_rollback_only();
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        return $is_rollback_only;
    }
    public function is_transaction_active()
    {
        try {
            $is_transaction_active = $this->connection->is_transaction_active();
        } catch (Dbal_Exception|PDOException $exception) {
            $exception = $this->convert_exception($exception);
            $this->handle_exception($exception);
        }
        return $is_transaction_active;
    }
    protected function get_meta_column_value_by_key(array $column, $key)
    {
        if (array_key_exists('Field', $column)) {
            $key_map = ['Field' => 'Field', 'Type' => 'Type', 'Null' => 'Null', 'Key' => 'Key', 'Default' => 'Default', 'Extra' => 'Extra', 'Comment' => 'Comment', 'CharacterSet' => 'CharacterSet', 'Collation' => 'Collation'];
        } else {
            $key_map = ['Field' => 0, 'Type' => 1, 'Null' => 2, 'Key' => 3, 'Default' => 4, 'Extra' => 5, 'Comment' => 6, 'CharacterSet' => 7, 'Collation' => 8];
        }
        return $column[$key_map[$key]];
    }
    protected function get_column_max_length_and_scale(array $column, $assigned_type): array
    {
        /** @var int $maxLength The max length of a field. For floating point type or fixed point type fields the precision of the field */
        $max_length = -1;
        /** @var int $scale The scale of floating point type or fixed point type fields */
        $scale = -1;
        /** @var string $mySqlType E.g. "CHAR(4)" or "DECIMAL(5,2)" or "tinyint(1) unsigned" */
        $my_sql_type = $this->get_meta_column_value_by_key($column, 'Type');
        /** Get the maximum display width for the type */
        /** Match Precision an scale E.g DECIMAL(5,2) */
        if (preg_match("/^(.+)\\((\\d+),(\\d+)/", $my_sql_type, $matches)) {
            if (is_numeric($matches[2])) {
                $max_length = $matches[2];
            }
            if (is_numeric($matches[3])) {
                $scale = $matches[3];
            }
            /** Match max length E.g CHAR(4) */
        } elseif (preg_match("/^(.+)\\((\\d+)/", $my_sql_type, $matches)) {
            if (is_numeric($matches[2])) {
                $max_length = $matches[2];
            }
            /**
             * Match List type E.g. SET('A', 'B', 'CDE)
             * In this case the length will be the string length of the longest element
             */
        } elseif (preg_match("/^(enum|set)\\((.*)\\)\$/i", strtolower($my_sql_type), $matches)) {
            if ($matches[2]) {
                $pieces = explode(',', $matches[2]);
                /** The array values contain 2 quotes, so we have to subtract 2 from the strlen */
                $max_length = max(array_map(strlen(...), $pieces)) - 2;
                if ($max_length <= 0) {
                    $max_length = 1;
                }
            }
        }
        /** Numeric types, which may have a maximum length */
        $integer_types = ['INTEGER', 'INT', 'SMALLINT', 'TINYINT', 'MEDIUMINT', 'BIGINT'];
        $fixed_point_types = ['DECIMAL', 'NUMERIC'];
        $floating_point_types = ['FLOAT', 'DOUBLE'];
        /** Text types, which may have a maximum length */
        $text_types = ['CHAR', 'VARCHAR'];
        /** Date types, which may have a maximum length */
        $date_types = ['YEAR'];
        $assigned_type = strtoupper((string) $assigned_type);
        if ((in_array($assigned_type, $integer_types) || in_array($assigned_type, $fixed_point_types) || in_array($assigned_type, $floating_point_types) || in_array($assigned_type, $text_types) || in_array($assigned_type, $date_types)) && -1 == $max_length) {
            /**
             * @todo: If the assigned type is one of the following and maxLength is -1, then,
             * if applicable the default max length ot that type should be assigned.
             */
        }
        return [(int) $max_length, (int) $scale];
    }
    protected function get_first_command_in_statement($query): string
    {
        $single_line_query = str_replace(["\r", "\n"], ' ', $query);
        $sql_comments = '@(([\'"]).*?[^\\\\]\2)|((?:\#|--).*?$|/\*(?:[^/*]|/(?!\*)|\*(?!/)|(?R))*\*\/)\s*|(?<=;)\s+@ms';
        $uncommented_query = preg_replace($sql_comments, '$1', $single_line_query);
        return strtoupper(trim(explode(' ', trim($uncommented_query))[0]));
    }
    protected function ensure_connection_is_established($connection)
    {
        if (!$this->is_connection_established($connection)) {
            $message = $this->create_connection_error_message($connection);
            throw new Connection_Exception($message);
        }
    }
    protected function is_connection_established($connection): bool
    {
        try {
            $connection->get_server_version();
        } catch (Dbal_Exception) {
            return false;
        }
        return true;
    }
    protected function create_connection_error_message($connection): string
    {
        $params = $connection->get_params();
        return sprintf('Could not connect to the database. Please check your database status and configuration. ' . "driver: '%s', host: '%s'", $params['driver'] ?? '', $params['host'] ?? '');
    }
    private function convert_error_code($code)
    {
        return $code === self::MYSQL_DUPLICATE_KEY_ERROR_CODE ? self::DUPLICATE_KEY_ERROR_CODE : $code;
    }
    /**
     * Doctrine's DBAL requires that arrays with integer keys for positional
     * parameters must start from index 1. This method checks if the provided
     * parameter array keys are integers and if the lowest index is 0. If so,
     * it shifts all keys to begin from 1. Associative arrays are left untouched.
     */
    private function ensure_parameters_with_integer_keys_start_with_one(array $parameters): array
    {
        if (array_key_exists(0, $parameters)) {
            array_unshift($parameters, '');
            unset($parameters[0]);
        }
        return $parameters;
    }
}