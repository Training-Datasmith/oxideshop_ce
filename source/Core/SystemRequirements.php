<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Database_Provider as DatabaseConnectionProvider;
use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\System_Requirements\System_Security_Checker;
/**
 * System requirements class.
 */
class System_Requirements
{
    public const MODULE_STATUS_UNABLE_TO_DETECT = -1;
    public const MODULE_STATUS_BLOCKS_SETUP = 0;
    public const MODULE_STATUS_FITS_MINIMUM_REQUIREMENTS = 1;
    public const MODULE_STATUS_OK = 2;
    public const MODULE_GROUP_ID_SERVER_CONFIG = 'server_config';
    public const MODULE_ID_MOD_REWRITE = 'mod_rewrite';
    public const MODULE_ID_MYSQL_VERSION = 'mysql_version';
    /**
     * System required modules
     *
     * @var array
     */
    protected $_a_required_modules;
    /**
     * System requirements status
     *
     * @var bool
     */
    protected $_bl_sys_req_status;
    /**
     * Columns that should not be check for collation
     *
     * @var array
     */
    protected $_a_exception = ['OXDELIVERY' => 'OXDELTYPE', 'OXSELECTLIST' => 'OXIDENT'];
    /**
     * Columns to check for collation
     *
     * @var array
     */
    protected $_a_columns = ['OXID', 'OXOBJECTID', 'OXARTICLENID', 'OXACTIONID', 'OXARTID', 'OXUSERID', 'OXADDRESSUSERID', 'OXCOUNTRYID', 'OXSESSID', 'OXITMID', 'OXPARENTID', 'OXAMITEMID', 'OXAMTASKID', 'OXVENDORID', 'OXMANUFACTURERID', 'OXROOTID', 'OXATTRID', 'OXCATID', 'OXDELID', 'OXDELSETID', 'OXITMARTID', 'OXFIELDID', 'OXROLEID', 'OXCNID', 'OXANID', 'OXARTICLENID', 'OXCATNID', 'OXDELIVERYID', 'OXDISCOUNTID', 'OXGROUPSID', 'OXLISTID', 'OXPAYMENTID', 'OXDELTYPE', 'OXROLEID', 'OXSELNID', 'OXBILLCOUNTRYID', 'OXDELCOUNTRYID', 'OXPAYMENTID', 'OXCARDID', 'OXPAYID', 'OXIDENT', 'OXDEFCAT', 'OXBASKETID', 'OXPAYMENTSID', 'OXORDERID', 'OXVOUCHERSERIEID'];
    /**
     * Installation requirements info url
     *
     * @var string
     */
    protected $_s_req_info_url = 'https://docs.oxid-esales.com/eshop/en/latest/installation/new-installation/server-and-system-requirements.html';
    /**
     * Installation preparation info url
     *
     * @var string
     */
    protected $_s_preparation_info_url = 'https://docs.oxid-esales.com/eshop/en/latest/installation/new-installation/preparing-for-installation.html';
    /**
     * Module or system configuration mapping with installation requirements info url anchor
     *
     * @var array
     */
    protected $_a_info_map = ['php_version' => 'php', 'mod_rewrite' => 'web-server', 'mysql_version' => 'database', 'allow_url_fopen' => 'php', 'request_uri' => 'php', 'ini_set' => 'php', 'memory_limit' => 'php', 'file_uploads' => 'php', 'session_autostart' => 'php', 'php_xml' => 'php', 'j_son' => 'php', 'i_conv' => 'php', 'tokenizer' => 'php', 'mysql_connect' => 'php', 'gd_info' => 'php', 'mb_string' => 'php', 'curl' => 'php', 'bc_math' => 'php', 'open_ssl' => 'openssl', 'soap' => 'php'];
    /**
     * Module or system configuration mapping with installation preparations info url anchor
     *
     * @var array
     */
    protected $_a_preparation_info_map = ['server_permissions' => 'schritt-customising-file-and-directory-permissions'];
    /**
     * Class constructor. The constructor is defined in order to be possible to call parent::__construct() in modules.
     */
    public function __construct()
    {
    }
    /**
     * Only used for convenience in UNIT tests by doing so we avoid
     * writing extended classes for testing protected or private methods
     *
     * @param string $method Methods name
     * @param array  $arguments Argument array
     * @return false|mixed
     * @throws SystemComponentException
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([&$this, $method], $arguments);
        }
        throw new System_Component_Exception("Function '{$method}' does not exist or is not accessible! (" . static::class . ')' . PHP_EOL);
    }
    /**
     * Possibility to mock isAdmin() function as we do not extend oxsuperconfig.
     */
    public function is_admin(): bool
    {
        return is_admin();
    }
    /**
     * Sets system required modules
     *
     * @return array
     */
    public function get_required_modules()
    {
        if ($this->_a_required_modules == null) {
            $a_required_php_extensions = ['php_xml', 'j_son', 'i_conv', 'tokenizer', 'mysql_connect', 'gd_info', 'mb_string', 'curl', 'bc_math', 'open_ssl', 'soap'];
            $a_required_php_configs = ['allow_url_fopen', 'request_uri', 'ini_set', 'memory_limit', 'unicode_support', 'file_uploads', 'session_autostart'];
            $a_required_server_configs = ['mod_rewrite', 'server_permissions', 'cryptographically_sufficient_configuration'];
            $this->_a_required_modules = array_fill_keys($a_required_server_configs, 'server_config') + array_fill_keys($a_required_php_configs, 'php_config') + array_fill_keys($a_required_php_extensions, 'php_extennsions');
        }
        return $this->_a_required_modules;
    }
    /**
     * Checks if curl extension is loaded
     */
    public function check_curl(): int
    {
        return extension_loaded('curl') ? 2 : 1;
    }
    /**
     * Checks if mbstring extension is loaded
     */
    public function check_mb_string(): int
    {
        return extension_loaded('mbstring') ? 2 : 1;
    }
    /**
     * Checks if permissions on servers are correctly setup
     *
     * @param string $path    check path [optional]
     * @param int    $minPerm min permission level, default 777 [optional]
     */
    public function check_server_permissions($path = null, $min_perm = 777): int
    {
        clearstatcache();
        $path = $path ?: get_shop_base_path();
        $mod_stat = 2;
        $permission_issues = $this->get_permission_issues_list($path, $min_perm);
        if (count($permission_issues['missing']) + count($permission_issues['not_writable'])) {
            return 0;
        }
        return $mod_stat;
    }
    /**
     * @see cryptographically_sufficient_configuration
     */
    public function check_cryptographically_sufficient_configuration(): int
    {
        return (new System_Security_Checker())->is_cryptographically_secure() ? self::MODULE_STATUS_OK : self::MODULE_STATUS_BLOCKS_SETUP;
    }
    /**
     * Get list of permission issues
     *
     * @param string $shopPath
     * @param int $minPerm
     */
    public function get_permission_issues_list($shop_path = null, $min_perm = 777): array
    {
        clearstatcache();
        $shop_path = $shop_path ?: get_shop_base_path();
        $path_check_results = ['missing' => [], 'not_writable' => []];
        $build_directory = Container_Facade::get_parameter('oxid_esales.build_directory');
        $paths_to_check = [$build_directory];
        $one_path_to_check = reset($paths_to_check);
        while ($one_path_to_check) {
            // missing file/folder?
            if (!file_exists($one_path_to_check)) {
                $path_check_results['missing'][] = str_replace($shop_path, '', $one_path_to_check);
            }
            if (is_dir($one_path_to_check)) {
                // adding subfolders
                $sub_directories = glob($one_path_to_check . '*', GLOB_ONLYDIR);
                if (is_array($sub_directories)) {
                    foreach ($sub_directories as $one_sub_directory) {
                        $paths_to_check[] = $one_sub_directory . '/';
                    }
                }
            }
            // testing if file permissions >= $iMinPerm
            if (!is_readable($one_path_to_check) || !is_writable($one_path_to_check)) {
                $path_check_results['not_writable'][] = str_replace($shop_path, '', $one_path_to_check);
            }
            $one_path_to_check = next($paths_to_check);
        }
        return $path_check_results;
    }
    /**
     * returns host, port, base dir, ssl information as associative array, false on error
     *
     * @return array|false
     */
    protected function get_shop_ssl_host_info_from_config(): false|array
    {
        $ssl_shop_url = Container_Facade::get_parameter('oxid_esales.shop_url');
        if (!preg_match('#^(https?://)?([^/:]+)(:(\d+))?(/.*)?$#i', (string) $ssl_shop_url, $shop_url_components)) {
            return false;
        }
        $host = $shop_url_components[2];
        $port = (int) $shop_url_components[4];
        $ssl = strtolower($shop_url_components[1]) === 'https://';
        if (!$port) {
            $port = $ssl ? 443 : 80;
        }
        $script = rtrim($shop_url_components[5], '/') . '/';
        return ['host' => $host, 'port' => $port, 'dir' => $script, 'ssl' => $ssl];
    }
    /**
     * returns host, port, current script, ssl information as assotiative array, false on error
     * Takes ssl address from config so important only in admin.
     *
     * @return array
     */
    protected function get_shop_ssl_host_info(): array|false
    {
        if ($this->is_admin()) {
            return $this->get_shop_ssl_host_info_from_config();
        }
        return false;
    }
    /**
     * Checks if mod_rewrite extension is loaded.
     * Checks for all address.
     */
    public function check_mod_rewrite(): int
    {
        $ssl_host_info = $this->get_shop_ssl_host_info();
        $mod_stat = $this->is_mode_rewrite_extension_loaded($ssl_host_info);
        if (0 != $mod_stat && $ssl_host_info) {
            $ssl_mod_stat = $this->is_mode_rewrite_extension_loaded($ssl_host_info);
            // Send if failed, even if you couldn't check another
            if (0 == $ssl_mod_stat) {
                return 0;
            }
            // Send if failed, even if you couldn't check another
            if (1 == $ssl_mod_stat || 1 == $mod_stat) {
                return 1;
            }
            return min($mod_stat, $ssl_mod_stat);
        }
        return $mod_stat;
    }
    /**
     * Checks if mod_rewrite extension is loaded.
     * Checks for one address.
     *
     * @param array $aHostInfo host info to open socket
     */
    protected function is_mode_rewrite_extension_loaded(array $a_host_info): int
    {
        $s_hostname = ($a_host_info['ssl'] ? 'ssl://' : '') . $a_host_info['host'];
        if ($r_fp = @fsockopen($s_hostname, $a_host_info['port'], $i_err_no, $s_err_str, 10)) {
            $s_req = "POST {$a_host_info['dir']}oxseo.php?mod_rewrite_module_is=off HTTP/1.1\r\n";
            $s_req .= "Host: {$a_host_info['host']}\r\n";
            $s_req .= "User-Agent: OXID eShop setup\r\n";
            $s_req .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $s_req .= "Content-Length: 0\r\n";
            // empty post
            $s_req .= "Connection: close\r\n\r\n";
            $s_out = '';
            fwrite($r_fp, $s_req);
            while (!feof($r_fp)) {
                $s_out .= fgets($r_fp, 100);
            }
            fclose($r_fp);
            $i_mod_stat = str_contains($s_out, 'mod_rewrite_on') ? 2 : 0;
        } else if (function_exists('apache_get_modules')) {
            // it does not assure that mod_rewrite is enabled on current host, so setting 1
            $i_mod_stat = in_array('mod_rewrite', apache_get_modules()) ? 1 : 0;
        } else {
            $i_mod_stat = -1;
        }
        return $i_mod_stat;
    }
    /**
     * Checks if activated allow_url_fopen and fsockopen on port 80 possible
     */
    public function check_allow_url_fopen(): int
    {
        $result_allow_url_fopen = @ini_get('allow_url_fopen');
        $result_allow_url_fopen = strcasecmp('1', $result_allow_url_fopen);
        if (0 === $result_allow_url_fopen && 2 === $this->check_fsockopen()) {
            return 2;
        }
        return 1;
    }
    /**
     * Check if fsockopen on port 80 possible
     */
    public function check_fsockopen(): int
    {
        $result = 1;
        $i_err_no = 0;
        $s_err_str = '';
        if ($o_res = @fsockopen('olc.oxid-esales.com', 80, $i_err_no, $s_err_str, 10)) {
            $result = 2;
            fclose($o_res);
        }
        return $result;
    }
    /**
     * Gets PHP version.
     */
    public function get_php_version(): string
    {
        return PHP_VERSION;
    }
    /**
     * Checks if apache server variables REQUEST_URI or SCRIPT_URI are set
     */
    public function check_request_uri(): int
    {
        return isset($_SERVER['REQUEST_URI']) || isset($_SERVER['SCRIPT_URI']) ? 2 : 0;
    }
    /**
     * Check if DOM extension is loaded
     */
    public function check_php_xml(): int
    {
        return extension_loaded('dom') ? 2 : 0;
    }
    /**
     * Checks if JSON extension is loaded
     */
    public function check_j_son(): int
    {
        return extension_loaded('json') ? 2 : 0;
    }
    /**
     * Checks if iconv extension is loaded
     */
    public function check_i_conv(): int
    {
        return extension_loaded('iconv') ? 2 : 0;
    }
    /**
     * Checks if tokenizer extension is loaded
     */
    public function check_tokenizer(): int
    {
        return extension_loaded('tokenizer') ? 2 : 0;
    }
    /**
     * Checks if bcmath extension is loaded
     */
    public function check_bc_math(): int
    {
        return extension_loaded('bcmath') ? 2 : 1;
    }
    /**
     * Checks if openssl extension is loaded
     */
    public function check_open_ssl(): int
    {
        return extension_loaded('openssl') ? 2 : 1;
    }
    /**
     * Checks if SOAP extension is loaded
     */
    public function check_soap(): int
    {
        return extension_loaded('soap') ? 2 : 1;
    }
    /**
     * Checks if mysql5 extension is loaded.
     */
    public function check_mysql_connect(): int
    {
        return extension_loaded('pdo_mysql') ? 2 : 0;
    }
    /**
     * Checks if GDlib extension is loaded
     */
    public function check_gd_info(): int
    {
        $i_mod_stat = extension_loaded('gd') ? 1 : 0;
        $i_mod_stat = function_exists('imagecreatetruecolor') ? 2 : $i_mod_stat;
        $i_mod_stat = function_exists('imagecreatefromgif') ? $i_mod_stat : 0;
        $i_mod_stat = function_exists('imagecreatefromjpeg') ? $i_mod_stat : 0;
        return function_exists('imagecreatefrompng') ? $i_mod_stat : 0;
    }
    /**
     * Checks if ini set is allowed
     */
    public function check_ini_set(): int
    {
        return @ini_set('memory_limit', @ini_get('memory_limit')) !== false ? 2 : 0;
    }
    /**
     * Checks memory limit.
     *
     * @param string $sMemLimit memory limit to compare with requirements
     */
    public function check_memory_limit($s_mem_limit = null): int
    {
        if ($s_mem_limit === null) {
            $s_mem_limit = @ini_get('memory_limit');
        }
        if ($s_mem_limit) {
            $s_def_limit = $this->get_minimum_memory_limit();
            $s_rec_limit = $this->get_recommend_memory_limit();
            $i_mem_limit = $this->get_bytes($s_mem_limit);
            if ($i_mem_limit === -1) {
                // -1 is equivalent to no memory limit
                $i_mod_stat = 2;
            } else {
                $i_mod_stat = $i_mem_limit >= $this->get_bytes($s_def_limit) ? 1 : 0;
                $i_mod_stat = $i_mod_stat ? $i_mem_limit >= $this->get_bytes($s_rec_limit) ? 2 : $i_mod_stat : $i_mod_stat;
            }
        } else {
            $i_mod_stat = -1;
        }
        return $i_mod_stat;
    }
    /**
     * Additional sql: do not check collation for \OxidEsales\Eshop\Core\SystemRequirements::$_aException columns
     */
    protected function get_additional_check(): string
    {
        $s_select = '';
        foreach ($this->_a_exception as $s_table => $s_column) {
            $s_select .= 'and ( TABLE_NAME != "' . $s_table . '" and COLUMN_NAME != "' . $s_column . '" ) ';
        }
        return $s_select;
    }
    /**
     * Checks tables and columns (\OxidEsales\Eshop\Core\SystemRequirements::$_aColumns) collation
     */
    public function check_collation(): array
    {
        $my_config = Registry::get_config();
        $a_collations = [];
        $s_collation = '';
        $s_select = 'select TABLE_NAME, COLUMN_NAME, COLLATION_NAME from INFORMATION_SCHEMA.columns
                    where TABLE_NAME not like "oxv\_%" and table_schema = "' . $my_config->get_config_param('dbName') . '"
                    and COLUMN_NAME in ("' . implode('", "', $this->_a_columns) . '") ' . $this->get_additional_check() . 'ORDER BY TABLE_NAME, COLUMN_NAME DESC;';
        $a_rez = Database_Connection_Provider::get_db()->get_all($s_select);
        foreach ($a_rez as $a_ret_table) {
            if (!$s_collation) {
                $s_collation = $a_ret_table[2];
            } else if ($a_ret_table[2] && $s_collation != $a_ret_table[2]) {
                $a_collations[$a_ret_table[0]][$a_ret_table[1]] = $a_ret_table[2];
            }
        }
        if ($this->_bl_sys_req_status === null) {
            $this->_bl_sys_req_status = true;
        }
        if (count($a_collations) > 0) {
            $this->_bl_sys_req_status = false;
        }
        return $a_collations;
    }
    /**
     * Checks if database cluster is installed
     */
    public function check_database_cluster(): int
    {
        return 2;
    }
    /**
     * Checks if PCRE unicode support is turned off/on. Should be on.
     */
    public function check_unicode_support(): int
    {
        return @preg_match('/\pL/u', 'a') == 1 ? 2 : 1;
    }
    /**
     * Checks if php_admin_flag file_uploads is ON
     */
    public function check_file_uploads(): int
    {
        $d_upload_file = -1;
        $s_file_uploads = @ini_get('file_uploads');
        if ($s_file_uploads !== false) {
            if ($s_file_uploads && ($s_file_uploads == '1' || strtolower($s_file_uploads) == 'on')) {
                $d_upload_file = 2;
            } else {
                $d_upload_file = 1;
            }
        }
        return $d_upload_file;
    }
    /**
     * Checks system requirements status
     *
     * @return bool
     */
    public function get_sys_req_status()
    {
        if ($this->_bl_sys_req_status == null) {
            $this->_bl_sys_req_status = true;
            $this->get_system_info();
            $this->check_collation();
        }
        return $this->_bl_sys_req_status;
    }
    /**
     * Runs through modules array and checks if current system fits requirements.
     * Returns array with module info:
     *   array( $sGroup, $sModuleName, $sModuleState ):
     *     $sGroup       - group of module
     *     $sModuleName  - name of checked module
     *     $sModuleState - module state:
     *       -1 - unable to datect, should not block
     *        0 - missing, blocks setup
     *        1 - fits min requirements
     *        2 - exists required or better
     *
     * @return array $aSysInfo
     */
    public function get_system_info(): array
    {
        $a_sys_info = [];
        $a_required_modules = $this->get_required_modules();
        $this->_bl_sys_req_status = true;
        foreach ($a_required_modules as $s_module => $s_group) {
            if (isset($a_sys_info[$s_group]) && !$a_sys_info[$s_group]) {
                $a_sys_info[$s_group] = [];
            }
            $i_module_state = $this->get_module_info($s_module);
            $a_sys_info[$s_group][$s_module] = $i_module_state;
            $this->_bl_sys_req_status = $this->_bl_sys_req_status && (bool) abs($i_module_state);
        }
        return $a_sys_info;
    }
    /**
     * Apply given filter function to all iterations of SystemRequirementInfo array.
     *
     * @param \Closure $filterFunction         Filter function used for the update of actual values; Function will
     *                                         receive the same arguments as provided from
     *                                         `iterateThroughSystemRequirementsInfo` method.
     * @return array An array which is in the same format as the main input argument but with updated data.
     */
    public static function filter(array $system_requirements_info, $filter_function): array
    {
        $iterator = static::iterate_through_system_requirements_info($system_requirements_info);
        foreach ($iterator as [$group_id, $module_id, $module_state]) {
            $system_requirements_info[$group_id][$module_id] = $filter_function($group_id, $module_id, $module_state);
        }
        return $system_requirements_info;
    }
    /**
     * Returns passed module state
     *
     * @param string $sModule module name to check
     *
     * @return integer $iModStat
     */
    public function get_module_info($s_module = null)
    {
        if ($s_module) {
            $i_mod_stat = null;
            $s_check_function = 'check' . str_replace(' ', '', ucwords(str_replace('_', ' ', $s_module)));
            return $this->{$s_check_function}();
        }
    }
    /**
     * Returns true if given module state is acceptable for setup process to continue.
     *
     * @param array $systemRequirementsInfo
     */
    public static function can_setup_continue($system_requirements_info): bool
    {
        $iterator = static::iterate_through_system_requirements_info($system_requirements_info);
        foreach ($iterator as [$group_id, $module_id, $module_state]) {
            if ($module_state === static::MODULE_STATUS_BLOCKS_SETUP) {
                return false;
            }
        }
        return true;
    }
    /**
     * Iterates through given SystemRequirementsInfo returning three items:
     *
     *   - GroupId
     *   - ModuleId
     *   - ModuleState
     *
     * @param array $systemRequirementsInfo
     * @return \Generator Iterator which yields [group_id, module_id, module_state].
     */
    public static function iterate_through_system_requirements_info($system_requirements_info)
    {
        foreach ($system_requirements_info as $group_id => $modules) {
            foreach ($modules as $module_id => $module_state) {
                yield [$group_id, $module_id, $module_state];
            }
        }
    }
    /**
     * Returns or prints url for info about missing web service configuration
     *
     * @param string $sIdent Module identifier
     *
     * @return mixed
     */
    public function get_req_info_url($s_ident)
    {
        $s_url = $this->_s_req_info_url;
        $a_info_map = $this->_a_info_map;
        $a_preparation_info_map = $this->_a_preparation_info_map;
        // only known will be anchored
        if (isset($a_info_map[$s_ident])) {
            $s_url .= '#' . $a_info_map[$s_ident];
        } elseif (isset($a_preparation_info_map[$s_ident])) {
            $s_url = $this->_s_preparation_info_url . '#' . $a_preparation_info_map[$s_ident];
        }
        return $s_url;
    }
    /**
     * Parses and calculates given string form byte size value
     *
     * @param string $bytes string form byte value (64M, 32K etc)
     */
    protected function get_bytes($s_bytes): int
    {
        $s_bytes = trim($s_bytes);
        $s_last = strtolower($s_bytes[strlen($s_bytes) - 1]);
        $s_bytes = (int) $s_bytes;
        switch ($s_last) {
            // The 'G' modifier is available since PHP 5.1.0
            // gigabytes
            case 'g':
                $s_bytes *= 1024;
            // megabytes
            // no break
            case 'm':
                $s_bytes *= 1024;
            // kilobytes
            // no break
            case 'k':
                $s_bytes *= 1024;
                break;
        }
        return $s_bytes;
    }
    /**
     * Check if correct AutoStart setting.
     */
    public function check_session_autostart(): int
    {
        $s_status = strtolower((string) @ini_get('session.auto_start'));
        return in_array($s_status, ['on', '1']) ? 0 : 2;
    }
    /**
     * Return minimum memory limit by edition.
     */
    protected function get_minimum_memory_limit(): string
    {
        return '32M';
    }
    /**
     * Return recommend memory limit by edition.
     */
    protected function get_recommend_memory_limit(): string
    {
        return '60M';
    }
}