<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Model\List_Model;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Admin article list manager.
 * Collects base article information (according to filtering rules), performs sorting,
 * deletion of articles, etc.
 * Admin Menu: Manage Products -> Articles.
 */
class Article_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxarticle';
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxarticlelist';
    /**
     * Collects articles base data and passes them according to filtering rules,
     * returns name of template file "article_list".
     *
     * @return string
     */
    public function render()
    {
        $list_type = '';
        $active_item_id = '';
        $requested_category = Registry::get_request()->get_request_escaped_parameter('art_category');
        $requested_search_field = Registry::get_request()->get_request_escaped_parameter('pwrsearchfld');
        $search_field = $requested_search_field ? strtolower((string) $requested_search_field) : 'oxtitle';
        $product_list = $this->get_item_list();
        if ($product_list && $product_list->count()) {
            $this->convert_search_field_value_for_products_in_list($product_list, $search_field);
            $this->set_additional_search_field_for_products_in_list($product_list, $search_field);
            $this->set_is_active_field_for_products_in_list($product_list);
        }
        parent::render();
        $this->_a_view_data['pwrsearchfields'] = $product_list->count() || $product_list->get_base_object() ? $this->get_search_fields() : null;
        $this->_a_view_data['pwrsearchfld'] = strtoupper($search_field);
        $list_filter = $this->get_list_filter();
        if (isset($list_filter['oxarticles'][$search_field])) {
            $this->_a_view_data['pwrsearchinput'] = $list_filter['oxarticles'][$search_field];
        }
        if ($requested_category && str_contains((string) $requested_category, '@@')) {
            [$list_type, $active_item_id] = explode('@@', (string) $requested_category);
        }
        $this->_a_view_data['art_category'] = $requested_category;
        // parent categorie tree
        $this->_a_view_data['cattree'] = $this->get_category_list($list_type, $active_item_id);
        // manufacturer list
        $this->_a_view_data['mnftree'] = $this->get_manufacturerlist($list_type, $active_item_id);
        // vendor list
        $this->_a_view_data['vndtree'] = $this->get_vendor_list($list_type, $active_item_id);
        return 'article_list';
    }
    /**
     * Returns array of fields which may be used for product data search
     *
     * @return array
     */
    public function get_search_fields()
    {
        $a_skip_fields = ['oxblfixedprice', 'oxvarselect', 'oxamitemid', 'oxamtaskid', 'oxpixiexport', 'oxpixiexported'];
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        return array_diff($o_article->get_field_names(), $a_skip_fields);
    }
    /**
     * Load category list, mark active category;
     *
     * @param string $sType  active list type
     * @param string $sValue active list item id
     *
     * @return \OxidEsales\Eshop\Application\Model\CategoryList
     */
    public function get_category_list($s_type, $s_value)
    {
        /** @var \OxidEsales\Eshop\Application\Model\CategoryList $oCatTree parent category tree */
        $o_cat_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
        $o_cat_tree->load_list();
        if ($s_type === 'cat') {
            foreach ($o_cat_tree as $o_category) {
                if ($o_category->oxcategories__oxid->value == $s_value) {
                    $o_category->selected = 1;
                    break;
                }
            }
        }
        return $o_cat_tree;
    }
    /**
     * Load manufacturer list, mark active category;
     *
     * @param string $sType  active list type
     * @param string $sValue active list item id
     *
     * @return \OxidEsales\Eshop\Application\Model\ManufacturerList
     */
    public function get_manufacturer_list($s_type, $s_value)
    {
        $o_mnf_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer_List::class);
        $o_mnf_tree->load_manufacturer_list();
        if ($s_type === 'mnf') {
            foreach ($o_mnf_tree as $o_manufacturer) {
                if ($o_manufacturer->oxmanufacturers__oxid->value == $s_value) {
                    $o_manufacturer->selected = 1;
                    break;
                }
            }
        }
        return $o_mnf_tree;
    }
    /**
     * Load vendor list, mark active category;
     *
     * @param string $sType  active list type
     * @param string $sValue active list item id
     *
     * @return \OxidEsales\Eshop\Application\Model\VendorList
     */
    public function get_vendor_list($s_type, $s_value)
    {
        $o_vnd_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor_List::class);
        $o_vnd_tree->load_vendor_list();
        if ($s_type === 'vnd') {
            foreach ($o_vnd_tree as $o_vendor) {
                if ($o_vendor->oxvendor__oxid->value == $s_value) {
                    $o_vendor->selected = 1;
                    break;
                }
            }
        }
        return $o_vnd_tree;
    }
    /**
     * Builds and returns SQL query string.
     *
     * @param object $oListObject list main object
     *
     * @return string
     */
    protected function build_select_string($o_list_object = null)
    {
        $s_q = parent::build_select_string($o_list_object);
        if ($s_q) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_table = $table_view_name_generator->get_view_name('oxarticles');
            $s_q .= " and {$s_table}.oxparentid = '' ";
            $s_type = false;
            $s_art_cat = Registry::get_request()->get_request_escaped_parameter('art_category');
            if ($s_art_cat && str_contains((string) $s_art_cat, '@@')) {
                [$s_type, $s_value] = explode('@@', (string) $s_art_cat);
            }
            switch ($s_type) {
                // add category
                case 'cat':
                    $o_str = Str::get_str();
                    $s_view_name = $table_view_name_generator->get_view_name('oxobject2category');
                    $s_insert = "from {$s_table} left join {$s_view_name} on {$s_table}.oxid = {$s_view_name}.oxobjectid " . "where {$s_view_name}.oxcatnid = " . Database_Provider::get_db()->quote($s_value) . ' and ';
                    $s_q = $o_str->preg_replace("/from\\s+{$s_table}\\s+where/i", $s_insert, $s_q);
                    break;
                // add category
                case 'mnf':
                    $s_q .= " and {$s_table}.oxmanufacturerid = " . Database_Provider::get_db()->quote($s_value);
                    break;
                // add vendor
                case 'vnd':
                    $s_q .= " and {$s_table}.oxvendorid = " . Database_Provider::get_db()->quote($s_value);
                    break;
            }
        }
        return $s_q;
    }
    /**
     * Builds and returns array of SQL WHERE conditions.
     *
     * @return array
     */
    public function build_where()
    {
        // we override this to select only parent articles
        $this->_a_where = parent::build_where();
        // adding folder check
        $s_folder = Registry::get_request()->get_request_escaped_parameter('folder');
        if ($s_folder && $s_folder != '-1') {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $this->_a_where[$table_view_name_generator->get_view_name('oxarticles') . '.oxfolder'] = $s_folder;
        }
        return $this->_a_where;
    }
    /**
     * Deletes entry from the database
     */
    public function delete_entry(): void
    {
        $s_ox_id = $this->get_edit_object_id();
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($s_ox_id && $o_article->load($s_ox_id)) {
            parent::delete_entry();
        }
    }
    private function convert_search_field_value_for_products_in_list(List_Model $product_list, string $search_field): void
    {
        $field_name = "oxarticles__{$search_field}";
        if (Registry::get_config()->get_config_param('blSkipFormatConversion') || !$this->is_date_field($this->get_field_type_for_current_product($product_list, $field_name))) {
            return;
        }
        foreach ($product_list as $key => $product) {
            $this->convert_value_to_database_timestamp($product->{$field_name});
            $product_list[$key] = $product;
        }
    }
    private function get_field_type_for_current_product(List_Model $product_list, string $field_name): string
    {
        $current_product = $product_list->offsetGet($product_list->key());
        return $current_product->{$field_name}->fldtype;
    }
    private function is_date_field(string $field_type): bool
    {
        return in_array($field_type, ['date', 'datetime', 'timestamp']);
    }
    private function set_additional_search_field_for_products_in_list(List_Model $product_list, string $search_field): void
    {
        $field_name = "oxarticles__{$search_field}";
        foreach ($product_list as $key => $product) {
            $product->pwrsearchval = $product->{$field_name}->value;
            $product_list[$key] = $product;
        }
    }
    private function set_is_active_field_for_products_in_list(List_Model $product_list): void
    {
        $use_time_check = Registry::get_config()->get_config_param('blUseTimeCheck');
        foreach ($product_list as $key => $product) {
            $product->show_active_check_in_admin_panel = $product->is_product_always_active();
            if ($use_time_check) {
                $product->has_active_time_range = $product->has_product_valid_time_range();
                $product->is_active_now = $product->has_active_time_range();
            }
            $product_list[$key] = $product;
        }
    }
    private function convert_value_to_database_timestamp(Field $field): void
    {
        if ($field->fldtype === 'datetime') {
            Registry::get_utils_date()->convert_db_date_time($field);
        } elseif ($field->fldtype === 'timestamp') {
            Registry::get_utils_date()->convert_db_timestamp($field);
        } elseif ($field->fldtype === 'date') {
            Registry::get_utils_date()->convert_db_date($field);
        }
    }
}