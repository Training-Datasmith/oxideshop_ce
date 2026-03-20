<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Shop view validator.
 * checks which views are valid / invalid
 */
class Shop_View_Validator
{
    protected $_a_multi_lang_tables = [];
    protected $_a_multi_shop_tables = [];
    protected $_a_languages = [];
    protected $_a_all_shop_languages = [];
    protected $_i_shop_id;
    protected $_a_all_views = [];
    protected $_a_shop_views = [];
    protected $_a_valid_shop_views = [];
    /**
     * Sets multi language tables.
     */
    public function set_multi_lang_tables($a_multi_lang_tables): void
    {
        $this->_a_multi_lang_tables = $a_multi_lang_tables;
    }
    /**
     * Returns multi lang tables
     *
     * @return array
     */
    public function get_multi_lang_tables()
    {
        return $this->_a_multi_lang_tables;
    }
    /**
     * Sets multi shop tables.
     *
     * @param array $aMultiShopTables
     */
    public function set_multi_shop_tables($a_multi_shop_tables): void
    {
        $this->_a_multi_shop_tables = $a_multi_shop_tables;
    }
    /**
     * Returns multi shop tables
     *
     * @return array
     */
    public function get_multi_shop_tables()
    {
        return $this->_a_multi_shop_tables;
    }
    /**
     * Returns list of active languages in shop
     *
     * @param array $aLanguages
     */
    public function set_languages($a_languages): void
    {
        $this->_a_languages = $a_languages;
    }
    /**
     * Gets languages.
     *
     * @return array
     */
    public function get_languages()
    {
        return $this->_a_languages;
    }
    /**
     * Returns list of active languages in shop
     *
     * @param array $aAllShopLanguages
     */
    public function set_all_shop_languages($a_all_shop_languages): void
    {
        $this->_a_all_shop_languages = $a_all_shop_languages;
    }
    /**
     * Gets all shop languages.
     *
     * @return array
     */
    public function get_all_shop_languages()
    {
        return $this->_a_all_shop_languages;
    }
    /**
     * Sets shop id.
     *
     * @param integer $iShopId
     */
    public function set_shop_id($i_shop_id): void
    {
        $this->_i_shop_id = $i_shop_id;
    }
    /**
     * Returns list of available shops
     *
     * @return integer
     */
    public function get_shop_id()
    {
        return $this->_i_shop_id;
    }
    /**
     * Returns list of all shop views
     *
     * @return array
     */
    protected function get_all_views()
    {
        if (empty($this->_a_all_views)) {
            $this->_a_all_views = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_col("SHOW TABLES LIKE  'oxv\\_%'");
        }
        return $this->_a_all_views;
    }
    /**
     * Checks if given view name belongs to current subshop or is general view
     *
     * @param string $sViewName View name
     *
     * @return bool
     */
    protected function is_current_shop_view($s_view_name)
    {
        $bl_result = false;
        $bl_ends_with_shop_id = preg_match('/[_]([0-9]+)$/', $s_view_name, $a_match_ends_with_shop_id);
        $bl_contains_shop_id = preg_match('/[_]([0-9]+)[_]/', $s_view_name, $a_match_contains_shop_id);
        if (!$bl_ends_with_shop_id && !$bl_contains_shop_id || $bl_ends_with_shop_id && $a_match_ends_with_shop_id[1] == $this->get_shop_id() || $bl_contains_shop_id && $a_match_contains_shop_id[1] == $this->get_shop_id()) {
            return true;
        }
        return $bl_result;
    }
    /**
     * Returns list of shop specific views currently in database
     *
     * @return array
     */
    protected function get_shop_views()
    {
        if (empty($this->_a_shop_views)) {
            $this->_a_shop_views = [];
            $a_all_views = $this->get_all_views();
            foreach ($a_all_views as $s_view) {
                if ($this->is_current_shop_view($s_view)) {
                    $this->_a_shop_views[] = $s_view;
                }
            }
        }
        return $this->_a_shop_views;
    }
    /**
     * Returns list of valid shop views
     *
     * @return array
     */
    protected function get_valid_shop_views()
    {
        if (empty($this->_a_valid_shop_views)) {
            $a_tables = $this->get_shop_tables();
            $this->_a_valid_shop_views = [];
            foreach ($a_tables as $s_table) {
                $this->prepare_shop_table_view_names($s_table);
            }
        }
        return $this->_a_valid_shop_views;
    }
    /**
     * Get list of shop tables
     *
     * @return array
     */
    protected function get_shop_tables()
    {
        return $this->get_multilang_tables();
    }
    /**
     * Appends possible table views to $this->_aValidShopViews variable.
     */
    protected function prepare_shop_table_view_names(string $table_name)
    {
        $this->_a_valid_shop_views[] = 'oxv_' . $table_name;
        if (in_array($table_name, $this->get_multi_lang_tables())) {
            foreach ($this->get_all_shop_languages() as $s_lang) {
                $this->_a_valid_shop_views[] = 'oxv_' . $table_name . '_' . $s_lang;
            }
        }
    }
    /**
     * Checks if view name is valid according to current config
     *
     * @param string $sViewName View name
     */
    protected function is_view_valid($s_view_name): bool
    {
        return in_array($s_view_name, $this->get_valid_shop_views());
    }
    /**
     * Returns list of invalid views
     */
    public function get_invalid_views(): array
    {
        $a_invalid_views = [];
        $a_shop_views = $this->get_shop_views();
        foreach ($a_shop_views as $s_view) {
            if (!$this->is_view_valid($s_view)) {
                $a_invalid_views[] = $s_view;
            }
        }
        return $a_invalid_views;
    }
}