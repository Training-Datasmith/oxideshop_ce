<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class controls article assignment to category.
 */
class Article_Extend_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxcategories', 1, 1, 0],
        ['oxdesc', 'oxcategories', 1, 1, 0],
        ['oxid', 'oxcategories', 0, 0, 0],
        ['oxid', 'oxcategories', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxcategories', 1, 1, 0], ['oxdesc', 'oxcategories', 1, 1, 0], ['oxid', 'oxcategories', 0, 0, 0], ['oxid', 'oxobject2category', 0, 0, 1], ['oxtime', 'oxobject2category', 0, 0, 1], ['oxid', 'oxcategories', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $categories_table = $this->get_view_name('oxcategories');
        $object_to_category_view = $this->get_view_name('oxobject2category');
        $database = Database_Provider::get_db();
        $ox_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $synch_oxid = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if ($ox_id) {
            // all categories article is in
            return " from {$object_to_category_view} left join {$categories_table}" . " on {$categories_table}.oxid={$object_to_category_view}.oxcatnid " . " where {$object_to_category_view}.oxobjectid = " . $database->quote($ox_id) . " and {$categories_table}.oxid is not null ";
        }
        return " from {$categories_table} where {$categories_table}.oxid not in ( " . " select {$categories_table}.oxid from {$object_to_category_view} " . "left join {$categories_table} on {$categories_table}.oxid={$object_to_category_view}.oxcatnid " . " where {$object_to_category_view}.oxobjectid = " . $database->quote($synch_oxid) . " and {$categories_table}.oxid is not null ) and {$categories_table}.oxpriceto = '0'";
    }
    /**
     * Returns array with DB records
     *
     * @param string $sQ SQL query
     *
     * @return array
     */
    protected function get_data_fields($s_q)
    {
        $data_fields = parent::get_data_fields($s_q);
        if (Registry::get_request()->get_request_escaped_parameter('oxid') && is_array($data_fields) && count($data_fields)) {
            // looking for smallest time value to mark record as main category ..
            $minimal_position = null;
            $minimal_value = null;
            reset($data_fields);
            foreach ($data_fields as $position => $fields) {
                // already set ?
                if ($fields['_3'] == '0') {
                    $minimal_position = null;
                    break;
                }
                if (!$minimal_value) {
                    $minimal_value = $fields['_3'];
                    $minimal_position = $position;
                } elseif ($minimal_value > $fields['_3']) {
                    $minimal_position = $position;
                }
            }
            // setting primary category
            if (isset($minimal_position)) {
                $data_fields[$minimal_position]['_3'] = '0';
            }
        }
        return $data_fields;
    }
    /**
     * Removes article from chosen category
     */
    public function remove_cat(): void
    {
        $categories_to_remove = $this->get_action_ids('oxcategories.oxid');
        $ox_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $data_base = Database_Provider::get_db();
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $categories_table = $this->get_view_name('oxcategories');
            $categories_to_remove = $this->get_all($this->add_filter("select {$categories_table}.oxid " . $this->get_query()));
        }
        // removing all
        if (is_array($categories_to_remove) && count($categories_to_remove)) {
            $query = 'delete from oxobject2category where oxobject2category.oxobjectid = :oxobjectid and ';
            $query = $this->update_query_for_removing_article_from_category($query);
            $query .= ' oxcatnid in (' . implode(', ', Database_Provider::get_db()->quote_array($categories_to_remove)) . ')';
            $data_base->Execute($query, ['oxobjectid' => $ox_id]);
            // updating oxtime values
            $this->update_ox_time($ox_id);
        }
        $this->reset_art_seo_url($ox_id, $categories_to_remove);
        $this->reset_content_cache();
        $this->on_categories_removal($categories_to_remove, $ox_id);
    }
    /**
     * Adds article to chosen category
     *
     * @throws Exception
     */
    public function add_cat(): void
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $categories_to_add = $this->get_action_ids('oxcategories.oxid');
        $ox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $shop_id = $config->get_shop_id();
        $object_to_category_view = $this->get_view_name('oxobject2category');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $categories_table = $this->get_view_name('oxcategories');
            $categories_to_add = $this->get_all($this->add_filter("select {$categories_table}.oxid " . $this->get_query()));
        }
        if (isset($categories_to_add) && is_array($categories_to_add)) {
            // We force reading from master to prevent issues with slow replications or open transactions
            // (see ESDEV-3804 and ESDEV-3822).
            $database = Database_Provider::get_master();
            $object_to_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Object2Category::class);
            foreach ($categories_to_add as $s_add) {
                // check, if it's already in, then don't add it again
                $s_select = 'select 1 from ' . $object_to_category_view . ' as oxobject2category ' . 'where oxobject2category.oxcatnid = :oxcatnid ' . 'and oxobject2category.oxobjectid = :oxobjectid';
                if ($database->get_one($s_select, ['oxcatnid' => $s_add, 'oxobjectid' => $ox_id])) {
                    continue;
                }
                $object_to_category->set_id(md5($ox_id . $s_add . $shop_id));
                $object_to_category->oxobject2category__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($ox_id);
                $object_to_category->oxobject2category__oxcatnid = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $object_to_category->oxobject2category__oxtime = new \Oxid_Esales\Eshop\Core\Field(time());
                $object_to_category->save();
            }
            $this->update_ox_time($ox_id);
            $this->reset_art_seo_url($ox_id);
            $this->reset_content_cache();
            $this->on_categories_add($categories_to_add);
        }
    }
    /**
     * Updates oxtime value for product
     *
     * @param string $oxId product id
     */
    protected function update_ox_time($ox_id)
    {
        $database = Database_Provider::get_db();
        $object_to_category_view = $this->get_view_name('oxobject2category');
        $query_to_embed = $this->form_query_to_embed_for_updating_time();
        // updating oxtime values
        $query = "update oxobject2category set oxtime = 0 where oxobjectid = :oxobjectid {$query_to_embed} and oxid = (\n                    select oxid from (\n                        select oxid from {$object_to_category_view} where oxobjectid = :oxobjectid {$query_to_embed}\n                        order by oxtime limit 1\n                    ) as _tmp\n                )";
        $database->execute($query, ['oxobjectid' => $ox_id]);
    }
    /**
     * Sets selected category as a default
     */
    public function set_as_default(): void
    {
        $def_cat = Registry::get_request()->get_request_escaped_parameter('defcat');
        $ox_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $query_to_embed = $this->form_query_to_embed_for_setting_category_as_default();
        // #0003650: increment all product references independent to active shop
        $query = "update oxobject2category set oxtime = oxtime + 10 where oxobjectid = :oxobjectid {$query_to_embed}";
        Database_Provider::get_instance()->get_db()->execute($query, ['oxobjectid' => $ox_id]);
        // set main category for active shop
        $query = "update oxobject2category set oxtime = 0\n                  where oxobjectid = :oxobjectid and oxcatnid = :oxcatnid {$query_to_embed}";
        Database_Provider::get_instance()->get_db()->execute($query, ['oxobjectid' => $ox_id, 'oxcatnid' => $def_cat]);
        // #0003366: invalidate article SEO for all shops
        \Oxid_Esales\Eshop\Core\Registry::get_seo_encoder()->mark_as_expired($ox_id, null, 1, null, "oxtype='oxarticle'");
        $this->reset_content_cache();
    }
    /**
     * Method used for overloading and embed query.
     *
     * @param string $query
     *
     * @return string
     */
    protected function update_query_for_removing_article_from_category($query)
    {
        return $query;
    }
    /**
     * Method is used for overloading to do additional actions.
     *
     * @param array  $categoriesToRemove
     * @param string $oxId
     */
    protected function on_categories_removal($categories_to_remove, $ox_id)
    {
    }
    /**
     * Method is used for overloading.
     *
     * @param array $categories
     */
    protected function on_categories_add($categories)
    {
    }
    /**
     * Method is used for overloading to insert additional query condition.
     *
     * @return string
     */
    protected function form_query_to_embed_for_updating_time()
    {
        return '';
    }
    /**
     * Method is used for overloading to insert additional query condition.
     *
     * @return string
     */
    protected function form_query_to_embed_for_setting_category_as_default()
    {
        return '';
    }
}