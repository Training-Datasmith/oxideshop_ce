<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
use Oxid_Esales\Eshop\Core\Database\Adapter\Database_Interface;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Article list manager.
 * Collects list of article according to collection rules (categories, etc.).
 */
class Article_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * @var string SQL addon for sorting
     */
    protected $_s_custom_sorting;
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxarticle';
    /**
     * Set to true if Select Lists should be laoded
     *
     * @var bool
     */
    protected $_bl_load_select_lists = false;
    /**
     * Set Custom Sorting, simply an order by....
     *
     * @param string $sSorting Custom sorting
     */
    public function set_custom_sorting($s_sorting): void
    {
        $this->_s_custom_sorting = $s_sorting;
    }
    /**
     * Call enableSelectLists() for loading select lists in lst articles
     */
    public function enable_select_lists(): void
    {
        $this->_bl_load_select_lists = true;
    }
    /**
     * @inheritdoc
     * In addition to the parent method, this method includes profiling.
     *
     * @param string $sql        SQL select statement or prepared statement
     * @param array  $parameters Parameters to be used in a prepared statement
     */
    public function select_string($sql, array $parameters = []): void
    {
        start_profile('loadinglists');
        parent::select_string($sql, $parameters);
        stop_profile('loadinglists');
    }
    public function get_history_articles(): array
    {
        $session_history = Registry::get_session()->get_variable('aHistoryArticles');
        if (is_array($session_history)) {
            return $session_history;
        }
        $cookie_history = Registry::get_utils_server()->get_ox_cookie('aHistoryArticles');
        return is_string($cookie_history) ? explode('|', $cookie_history) : [];
    }
    /**
     * Set history article id's to session or cookie
     *
     * @param array $aArticlesIds array history article ids
     */
    public function set_history_articles($a_articles_ids): void
    {
        $session = Registry::get_session();
        if ($session->get_id()) {
            $session->set_variable('aHistoryArticles', $a_articles_ids);
            // clean cookie, if session started
            Registry::get_utils_server()->set_ox_cookie('aHistoryArticles', '');
        } else {
            Registry::get_utils_server()->set_ox_cookie('aHistoryArticles', implode('|', $a_articles_ids));
        }
    }
    /**
     * Loads up to 4 history (normally recently seen) articles from session, and adds $sArtId to history.
     * Returns article id array.
     *
     * @param string $sArtId Article ID
     * @param int    $iCnt   product count
     */
    public function load_history_articles($s_art_id, $i_cnt = 4): void
    {
        $a_history_articles = $this->get_history_articles();
        $a_history_articles[] = $s_art_id;
        // removing duplicates
        $a_history_articles = array_unique($a_history_articles);
        if (count($a_history_articles) > $i_cnt + 1) {
            array_shift($a_history_articles);
        }
        $this->set_history_articles($a_history_articles);
        //remove current article and return array
        //asignment =, not ==
        if (($i_current_art = array_search($s_art_id, $a_history_articles)) !== false) {
            unset($a_history_articles[$i_current_art]);
        }
        $a_history_articles = array_values($a_history_articles);
        $this->load_ids($a_history_articles);
        $this->sort_by_ids($a_history_articles);
    }
    /**
     * sort this list by given order.
     *
     * @param array $aIds ordered ids
     */
    public function sort_by_ids($a_ids): void
    {
        $this->_a_order_map = array_flip($a_ids);
        uksort($this->_a_array, $this->sort_by_order_map_callback(...));
    }
    /**
     * callback function only used from sortByIds
     *
     * @param string $key1 1st key
     * @param string $key2 2nd key
     *
     * @see oxArticleList::sortByIds
     *
     * @return int
     */
    protected function sort_by_order_map_callback($key1, $key2)
    {
        if (isset($this->_a_order_map[$key1])) {
            if (isset($this->_a_order_map[$key2])) {
                $i_diff = $this->_a_order_map[$key2] - $this->_a_order_map[$key1];
                if ($i_diff > 0) {
                    return -1;
                }
                if ($i_diff < 0) {
                    return 1;
                }
                return 0;
            }
            // first is here, but 2nd is not - 1st gets more priority
            return -1;
        }
        if (isset($this->_a_order_map[$key2])) {
            // first is not here, but 2nd is - 2nd gets more priority
            return 1;
        }
        // both unset, equal
        return 0;
    }
    /**
     * Loads newest shops articles from DB.
     *
     * @param int $iLimit Select limit
     */
    public function load_newest_articles($i_limit = null): void
    {
        //has module?
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadPriceForAddList')) {
            $this->get_base_object()->disable_price_load();
        }
        $this->_a_array = [];
        switch ($my_config->get_config_param('iNewestArticlesMode')) {
            case 0:
                // switched off, do nothing
                break;
            case 1:
                // manually entered
                $this->load_action_articles('oxnewest', $i_limit);
                break;
            case 2:
                $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
                $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
                if ($my_config->get_config_param('blNewArtByInsert')) {
                    $s_type = 'oxinsert';
                } else {
                    $s_type = 'oxtimestamp';
                }
                $s_select = "select * from {$s_article_table} ";
                $s_select .= "where oxparentid = '' and " . $this->get_base_object()->get_sql_active_snippet() . " and oxissearch = 1 order by {$s_type} desc ";
                if (!$i_limit = (int) $i_limit) {
                    $i_limit = $my_config->get_config_param('iNrofNewcomerArticles');
                }
                $s_select .= 'limit ' . $i_limit;
                $this->select_string($s_select);
                break;
        }
    }
    /**
     * Load top 5 articles
     *
     * @param int $iLimit Select limit
     */
    public function load_top5articles($i_limit = null): void
    {
        //has module?
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadPriceForAddList')) {
            $this->get_base_object()->disable_price_load();
        }
        switch ($my_config->get_config_param('iTop5Mode')) {
            case 0:
                // switched off, do nothing
                break;
            case 1:
                // manually entered
                $this->load_action_articles('oxtop5', $i_limit);
                break;
            case 2:
                $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
                $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
                //by default limit 5
                $s_limit = $i_limit > 0 ? 'limit ' . $i_limit : 'limit 5';
                $s_select = "select * from {$s_article_table} ";
                $s_select .= 'where ' . $this->get_base_object()->get_sql_active_snippet() . " and {$s_article_table}.oxissearch = 1 ";
                $s_select .= "and {$s_article_table}.oxparentid = '' and {$s_article_table}.oxsoldamount>0 ";
                $s_select .= "order by {$s_article_table}.oxsoldamount desc {$s_limit}";
                $this->select_string($s_select);
                break;
        }
    }
    /**
     * Loads shop AktionArticles.
     *
     * @param string $sActionID Action id
     * @param int    $iLimit    Select limit
     */
    public function load_action_articles($s_action_id, $i_limit = null): void
    {
        // Performance
        if (!trim($s_action_id)) {
            return;
        }
        $s_shop_id = Registry::get_config()->get_shop_id();
        $s_action_id = strtolower($s_action_id);
        $o_base_object = $this->get_base_object();
        $s_article_table = $o_base_object->get_view_name();
        $s_article_fields = $o_base_object->get_select_fields();
        $o_base = ox_new(\Oxid_Esales\Eshop\Application\Model\Actions::class);
        $s_active_sql = $o_base->get_sql_active_snippet();
        $s_view_name = $o_base->get_view_name();
        $s_limit = $i_limit > 0 ? 'limit ' . $i_limit : '';
        $s_select = "select {$s_article_fields} from oxactions2article\n                              left join {$s_article_table} on {$s_article_table}.oxid = oxactions2article.oxartid\n                              left join {$s_view_name} on {$s_view_name}.oxid = oxactions2article.oxactionid\n                              where oxactions2article.oxshopid = :oxshopid\n                                  and oxactions2article.oxactionid = :oxactionid\n                                  and {$s_active_sql}\n                                  and {$s_article_table}.oxid is not null and " . $o_base_object->get_sql_active_snippet() . "\n                              order by oxactions2article.oxsort {$s_limit}";
        $this->select_string($s_select, ['oxshopid' => $s_shop_id, 'oxactionid' => $s_action_id]);
    }
    /**
     * Loads article cross selling
     *
     * @param string $sArticleId Article id
     */
    public function load_article_cross_sell($s_article_id)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // Performance
        if (!$my_config->get_config_param('bl_perfLoadCrossselling')) {
            return null;
        }
        $o_base_object = $this->get_base_object();
        $s_article_table = $o_base_object->get_view_name();
        $s_select = "SELECT {$s_article_table}.*\n            FROM {$s_article_table} INNER JOIN oxobject2article ON oxobject2article.oxobjectid={$s_article_table}.oxid \n            WHERE oxobject2article.oxarticlenid = :oxarticlenid\n              AND {$o_base_object->get_sql_active_snippet()} \n            ORDER BY oxobject2article.oxsort";
        // #525 bidirectional cross selling
        if ($my_config->get_config_param('blBidirectCross')) {
            $s_select = "\n                (\n                    SELECT {$s_article_table}.*, O2A1.OXSORT as sorting FROM {$s_article_table}\n                        INNER JOIN oxobject2article AS O2A1 on\n                            ( O2A1.oxobjectid = {$s_article_table}.oxid AND O2A1.oxarticlenid = :oxarticlenid )\n                    WHERE 1\n                    AND " . $o_base_object->get_sql_active_snippet() . "\n                    AND ({$s_article_table}.oxid != :oxarticlenid)\n                )\n                UNION\n                (\n                    SELECT {$s_article_table}.*, O2A2.OXSORT as sorting FROM {$s_article_table}\n                        INNER JOIN oxobject2article AS O2A2 ON\n                            ( O2A2.oxarticlenid = {$s_article_table}.oxid AND O2A2.oxobjectid = :oxarticlenid )\n                    WHERE 1\n                    AND " . $o_base_object->get_sql_active_snippet() . "\n                    AND ({$s_article_table}.oxid != :oxarticlenid)\n                )\n                ORDER BY sorting";
        }
        $this->set_sql_limit(0, $my_config->get_config_param('iNrofCrossellArticles'));
        $this->select_string($s_select, ['oxarticlenid' => $s_article_id]);
    }
    /**
     * Loads article accessories
     *
     * @param string $sArticleId Article id
     */
    public function load_article_accessoires($s_article_id): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // Performance
        if (!$my_config->get_config_param('bl_perfLoadAccessoires')) {
            return;
        }
        $o_base_object = $this->get_base_object();
        $s_article_table = $o_base_object->get_view_name();
        $s_select = "select {$s_article_table}.* from oxaccessoire2article\n            left join {$s_article_table} on oxaccessoire2article.oxobjectid={$s_article_table}.oxid ";
        $s_select .= 'where oxaccessoire2article.oxarticlenid = :oxarticlenid ';
        $s_select .= " and {$s_article_table}.oxid is not null and " . $o_base_object->get_sql_active_snippet();
        //sorting articles
        $s_select .= ' order by oxaccessoire2article.oxsort';
        $this->select_string($s_select, ['oxarticlenid' => $s_article_id]);
    }
    /**
     * Loads only ID's and create Fake objects for cmp_categories.
     *
     * @param string $sCatId         Category tree ID
     * @param array  $aSessionFilter Like array ( catid => array( attrid => value,...))
     */
    public function load_category_ids($s_cat_id, $a_session_filter): void
    {
        $s_article_table = $this->get_base_object()->get_view_name();
        $s_select = $this->get_category_select($s_article_table . '.oxid as oxid', $s_cat_id, $a_session_filter);
        $this->create_id_list_from_sql($s_select);
    }
    /**
     * Loads articles for the give Category
     *
     * @param string $sCatId         Category tree ID
     * @param array  $aSessionFilter Like array ( catid => array( attrid => value,...))
     * @param int    $iLimit         Limit
     *
     * @return integer total Count of Articles in this Category
     */
    public function load_category_articles($s_cat_id, $a_session_filter, $i_limit = null)
    {
        $s_article_fields = $this->get_base_object()->get_select_fields();
        $s_select = $this->get_category_select($s_article_fields, $s_cat_id, $a_session_filter);
        // calc count - we can not use count($this) here as we might have paging enabled
        // #1970C - if any filters are used, we can not use cached category article count
        $i_article_count = null;
        if ($a_session_filter) {
            $i_article_count = Database_Provider::get_db()->get_one($this->get_category_count_select($s_cat_id, $a_session_filter));
        }
        if ($i_limit = (int) $i_limit) {
            $s_select .= " LIMIT {$i_limit}";
        }
        $this->select_string($s_select);
        if ($i_article_count !== null) {
            return $i_article_count;
        }
        // this select is FAST so no need to hazzle here with getNrOfArticles()
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_count()->get_cat_article_count($s_cat_id);
    }
    /**
     * Loads articles for the recommlist
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @param string $sRecommId       Recommlist ID
     * @param string $sArticlesFilter Additional filter for recommlist's items
     */
    public function load_recomm_articles($s_recomm_id, $s_articles_filter = null): void
    {
        $s_select = $this->get_article_select($s_recomm_id, $s_articles_filter);
        $this->select_string($s_select);
    }
    /**
     * Loads only ID's and create Fake objects.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @param string $sRecommId       Recommlist ID
     * @param string $sArticlesFilter Additional filter for recommlist's items
     */
    public function load_recomm_article_ids($s_recomm_id, $s_articles_filter): void
    {
        $s_select = $this->get_article_select($s_recomm_id, $s_articles_filter);
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_art_view = $table_view_name_generator->get_view_name('oxarticles');
        $s_partial = substr($s_select, strpos($s_select, ' from '));
        $s_select = "select distinct {$s_art_view}.oxid {$s_partial} ";
        $this->create_id_list_from_sql($s_select);
    }
    /**
     * Returns the appropriate SQL select
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @param string $sRecommId       Recommlist ID
     * @param string $sArticlesFilter Additional filter for recommlist's items
     *
     * @return string
     */
    protected function get_article_select($s_recomm_id, $s_articles_filter = null)
    {
        $s_recomm_id = Database_Provider::get_db()->quote($s_recomm_id);
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_art_view = $table_view_name_generator->get_view_name('oxarticles');
        $s_select = "select distinct {$s_art_view}.*, oxobject2list.oxdesc from oxobject2list ";
        $s_select .= "left join {$s_art_view} on oxobject2list.oxobjectid = {$s_art_view}.oxid ";
        return $s_select . ("where (oxobject2list.oxlistid = {$s_recomm_id}) " . $s_articles_filter);
    }
    /**
     * Loads only ID's and create Fake objects for cmp_categories.
     *
     * @param string $sSearchStr          Search string
     * @param string $sSearchCat          Search within category
     * @param string $sSearchVendor       Search within vendor
     * @param string $sSearchManufacturer Search within manufacturer
     */
    public function load_search_ids($s_search_str = '', $s_search_cat = '', $s_search_vendor = '', $s_search_manufacturer = ''): void
    {
        $o_db = Database_Provider::get_db();
        $s_search_cat = $s_search_cat ?: null;
        $s_search_vendor = $s_search_vendor ?: null;
        $s_search_manufacturer = $s_search_manufacturer ?: null;
        $s_where = null;
        if ($s_search_str) {
            $s_where = $this->get_search_select($s_search_str);
        }
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
        // longdesc field now is kept on different table
        $s_desc_join = $this->get_description_join();
        // load the articles
        $s_select = "select {$s_article_table}.oxid, {$s_article_table}.oxtimestamp from {$s_article_table} {$s_desc_join} where ";
        // must be additional conditions in select if searching in category
        if ($s_search_cat) {
            $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
            $s_select = "select {$s_article_table}.oxid from {$s_o2c_view} as oxobject2category, {$s_article_table} {$s_desc_join} ";
            $s_select .= 'where oxobject2category.oxcatnid=' . $o_db->quote($s_search_cat) . " and oxobject2category.oxobjectid={$s_article_table}.oxid and ";
        }
        $s_select .= $this->get_base_object()->get_sql_active_snippet();
        $s_select .= " and {$s_article_table}.oxparentid = '' and {$s_article_table}.oxissearch = 1 ";
        // #671
        if ($s_search_vendor) {
            $s_select .= " and {$s_article_table}.oxvendorid = " . $o_db->quote($s_search_vendor) . ' ';
        }
        if ($s_search_manufacturer) {
            $s_select .= " and {$s_article_table}.oxmanufacturerid = " . $o_db->quote($s_search_manufacturer) . ' ';
        }
        $s_select .= $s_where;
        if ($this->_s_custom_sorting) {
            $s_select .= " order by {$this->_s_custom_sorting} ";
        }
        $this->create_id_list_from_sql($s_select);
    }
    /**
     * Loads Id list of appropriate price products
     *
     * @param float $dPriceFrom Starting price
     * @param float $dPriceTo   Max price
     */
    public function load_price_ids($d_price_from, $d_price_to): void
    {
        $s_select = $this->get_price_select($d_price_from, $d_price_to);
        $this->create_id_list_from_sql($s_select);
    }
    /**
     * Loads articles, that price is bigger than passed $dPriceFrom and smaller
     * than passed $dPriceTo. Returns count of selected articles.
     *
     * @param double $dPriceFrom Price from
     * @param double $dPriceTo   Price to
     * @param object $oCategory  Active category object
     *
     * @return integer
     */
    public function load_price_articles($d_price_from, $d_price_to, $o_category = null)
    {
        $s_select = $this->get_price_select($d_price_from, $d_price_to);
        start_profile('loadPriceArticles');
        $this->select_string($s_select);
        stop_profile('loadPriceArticles');
        if (!$o_category) {
            return $this->count();
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_count()->get_price_cat_article_count($o_category->get_id(), $d_price_from, $d_price_to);
    }
    /**
     * Loads Products for specified vendor
     *
     * @param string $sVendorId Vendor id
     */
    public function load_vendor_i_ds($s_vendor_id): void
    {
        $s_select = $this->get_vendor_select($s_vendor_id);
        $this->create_id_list_from_sql($s_select);
    }
    /**
     * Loads Products for specified Manufacturer
     *
     * @param string $sManufacturerId Manufacturer id
     */
    public function load_manufacturer_i_ds($s_manufacturer_id): void
    {
        $s_select = $this->get_manufacturer_select($s_manufacturer_id);
        $this->create_id_list_from_sql($s_select);
    }
    /**
     * Loads articles that belongs to vendor, passed by parameter $sVendorId.
     * Returns count of selected articles.
     *
     * @param string $sVendorId Vendor ID
     * @param object $oVendor   Active vendor object
     *
     * @return integer
     */
    public function load_vendor_articles($s_vendor_id, $o_vendor = null)
    {
        $s_select = $this->get_vendor_select($s_vendor_id);
        $this->select_string($s_select);
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_count()->get_vendor_article_count($s_vendor_id);
    }
    /**
     * Loads articles that belongs to Manufacturer, passed by parameter $sManufacturerId.
     * Returns count of selected articles.
     *
     * @param string $sManufacturerId Manufacturer ID
     * @param object $oManufacturer   Active Manufacturer object
     *
     * @return integer
     */
    public function load_manufacturer_articles($s_manufacturer_id, $o_manufacturer = null)
    {
        $s_select = $this->get_manufacturer_select($s_manufacturer_id);
        $this->select_string($s_select);
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_count()->get_manufacturer_article_count($s_manufacturer_id);
    }
    /**
     * Load the list by article ids
     *
     * @param array $aIds Article ID array
     */
    public function load_ids($a_ids): void
    {
        if (!count($a_ids)) {
            $this->clear();
            return;
        }
        $o_base_object = $this->get_base_object();
        $s_article_table = $o_base_object->get_view_name();
        $s_article_fields = $o_base_object->get_select_fields();
        $ox_ids_sql = implode(',', Database_Provider::get_db()->quote_array($a_ids));
        $s_select = "select {$s_article_fields} from {$s_article_table} ";
        $s_select .= "where {$s_article_table}.oxid in ( " . $ox_ids_sql . ' ) and ';
        $s_select .= $o_base_object->get_sql_active_snippet();
        $this->select_string($s_select);
    }
    /**
     * Loads the article list by orders ids
     *
     * @param array $aOrders user orders array
     */
    public function load_order_articles($a_orders): void
    {
        if (!count($a_orders)) {
            $this->clear();
            return;
        }
        foreach ($a_orders as $o_order) {
            $a_orders_ids[] = $o_order->get_id();
        }
        $o_base_object = $this->get_base_object();
        $s_article_table = $o_base_object->get_view_name();
        $s_article_fields = $o_base_object->get_select_fields();
        $s_article_fields = str_replace("`{$s_article_table}`.`oxid`", '`oxorderarticles`.`oxartid` AS `oxid`', $s_article_fields);
        $s_select = "SELECT {$s_article_fields} FROM oxorderarticles ";
        $s_select .= "left join {$s_article_table} on oxorderarticles.oxartid = {$s_article_table}.oxid ";
        $s_select .= "WHERE oxorderarticles.oxorderid IN ( '" . implode("','", $a_orders_ids) . "' ) ";
        $s_select .= "order by {$s_article_table}.oxid ";
        $this->select_string($s_select);
        // not active or not available products must not have button "tobasket"
        $s_now = date('Y-m-d H:i:s');
        foreach ($this as $o_article) {
            if (!$o_article->oxarticles__oxactive->value && ($o_article->oxarticles__oxactivefrom->value > $s_now || $o_article->oxarticles__oxactiveto->value < $s_now)) {
                $o_article->set_buyable_state(false);
            }
        }
    }
    /**
     * Loads list of low stock state products
     *
     * @param array $aBasketContents product ids array
     */
    public function load_stock_remind_products($a_basket_contents): void
    {
        if (is_array($a_basket_contents) && count($a_basket_contents)) {
            $database = Database_Provider::get_db();
            foreach ($a_basket_contents as $o_basket_item) {
                $a_art_ids[] = $database->quote($o_basket_item->get_product_id());
            }
            $o_base_object = $this->get_base_object();
            $s_field_names = $o_base_object->get_select_fields();
            $table_name = $o_base_object->get_view_name();
            // fetching actual db stock state and reminder status
            $this->select_string(sprintf("select %s from %s where oxid in ( %s ) and oxremindactive = '1' and" . ' oxstock <= oxremindamount', $s_field_names, $table_name, implode(',', $a_art_ids)));
            // updating stock reminder state
            if ($this->count()) {
                $database->execute(sprintf("update %s set oxremindactive = '2' where :tableName in ( %s ) and oxremindactive = '1'" . ' and oxstock <= oxremindamount', $table_name, implode(',', $a_art_ids)), ['tableName' => $table_name . '.oxid']);
            }
        }
    }
    /**
     * Calculates, updates and returns next price renew time
     *
     * @return int
     */
    public function renew_price_update_time()
    {
        $i_time_to_update = $this->fetch_next_update_time();
        // next day?
        $i_curr_update_time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $i_next_update_time = $i_curr_update_time + 3600 * 24;
        // renew next update time
        if (!$i_time_to_update || $i_time_to_update > $i_next_update_time) {
            $i_time_to_update = $i_next_update_time;
        }
        \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('num', 'iTimeToUpdatePrices', $i_time_to_update);
        return $i_time_to_update;
    }
    /**
     * Updates prices where new price > 0, update time != '0000-00-00 00:00:00'
     * and <= CURRENT_TIMESTAMP. Returns update execution state (result of \OxidEsales\Eshop\Core\DatabaseProvider::execute())
     *
     * @param bool $blForceUpdate if true, forces price update without timeout check, default value is FALSE
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function update_upcoming_prices($bl_force_update = false)
    {
        $updated = false;
        if ($bl_force_update || $this->can_update_prices()) {
            // Transaction picks master automatically (see ESDEV-3804 and ESDEV-3822).
            $database = Database_Provider::get_db();
            $database->start_transaction();
            try {
                $curr_update_time = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
                // Collect article id's for later recalculation.
                $updated_product_ids = $database->get_col('SELECT `oxid` FROM `oxarticles` WHERE `oxupdatepricetime` > 0 AND `oxupdatepricetime` ' . '<= :oxupdatepricetime', ['oxupdatepricetime' => $curr_update_time]);
                // updating oxarticles
                $updated = $this->update_ox_articles($curr_update_time, $database);
                // renew update time in case update is not forced
                if (!$bl_force_update) {
                    $this->renew_price_update_time();
                }
                $database->commit_transaction();
            } catch (Exception $exception) {
                $database->rollback_transaction();
                throw $exception;
            }
            // recalculate oxvarminprice and oxvarmaxprice for parent
            if (is_array($updated_product_ids)) {
                foreach ($updated_product_ids as $product_id) {
                    $product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                    $product->load($product_id);
                    $product->on_change();
                }
            }
            $this->update_articles($updated_product_ids);
        }
        return $updated;
    }
    /**
     * fills the list simply with keys of the oxid and the position as value for the given sql
     *
     * @param string $sSql SQL select
     */
    protected function create_id_list_from_sql($s_sql)
    {
        $rs = Database_Provider::get_db()->select($s_sql);
        if ($rs != false && $rs->count() > 0) {
            while (!$rs->EOF) {
                $rs->fields = array_change_key_case($rs->fields, CASE_LOWER);
                $this[$rs->fields['oxid']] = $rs->fields['oxid'];
                //only the oxid
                $rs->fetch_row();
            }
        }
    }
    /**
     * Returns sql to fetch ids of articles fitting current filter
     *
     * @param string $sCatId  category id
     * @param array  $aFilter filters for this category
     *
     * @return string
     */
    protected function get_filter_ids_sql($s_cat_id, $a_filter)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        $s_o2a_view = $table_view_name_generator->get_view_name('oxobject2attribute');
        $s_filter = '';
        $i_cnt = 0;
        $o_db = Database_Provider::get_db();
        foreach ($a_filter as $s_attr_id => $s_value) {
            $s_value = (string) $s_value;
            if ($s_value !== '') {
                if ($s_filter) {
                    $s_filter .= ' or ';
                }
                $s_value = $o_db->quote($s_value);
                $s_attr_id = $o_db->quote($s_attr_id);
                $s_filter .= "( oa.oxattrid = {$s_attr_id} and oa.oxvalue = {$s_value} )";
                $i_cnt++;
            }
        }
        if ($s_filter) {
            $s_filter = "WHERE {$s_filter} ";
        }
        $s_filter_select = 'select oc.oxobjectid as oxobjectid, count(*) as cnt from ';
        $s_filter_select .= "(SELECT * FROM {$s_o2c_view} WHERE {$s_o2c_view}.oxcatnid = '{$s_cat_id}' GROUP BY {$s_o2c_view}.oxobjectid, {$s_o2c_view}.oxcatnid) as oc ";
        $s_filter_select .= "INNER JOIN {$s_o2a_view} as oa ON ( oa.oxobjectid = oc.oxobjectid ) ";
        return $s_filter_select . "{$s_filter} GROUP BY oa.oxobjectid HAVING cnt = {$i_cnt} ";
    }
    /**
     * Returns filtered articles sql "oxid in (filtered ids)" part
     *
     * @param string $sCatId  category id
     * @param array  $aFilter filters for this category
     *
     * @return string
     */
    protected function get_filter_sql($s_cat_id, $a_filter)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
        $a_ids = Database_Provider::get_db()->get_all($this->get_filter_ids_sql($s_cat_id, $a_filter));
        $s_ids = '';
        if ($a_ids) {
            foreach ($a_ids as $a_art) {
                if ($s_ids) {
                    $s_ids .= ', ';
                }
                $s_ids .= Database_Provider::get_db()->quote(current($a_art));
            }
            if ($s_ids) {
                $s_filter_sql = " and {$s_article_table}.oxid in ( {$s_ids} ) ";
            }
            // bug fix #0001695: if no articles found return false
        } elseif (!(empty(current($a_filter)) && count(array_unique($a_filter)) == 1)) {
            $s_filter_sql = ' and false ';
        }
        return $s_filter_sql;
    }
    /**
     * Creates SQL Statement to load Articles, etc.
     *
     * @param string $sFields        Fields which are loaded e.g. "oxid" or "*" etc.
     * @param string $sCatId         Category tree ID
     * @param array  $aSessionFilter Like array ( catid => array( attrid => value,...))
     *
     * @return string SQL
     */
    protected function get_category_select($s_fields, $s_cat_id, $a_session_filter)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        // ----------------------------------
        // sorting
        $s_sorting = '';
        if ($this->_s_custom_sorting) {
            $s_sorting = " {$this->_s_custom_sorting} , ";
        }
        // ----------------------------------
        // filtering ?
        $s_filter_sql = '';
        $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        if ($a_session_filter && isset($a_session_filter[$s_cat_id][$i_lang])) {
            $s_filter_sql = $this->get_filter_sql($s_cat_id, $a_session_filter[$s_cat_id][$i_lang]);
        }
        $o_db = Database_Provider::get_db();
        return "SELECT {$s_fields}, {$s_article_table}.oxtimestamp FROM {$s_o2c_view} as oc left join {$s_article_table}\n                    ON {$s_article_table}.oxid = oc.oxobjectid\n                    WHERE " . $this->get_base_object()->get_sql_active_snippet() . " and {$s_article_table}.oxparentid = ''\n                    and oc.oxcatnid = " . $o_db->quote($s_cat_id) . " {$s_filter_sql} ORDER BY {$s_sorting} oc.oxpos, oc.oxobjectid ";
    }
    /**
     * Creates SQL Statement to load Articles Count, etc.
     *
     * @param string $sCatId         Category tree ID
     * @param array  $aSessionFilter Like array ( catid => array( attrid => value,...))
     *
     * @return string SQL
     */
    protected function get_category_count_select($s_cat_id, $a_session_filter)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        // ----------------------------------
        // filtering ?
        $s_filter_sql = '';
        $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        if ($a_session_filter && isset($a_session_filter[$s_cat_id][$i_lang])) {
            $s_filter_sql = $this->get_filter_sql($s_cat_id, $a_session_filter[$s_cat_id][$i_lang]);
        }
        $o_db = Database_Provider::get_db();
        return "SELECT COUNT(*) FROM {$s_o2c_view} as oc left join {$s_article_table}\n                    ON {$s_article_table}.oxid = oc.oxobjectid\n                    WHERE " . $this->get_base_object()->get_sql_active_snippet() . " and {$s_article_table}.oxparentid = ''\n                    and oc.oxcatnid = " . $o_db->quote($s_cat_id) . " {$s_filter_sql} ";
    }
    /**
     * Forms and returns SQL query string for search in DB.
     *
     * @param string $sSearchString searching string
     *
     * @return string
     */
    protected function get_search_select($s_search_string)
    {
        // check if it has string at all
        if (!$s_search_string || !str_replace(' ', '', $s_search_string)) {
            return '';
        }
        $o_db = Database_Provider::get_db();
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_article_table = $this->get_base_object()->get_view_name();
        $a_search = explode(' ', $s_search_string);
        $s_search = ' and ( ';
        $bl_sep = false;
        // #723
        if ($my_config->get_config_param('blSearchUseAND')) {
            $s_search_sep = ' and ';
        } else {
            $s_search_sep = ' or ';
        }
        $a_search_cols = $my_config->get_config_param('aSearchCols');
        $my_utils_string = \Oxid_Esales\Eshop\Core\Registry::get_utils_string();
        foreach ($a_search as $s_search_string) {
            if (!strlen($s_search_string)) {
                continue;
            }
            if ($bl_sep) {
                $s_search .= $s_search_sep;
            }
            $bl_sep2 = false;
            $s_search .= '( ';
            $s_uml = $my_utils_string->prepare_str_for_search($s_search_string);
            foreach ($a_search_cols as $s_field) {
                if ($bl_sep2) {
                    $s_search .= ' or ';
                }
                // as long description now is on different table table must differ
                $s_search_table = $this->get_search_table_name($s_article_table, $s_field);
                $s_search .= $s_search_table . '.' . $s_field . ' like ' . $o_db->quote('%' . $s_search_string . '%') . ' ';
                if ($s_uml) {
                    $s_search .= ' or ' . $s_search_table . '.' . $s_field . ' like ' . $o_db->quote('%' . $s_uml . '%');
                }
                $bl_sep2 = true;
            }
            $s_search .= ' ) ';
            $bl_sep = true;
        }
        return $s_search . ' ) ';
    }
    /**
     * Builds SQL for selecting articles by price
     *
     * @param double $dPriceFrom Starting price
     * @param double $dPriceTo   Max price
     *
     * @return string
     */
    protected function get_price_select($d_price_from, $d_price_to)
    {
        $o_base_object = $this->get_base_object();
        $s_article_table = $o_base_object->get_view_name();
        $s_select_fields = $o_base_object->get_select_fields();
        $s_select = "select {$s_select_fields} from {$s_article_table} where oxvarminprice >= 0 ";
        $s_select .= $d_price_to ? 'and oxvarminprice <= ' . (float) $d_price_to . ' ' : ' ';
        $s_select .= $d_price_from ? 'and oxvarminprice  >= ' . (float) $d_price_from . ' ' : ' ';
        $s_select .= ' and ' . $o_base_object->get_sql_active_snippet() . " and {$s_article_table}.oxissearch = 1";
        if (!$this->_s_custom_sorting) {
            $s_select .= " order by {$s_article_table}.oxvarminprice asc , {$s_article_table}.oxid";
        } else {
            $s_select .= " order by {$this->_s_custom_sorting}, {$s_article_table}.oxid ";
        }
        return $s_select;
    }
    /**
     * Builds vendor select SQL statement
     *
     * @param string $sVendorId Vendor ID
     *
     * @return string
     */
    protected function get_vendor_select($s_vendor_id)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
        $o_base_object = $this->get_base_object();
        $s_field_names = $o_base_object->get_select_fields();
        $s_select = "select {$s_field_names} from {$s_article_table} ";
        $s_select .= "where {$s_article_table}.oxvendorid = " . Database_Provider::get_db()->quote($s_vendor_id) . ' ';
        $s_select .= ' and ' . $o_base_object->get_sql_active_snippet() . " and {$s_article_table}.oxparentid = ''  ";
        if ($this->_s_custom_sorting) {
            $s_select .= " ORDER BY {$this->_s_custom_sorting} ";
        }
        return $s_select;
    }
    /**
     * Builds Manufacturer select SQL statement
     *
     * @param string $sManufacturerId Manufacturer ID
     *
     * @return string
     */
    protected function get_manufacturer_select($s_manufacturer_id)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles');
        $o_base_object = $this->get_base_object();
        $s_field_names = $o_base_object->get_select_fields();
        $s_select = "select {$s_field_names} from {$s_article_table} ";
        $s_select .= "where {$s_article_table}.oxmanufacturerid = " . Database_Provider::get_db()->quote($s_manufacturer_id) . ' ';
        $s_select .= ' and ' . $o_base_object->get_sql_active_snippet() . " and {$s_article_table}.oxparentid = ''  ";
        if ($this->_s_custom_sorting) {
            $s_select .= " ORDER BY {$this->_s_custom_sorting} ";
        }
        return $s_select;
    }
    /**
     * Checks if price update can be executed - current time > next price update time
     *
     * @return bool
     */
    protected function can_update_prices()
    {
        if (Container_Facade::get_parameter('oxid_esales.cron_enabled')) {
            return false;
        }
        $time_to_update = Registry::get_config()->get_config_param('iTimeToUpdatePrices');
        return empty($time_to_update) || $time_to_update <= Registry::get_utils_date()->get_time();
    }
    /**
     * Method fetches next update time for renewing price update time.
     *
     * @return string
     */
    protected function fetch_next_update_time()
    {
        // Function is called inside a transaction or from admin backend which uses master connection only.
        // Transaction picks master automatically (see ESDEV-3804 and ESDEV-3822).
        $database = Database_Provider::get_db();
        // fetching next update time
        $s_q = $this->get_query_to_fetch_next_update_time();
        return $database->get_one(sprintf($s_q, '`oxarticles`'));
    }
    /**
     * Returns query to fetch next update time.
     *
     * @return string
     */
    protected function get_query_to_fetch_next_update_time()
    {
        return 'select unix_timestamp( oxupdatepricetime ) from %s where oxupdatepricetime > 0 order by oxupdatepricetime asc';
    }
    /**
     * Updates article.
     *
     * @param string            $sCurrUpdateTime
     * @param DatabaseInterface $oDb
     *
     * @return mixed
     */
    protected function update_ox_articles($s_curr_update_time, $o_db)
    {
        $s_q = $this->get_query_to_update_ox_article($s_curr_update_time);
        return $o_db->execute(sprintf($s_q, '`oxarticles`'));
    }
    /**
     * Method returns query to update article.
     *
     * @param string $sCurrUpdateTime
     *
     * @return string
     */
    protected function get_query_to_update_ox_article($s_curr_update_time)
    {
        return "UPDATE %s SET\n                       `oxprice`  = IF( `oxupdateprice` > 0, `oxupdateprice`, `oxprice` ),\n                       `oxpricea` = IF( `oxupdatepricea` > 0, `oxupdatepricea`, `oxpricea` ),\n                       `oxpriceb` = IF( `oxupdatepriceb` > 0, `oxupdatepriceb`, `oxpriceb` ),\n                       `oxpricec` = IF( `oxupdatepricec` > 0, `oxupdatepricec`, `oxpricec` ),\n                       `oxupdatepricetime` = 0,\n                       `oxupdateprice`     = 0,\n                       `oxupdatepricea`    = 0,\n                       `oxupdatepriceb`    = 0,\n                       `oxupdatepricec`    = 0\n                   WHERE\n                       `oxupdatepricetime` > 0 AND\n                       `oxupdatepricetime` <= '{$s_curr_update_time}'";
    }
    /**
     * Method is used for overloading.
     *
     * @param array $aUpdatedArticleIds
     */
    protected function update_articles($a_updated_article_ids)
    {
    }
    /**
     * Get description join. Needed in case of searching for data in table oxartextends or its views.
     *
     * @return string
     */
    protected function get_description_join()
    {
        $table = Registry::get(\Oxid_Esales\Eshop\Core\Table_View_Name_Generator::class)->get_view_name('oxarticles');
        $description_join = '';
        $search_columns = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aSearchCols');
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        if (is_array($search_columns) && in_array('oxlongdesc', $search_columns)) {
            $view_name = $table_view_name_generator->get_view_name('oxartextends');
            $description_join = " LEFT JOIN {$view_name} ON {$view_name}.oxid={$table}.oxid ";
        }
        return $description_join;
    }
    /**
     * Get search table name.
     * Needed in case of searching for data in table oxartextends or its views.
     *
     * @param string $table
     * @param string $field Chose table depending on field.
     *
     * @return string
     */
    protected function get_search_table_name($table, $field)
    {
        if ($field == 'oxlongdesc') {
            return Registry::get(\Oxid_Esales\Eshop\Core\Table_View_Name_Generator::class)->get_view_name('oxartextends');
        }
        return $table;
    }
}