<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Shop manager.
 * Performs configuration and object loading or deletion.
 */
class Shop extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /** @var string Name of current class. */
    protected $_s_class_name = 'oxshop';
    /** @var array Multi shop tables, set in config. */
    protected array $_a_multi_shop_tables = [];
    /** @var array Query variables. */
    protected $_a_queries = [];
    /** @var array Database tables. */
    protected $_a_tables;
    /** @var bool Defines if multishop inherits categories. */
    protected $_bl_multi_shop_inherit_categories = false;
    private static bool $disabled_view_usage = false;
    public static function disable_views(): void
    {
        self::$disabled_view_usage = true;
    }
    /**
     * Database tables setter.
     *
     * @param array $aTables
     */
    public function set_tables($a_tables): void
    {
        $this->_a_tables = $a_tables;
    }
    /**
     * Database tables getter.
     *
     * @return array
     */
    public function get_tables()
    {
        if (is_null($this->_a_tables)) {
            $a_tables = $this->form_database_tables_array();
            $this->set_tables($a_tables);
        }
        return $this->_a_tables;
    }
    /**
     * Database queries setter.
     *
     * @param array $aQueries
     */
    public function set_queries($a_queries): void
    {
        $this->_a_queries = $a_queries;
    }
    /**
     * Database queries getter.
     *
     * @return array
     */
    public function get_queries()
    {
        return $this->_a_queries;
    }
    /**
     * Add a query to query array.
     *
     * @param string $sQuery
     */
    public function add_query($s_query): void
    {
        $this->_a_queries[] = $s_query;
    }
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->is_shop_valid()) {
            Registry::get_logger()->error('Shop is not valid');
            return;
        }
        $this->init('oxshops');
        if ($i_max = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iMaxShopId')) {
            $this->set_max_shop_id($i_max);
        }
    }
    /**
     * Sets multi shop tables
     *
     * @param string $aMultiShopTables multi shop tables
     */
    public function set_multi_shop_tables($a_multi_shop_tables): void
    {
        $this->_a_multi_shop_tables = $a_multi_shop_tables;
    }
    public function get_multi_shop_tables(): array
    {
        return $this->_a_multi_shop_tables;
    }
    /**
     * (Re)generates shop views
     *
     * @param bool  $multishopInheritCategories Config option blMultishopInherit_oxcategories
     * @param array $mallInherit                Array of config options blMallInherit
     *
     * @return bool is all views generated successfully
     */
    public function generate_views($multishop_inherit_categories = false, $mall_inherit = null)
    {
        $this->prepare_views_queries();
        $bl_success = $this->run_queries();
        $this->clean_invalid_views();
        return $bl_success;
    }
    /**
     * Returns default category of the shop.
     *
     * @return string
     */
    public function get_default_category()
    {
        return $this->oxshops__oxdefcat->value;
    }
    /**
     * Returns true if shop in productive mode
     *
     * @return bool
     */
    public function is_productive_mode()
    {
        return (bool) $this->oxshops__oxproductive->value;
    }
    /**
     * Creates view query and adds it to query array.
     *
     * @param string $sTable     Table name
     * @param array  $aLanguages Language array( id => abbreviation )
     */
    public function create_view_query($s_table, $a_languages = null): void
    {
        $s_start = 'CREATE OR REPLACE SQL SECURITY INVOKER VIEW';
        if (!is_array($a_languages)) {
            $a_languages = [0 => null];
        }
        foreach ($a_languages as $i_lang => $s_lang) {
            $this->add_view_language_query($s_start, $s_table, $i_lang, $s_lang);
        }
    }
    public function get_view_name($force_core_table_usage = null)
    {
        if (self::$disabled_view_usage) {
            return $this->get_core_table_name();
        }
        return parent::get_view_name($force_core_table_usage);
    }
    /**
     * Returns table field name mapping sql section for single language views
     *
     * @param string $sTable Table name
     * @param int    $iLang  Language id
     *
     * @return string
     */
    protected function get_view_select($s_table, $i_lang)
    {
        $o_meta_data = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        $a_fields = $o_meta_data->get_singlelang_fields($s_table, $i_lang);
        foreach ($a_fields as $s_core_field => $s_field) {
            if ($s_core_field !== $s_field) {
                $a_fields[$s_core_field] = $s_field . ' AS ' . $s_core_field;
            }
        }
        return implode(',', $a_fields);
    }
    /**
     * Returns table fields sql section for multiple language views
     *
     * @param string $sTable table name
     *
     * @return string
     */
    protected function get_view_select_multilang($s_table)
    {
        $a_fields = [];
        $o_meta_data = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        $a_tables = array_merge([$s_table], $o_meta_data->get_all_multi_tables($s_table));
        foreach ($a_tables as $s_table_name) {
            $a_table_fields = $o_meta_data->get_fields($s_table_name);
            foreach ($a_table_fields as $s_core_field => $s_field) {
                if (!isset($a_fields[$s_core_field])) {
                    $a_fields[$s_core_field] = $s_field;
                }
            }
        }
        return implode(',', $a_fields);
    }
    /**
     * Returns all language table view JOIN section
     *
     * @param string $sTable table name
     *
     * @return string $sSQL
     */
    protected function get_view_join_all($s_table)
    {
        $s_join = ' ';
        $o_meta_data = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        $a_tables = $o_meta_data->get_all_multi_tables($s_table);
        if (count($a_tables)) {
            foreach ($a_tables as $s_table_name) {
                $s_join .= "LEFT JOIN {$s_table_name} USING (OXID) ";
            }
        }
        return $s_join;
    }
    /**
     * Returns language table view JOIN section
     *
     * @param string $sTable table name
     * @param int    $iLang  language id
     *
     * @return string $sSQL
     */
    protected function get_view_join_lang($s_table, $i_lang)
    {
        $s_join = ' ';
        $s_lang_table = get_lang_table_name($s_table, $i_lang);
        if ($s_lang_table && $s_lang_table !== $s_table) {
            $s_join .= "LEFT JOIN {$s_lang_table} USING (OXID) ";
        }
        return $s_join;
    }
    /**
     * Gets all invalid views and drops them from database
     */
    protected function clean_invalid_views()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_lang = Registry::get_lang();
        $a_languages = $o_lang->get_language_ids($this->get_id());
        $a_multilang_tables = Registry::get_lang()->get_multi_lang_tables();
        $a_multishop_tables = $this->get_multi_shop_tables();
        $o_lang = Registry::get_lang();
        $a_all_shop_languages = $o_lang->get_all_shop_language_ids();
        $o_views_validator = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop_View_Validator::class);
        $o_views_validator->set_shop_id($this->get_id());
        $o_views_validator->set_languages($a_languages);
        $o_views_validator->set_all_shop_languages($a_all_shop_languages);
        $o_views_validator->set_multi_lang_tables($a_multilang_tables);
        $o_views_validator->set_multi_shop_tables($a_multishop_tables);
        $a_views = $o_views_validator->get_invalid_views();
        foreach ($a_views as $s_view) {
            $o_db->execute('DROP VIEW IF EXISTS `' . $s_view . '`');
        }
    }
    /**
     * Creates all view queries and adds them in query array
     */
    protected function prepare_views_queries()
    {
        $o_lang = Registry::get_lang();
        $a_languages = $o_lang->get_language_ids($this->get_id());
        $a_multilang_tables = Registry::get_lang()->get_multi_lang_tables();
        $a_tables = $this->get_tables();
        foreach ($a_tables as $s_table) {
            $this->create_view_query($s_table);
            if (in_array($s_table, $a_multilang_tables)) {
                $this->create_view_query($s_table, $a_languages);
            }
        }
    }
    /**
     * Adds view language query to query array.
     *
     * @param string $queryStart
     * @param string $table
     * @param int    $languageId
     * @param string $languageAbbr
     */
    protected function add_view_language_query($query_start, $table, $language_id, $language_abbr)
    {
        $s_lang_addition = $language_abbr === null ? '' : "_{$language_abbr}";
        $s_view_table = "oxv_{$table}{$s_lang_addition}";
        if ($language_abbr === null) {
            $s_fields = $this->get_view_select_multilang($table);
            $s_join = $this->get_view_join_all($table);
        } else {
            $s_fields = $this->get_view_select($table, $language_id);
            $s_join = $this->get_view_join_lang($table, $language_id);
        }
        if ('' === $s_fields) {
            Registry::get_logger()->error("View for {$table} can not be generated, Please check if table exists");
            return;
        }
        $s_query = "{$query_start} `{$s_view_table}` AS SELECT {$s_fields} FROM {$table}{$s_join}";
        $this->add_query($s_query);
    }
    /**
     * Runs stored queries
     * Returns false when any of the queries fail, otherwise return true
     *
     * @return bool
     */
    protected function run_queries()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $a_queries = $this->get_queries();
        $b_success = true;
        foreach ($a_queries as $s_query) {
            try {
                $o_db->execute($s_query);
            } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
                \Oxid_Esales\Eshop\Core\Registry::get_logger()->error($exception->get_message(), [$exception]);
                $b_success = false;
            }
        }
        return $b_success;
    }
    /**
     * Forms array of tables which are available.
     *
     * @return array
     */
    protected function form_database_tables_array()
    {
        $multilanguage_tables = Registry::get_lang()->get_multi_lang_tables();
        return array_unique($multilanguage_tables);
    }
    /**
     * Checks whether current shop is valid.
     *
     * @return bool
     */
    protected function is_shop_valid()
    {
        return true;
    }
}