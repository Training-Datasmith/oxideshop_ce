<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Counting utility class
 */
class Utils_Count extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Users view id, used to identify current state cache
     *
     * @var string
     */
    protected $_s_user_view_id;
    /**
     * Returns category article count
     *
     * @param string $sCatId Category Id
     *
     * @return int
     */
    public function get_cat_article_count($s_cat_id)
    {
        // current status unique ident
        $s_act_ident = $this->get_user_view_id();
        // loading from cache
        $a_cat_data = $this->get_cat_cache();
        if (!$a_cat_data || !isset($a_cat_data[$s_cat_id][$s_act_ident])) {
            return $this->set_cat_article_count($a_cat_data, $s_cat_id, $s_act_ident);
        }
        return $a_cat_data[$s_cat_id][$s_act_ident];
    }
    /**
     * Returns category article count price
     *
     * @param string $sCatId     Category Id
     * @param float $dPriceFrom from price
     * @param float $dPriceTo   to price
     *
     * @return int
     */
    public function get_price_cat_article_count($s_cat_id, $d_price_from, $d_price_to)
    {
        // current status unique ident
        $s_act_ident = $this->get_user_view_id();
        // loading from cache
        $a_cat_data = $this->get_cat_cache();
        if (!$a_cat_data || !isset($a_cat_data[$s_cat_id][$s_act_ident])) {
            return $this->set_price_cat_article_count($a_cat_data, $s_cat_id, $s_act_ident, $d_price_from, $d_price_to);
        }
        return $a_cat_data[$s_cat_id][$s_act_ident];
    }
    /**
     * Returns vendor article count
     *
     * @param string $sVendorId Vendor category Id
     *
     * @return int
     */
    public function get_vendor_article_count($s_vendor_id)
    {
        // current category unique ident
        $s_act_ident = $this->get_user_view_id();
        // loading from cache
        $a_vendor_data = $this->get_vendor_cache();
        if (!$a_vendor_data || !isset($a_vendor_data[$s_vendor_id][$s_act_ident])) {
            return $this->set_vendor_article_count($a_vendor_data, $s_vendor_id, $s_act_ident);
        }
        return $a_vendor_data[$s_vendor_id][$s_act_ident];
    }
    /**
     * Returns Manufacturer article count
     *
     * @param string $sManufacturerId Manufacturer category Id
     *
     * @return int
     */
    public function get_manufacturer_article_count($s_manufacturer_id)
    {
        // current category unique ident
        $s_act_ident = $this->get_user_view_id();
        // loading from cache
        $a_manufacturer_data = $this->get_manufacturer_cache();
        if (!$a_manufacturer_data || !isset($a_manufacturer_data[$s_manufacturer_id][$s_act_ident])) {
            return $this->set_manufacturer_article_count($a_manufacturer_data, $s_manufacturer_id, $s_act_ident);
        }
        return $a_manufacturer_data[$s_manufacturer_id][$s_act_ident];
    }
    /**
     * Saves and returns category article count into cache
     *
     * @param array  $aCache    Category cache data
     * @param string $sCatId    Unique category identifier
     * @param string $sActIdent ID
     *
     * @return int
     */
    public function set_cat_article_count($a_cache, $s_cat_id, $s_act_ident)
    {
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $s_table = $o_article->get_view_name();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // we use distinct if article is assigned to category twice
        $s_q = "SELECT COUNT( DISTINCT {$s_table}.`oxid` )\n               FROM {$s_o2c_view}\n                   INNER JOIN {$s_table} ON {$s_o2c_view}.`oxobjectid` = {$s_table}.`oxid` AND {$s_table}.`oxparentid` = ''\n               WHERE {$s_o2c_view}.`oxcatnid` = :oxcatnid AND " . $o_article->get_sql_active_snippet();
        $a_cache[$s_cat_id][$s_act_ident] = $o_db->get_one($s_q, ['oxcatnid' => $s_cat_id]);
        $this->set_cat_cache($a_cache);
        return $a_cache[$s_cat_id][$s_act_ident];
    }
    /**
     * Saves (if needed) and returns price category article count into cache
     *
     * @param array  $aCache     Category cache data
     * @param string $sCatId     Unique category ident
     * @param string $sActIdent  Category ID
     * @param int    $dPriceFrom Price from
     * @param int    $dPriceTo   Price to
     */
    public function set_price_cat_article_count($a_cache, $s_cat_id, $s_act_ident, $d_price_from, $d_price_to)
    {
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $s_table = $o_article->get_view_name();
        $params = [];
        $s_select = "SELECT count({$s_table}.oxid) FROM {$s_table} WHERE oxvarminprice >= 0";
        if ($d_price_to) {
            $s_select .= ' AND oxvarminprice <= :oxvarpriceto';
            $params['oxvarpriceto'] = (float) $d_price_to;
        }
        if ($d_price_from) {
            $s_select .= ' AND oxvarminprice  >= :oxvarpricefrom';
            $params['oxvarpricefrom'] = (float) $d_price_from;
        }
        $s_select .= " AND {$s_table}.oxissearch = 1 AND " . $o_article->get_sql_active_snippet();
        $a_cache[$s_cat_id][$s_act_ident] = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_select, $params);
        $this->set_cat_cache($a_cache);
        return $a_cache[$s_cat_id][$s_act_ident];
    }
    /**
     * Saves and returns vendors category article count into cache
     *
     * @param array  $aCache    Category cache data
     * @param string $sCatId    Unique vendor category ident
     * @param string $sActIdent Vendor category ID
     *
     * @return int
     */
    public function set_vendor_article_count($a_cache, $s_cat_id, $s_act_ident)
    {
        // if vendor/category name is 'root', skip counting
        if ($s_cat_id == 'root') {
            return 0;
        }
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $s_table = $o_article->get_view_name();
        // select each vendor articles count
        $s_q = "select {$s_table}.oxvendorid AS vendorId, count(*) from {$s_table} where ";
        $s_q .= "{$s_table}.oxvendorid <> '' and {$s_table}.oxparentid = '' and " . $o_article->get_sql_active_snippet() . " group by {$s_table}.oxvendorid ";
        $a_db_result = $this->get_assoc($s_q);
        foreach ($a_db_result as $s_key => $s_value) {
            $a_cache[$s_key][$s_act_ident] = $s_value;
        }
        $this->set_vendor_cache($a_cache);
        return $a_cache[$s_cat_id][$s_act_ident] ?? 0;
    }
    /**
     * Returns the query result as a two dimensional associative array.
     * The keys of the first level are the firsts value of each row.
     * The values of the first level arrays with numeric key that hold the all the values of each row but the first one,
     * which is used a a key in the first level.
     *
     * @param string $query
     * @param array  $parameters
     *
     * @return array
     */
    protected function get_assoc($query, $parameters = [])
    {
        $database = Database_Provider::get_db();
        $result_set = $database->select($query, $parameters);
        $rows = $result_set->fetch_all();
        if (!$rows) {
            return [];
        }
        $result = [];
        foreach ($rows as $row) {
            $first_column = array_keys($row)[0];
            $key = $row[$first_column];
            $values = array_values($row);
            if (2 <= count($values)) {
                $result[$key] = $values[1];
            }
        }
        return $result;
    }
    /**
     * Saves and returns Manufacturers category article count into cache
     *
     * @param array  $aCache    Category cache data
     * @param string $sMnfId    Unique Manufacturer ident
     * @param string $sActIdent Unique user context ID
     *
     * @return int
     */
    public function set_manufacturer_article_count($a_cache, $s_mnf_id, $s_act_ident)
    {
        // if Manufacturer/category name is 'root', skip counting
        if ($s_mnf_id == 'root') {
            return 0;
        }
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $s_art_table = $o_article->get_view_name();
        // select each Manufacturer articles count
        //#3485
        $s_q = "SELECT count({$s_art_table}.oxid) FROM {$s_art_table} WHERE {$s_art_table}.oxparentid = '' AND oxmanufacturerid = :manufacturerId AND " . $o_article->get_sql_active_snippet();
        $i_value = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_q, ['manufacturerId' => $s_mnf_id]);
        $a_cache[$s_mnf_id][$s_act_ident] = (int) $i_value;
        $this->set_manufacturer_cache($a_cache);
        return $a_cache[$s_mnf_id][$s_act_ident];
    }
    /**
     * Resets category (all categories) article count
     *
     * @param string $sCatId Category/vendor/manufacturer ID
     */
    public function reset_cat_article_count($s_cat_id = null): void
    {
        if (!$s_cat_id) {
            \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('aLocalCatCache', null);
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->to_file_cache('aLocalCatCache', '');
        } else {
            // loading from cache
            $a_cat_data = $this->get_cat_cache();
            if (isset($a_cat_data[$s_cat_id])) {
                unset($a_cat_data[$s_cat_id]);
                $this->set_cat_cache($a_cat_data);
            }
        }
    }
    /**
     * Resets price categories article count
     *
     * @param int $iPrice article price
     */
    public function reset_price_cat_article_count($i_price): void
    {
        // loading from cache
        if ($a_cat_data = $this->get_cat_cache()) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
            $categories_ids = Database_Provider::get_master()->get_col(sprintf('SELECT oxid FROM %s WHERE :oxpricefrom >= oxpricefrom AND :oxpriceto <= oxpriceto', $table_view_name_generator->get_view_name('oxcategories')), ['oxpricefrom' => (float) $i_price, 'oxpriceto' => (float) $i_price]);
            foreach ($categories_ids as $category_id) {
                if (isset($a_cat_data[$category_id])) {
                    unset($a_cat_data[$category_id]);
                }
            }
            if (!empty($categories_ids)) {
                $this->set_cat_cache($a_cat_data);
            }
        }
    }
    /**
     * Resets vendor (all vendors) article count
     *
     * @param string $sVendorId Category/vendor ID
     */
    public function reset_vendor_article_count($s_vendor_id = null): void
    {
        if (!$s_vendor_id) {
            \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('aLocalVendorCache', null);
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->to_file_cache('aLocalVendorCache', '');
        } else {
            // loading from cache
            $a_vendor_data = $this->get_vendor_cache();
            if (isset($a_vendor_data[$s_vendor_id])) {
                unset($a_vendor_data[$s_vendor_id]);
                $this->set_vendor_cache($a_vendor_data);
            }
        }
    }
    /**
     * Resets Manufacturer (all Manufacturers) article count
     *
     * @param string $sManufacturerId Category/Manufacturer ID
     */
    public function reset_manufacturer_article_count($s_manufacturer_id = null): void
    {
        if (!$s_manufacturer_id) {
            \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('aLocalManufacturerCache', null);
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->to_file_cache('aLocalManufacturerCache', '');
        } else {
            // loading from cache
            $a_manufacturer_data = $this->get_manufacturer_cache();
            if (isset($a_manufacturer_data[$s_manufacturer_id])) {
                unset($a_manufacturer_data[$s_manufacturer_id]);
                $this->set_manufacturer_cache($a_manufacturer_data);
            }
        }
    }
    /**
     * Loads and returns category cache data array
     *
     * @return array
     */
    protected function get_cat_cache()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // first look at the local cache
        $a_local_cat_cache = $my_config->get_global_parameter('aLocalCatCache');
        // if local cache is not set - loading from file cache
        if (!$a_local_cat_cache) {
            $s_local_cat_cache = \Oxid_Esales\Eshop\Core\Registry::get_utils()->from_file_cache('aLocalCatCache');
            if ($s_local_cat_cache) {
                $a_local_cat_cache = $s_local_cat_cache;
            } else {
                $a_local_cat_cache = null;
            }
            $my_config->set_global_parameter('aLocalCatCache', $a_local_cat_cache);
        }
        return $a_local_cat_cache;
    }
    /**
     * Writes category data into cache
     *
     * @param array $aCache A cacheable data
     */
    protected function set_cat_cache($a_cache)
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('aLocalCatCache', $a_cache);
        \Oxid_Esales\Eshop\Core\Registry::get_utils()->to_file_cache('aLocalCatCache', $a_cache);
    }
    /**
     * Writes vendor data into cache
     *
     * @param array $aCache A cacheable data
     */
    protected function set_vendor_cache($a_cache)
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('aLocalVendorCache', $a_cache);
        \Oxid_Esales\Eshop\Core\Registry::get_utils()->to_file_cache('aLocalVendorCache', $a_cache);
    }
    /**
     * Writes Manufacturer data into cache
     *
     * @param array $aCache A cacheable data
     */
    protected function set_manufacturer_cache($a_cache)
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config()->set_global_parameter('aLocalManufacturerCache', $a_cache);
        \Oxid_Esales\Eshop\Core\Registry::get_utils()->to_file_cache('aLocalManufacturerCache', $a_cache);
    }
    /**
     * Loads and returns category/vendor cache data array
     *
     * @return array
     */
    protected function get_vendor_cache()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // first look at the local cache
        $a_local_vendor_cache = $my_config->get_global_parameter('aLocalVendorCache');
        // if local cache is not set - loading from file cache
        if (!$a_local_vendor_cache) {
            $s_local_vendor_cache = \Oxid_Esales\Eshop\Core\Registry::get_utils()->from_file_cache('aLocalVendorCache');
            if ($s_local_vendor_cache) {
                $a_local_vendor_cache = $s_local_vendor_cache;
            } else {
                $a_local_vendor_cache = null;
            }
            $my_config->set_global_parameter('aLocalVendorCache', $a_local_vendor_cache);
        }
        return $a_local_vendor_cache;
    }
    /**
     * Loads and returns category/Manufacturer cache data array
     *
     * @return array
     */
    protected function get_manufacturer_cache()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // first look at the local cache
        $a_local_manufacturer_cache = $my_config->get_global_parameter('aLocalManufacturerCache');
        // if local cache is not set - loading from file cache
        if (!$a_local_manufacturer_cache) {
            $s_local_manufacturer_cache = \Oxid_Esales\Eshop\Core\Registry::get_utils()->from_file_cache('aLocalManufacturerCache');
            if ($s_local_manufacturer_cache) {
                $a_local_manufacturer_cache = $s_local_manufacturer_cache;
            } else {
                $a_local_manufacturer_cache = null;
            }
            $my_config->set_global_parameter('aLocalManufacturerCache', $a_local_manufacturer_cache);
        }
        return $a_local_manufacturer_cache;
    }
    /**
     * Returns user view id (Shop, language, RR group index...)
     *
     * @param bool $blReset optional, default = false
     *
     * @return string
     */
    protected function get_user_view_id($bl_reset = false)
    {
        if ($this->_s_user_view_id != null && !$bl_reset) {
            return $this->_s_user_view_id;
        }
        // loading R&R data from session
        $user_session_groups = $this->get_current_user_session_groups();
        $this->_s_user_view_id = md5(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id() . \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_tag() . serialize($user_session_groups) . (int) $this->is_admin());
        return $this->_s_user_view_id;
    }
    /**
     * Get current user groups
     *
     * @return array|null
     */
    protected function get_current_user_session_groups()
    {
        return null;
    }
}