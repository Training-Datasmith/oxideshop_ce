<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
use Oxid_Esales\Eshop\Core\Database_Provider;
/**
 * Category list manager.
 * Collects available categories, performs some SQL queries to create category
 * list structure.
 */
class Category_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxcategory';
    /**
     * Performance option mapped to config option blDontShowEmptyCategories
     *
     * @var boolean
     */
    protected $_bl_hide_empty = false;
    /**
     * Performance option used to force full tree loading
     *
     * @var boolean
     */
    protected $_bl_force_full = false;
    /**
     * Levels count should be loaded available options 1 - only root and 2 - root and second level
     *
     * @var boolean
     */
    protected $_i_force_level = 2;
    /**
     * Active category id, used in path building, and performance optimization
     *
     * @var string
     */
    protected $_s_act_cat;
    /**
     * Active category path array
     *
     * @var array
     */
    protected $_a_path = [];
    /**
     * Category update info array
     *
     * @var array
     */
    protected $_a_update_info = [];
    /**
     * Class constructor, initiates parent constructor (parent::oxList()).
     *
     * @param string $sObjectsInListName optional parameter, the objects contained in the list, always oxCategory
     */
    public function __construct($s_objects_in_list_name = 'oxcategory')
    {
        $this->_bl_hide_empty = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blDontShowEmptyCategories');
        parent::__construct($s_objects_in_list_name);
    }
    /**
     * Set how to load tree true - for full tree
     *
     * @param boolean $blForceFull - true to load full
     */
    public function set_load_full($bl_force_full): void
    {
        $this->_bl_force_full = $bl_force_full;
    }
    /**
     * Return true if load full tree
     *
     * @return boolean
     */
    public function get_load_full()
    {
        return $this->_bl_force_full;
    }
    /**
     * Set tree level 1- load root or 2 - root and second level
     *
     * @param int $iForceLevel - level number
     */
    public function set_load_level($i_force_level): void
    {
        if ($i_force_level > 2) {
            $i_force_level = 2;
        } elseif ($i_force_level < 1) {
            $i_force_level = 0;
        }
        $this->_i_force_level = $i_force_level;
    }
    /**
     * Returns tree load level
     *
     * @return integer
     */
    public function get_load_level()
    {
        return $this->_i_force_level;
    }
    /**
     * return fields to select while loading category tree
     *
     * @param string $sTable   table name
     * @param array  $aColumns required column names (optional)
     *
     * @return string return
     */
    protected function get_sql_select_fields_for_tree($s_table, $a_columns = null)
    {
        if ($a_columns && count($a_columns)) {
            foreach ($a_columns as $key => $val) {
                $a_columns[$key] .= ' as ' . $val;
            }
            return "{$s_table}." . implode(", {$s_table}.", $a_columns);
        }
        $s_field_list = "{$s_table}.oxid as oxid, {$s_table}.oxactive as oxactive," . " {$s_table}.oxhidden as oxhidden, {$s_table}.oxparentid as oxparentid," . " {$s_table}.oxdefsort as oxdefsort, {$s_table}.oxdefsortmode as oxdefsortmode," . " {$s_table}.oxleft as oxleft, {$s_table}.oxright as oxright," . " {$s_table}.oxrootid as oxrootid, {$s_table}.oxsort as oxsort," . " {$s_table}.oxtitle as oxtitle, {$s_table}.oxdesc as oxdesc," . " {$s_table}.oxpricefrom as oxpricefrom, {$s_table}.oxpriceto as oxpriceto," . " {$s_table}.oxicon as oxicon, {$s_table}.oxextlink as oxextlink," . " {$s_table}.oxthumb as oxthumb, {$s_table}.oxpromoicon as oxpromoicon";
        return $s_field_list . $this->get_activity_fields_sql($s_table);
    }
    /**
     * Get activity related fields
     *
     * @param string $tableName
     *
     * @return string SQL snippet
     */
    protected function get_activity_fields_sql($table_name)
    {
        return ",not {$table_name}.oxactive as oxppremove";
    }
    /**
     * constructs the sql string to get the category list
     *
     * @param bool   $blReverse list loading order, true for tree, false for simple list (optional, default false)
     * @param array  $aColumns  required column names (optional)
     * @param string $sOrder    order by string (optional)
     *
     * @return string
     */
    protected function get_select_string($bl_reverse = false, $a_columns = null, $s_order = null)
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $s_field_list = $this->get_sql_select_fields_for_tree($s_view_name, $a_columns);
        //excluding long desc
        if (!$this->is_admin() && !$this->_bl_hide_empty && !$this->get_load_full()) {
            $o_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            if (!($this->_s_act_cat && $o_cat->load($this->_s_act_cat) && $o_cat->oxcategories__oxrootid->value)) {
                $o_cat = null;
                $this->_s_act_cat = null;
            }
            $s_union = $this->get_depth_sql_union($o_cat, $a_columns);
            $s_where = $this->get_depth_sql_snippet($o_cat);
        } else {
            $s_union = '';
            $s_where = '1';
        }
        if (!$s_order) {
            $s_ord_dir = $bl_reverse ? 'desc' : 'asc';
            $s_order = "oxrootid {$s_ord_dir}, oxleft {$s_ord_dir}";
        }
        return "select {$s_field_list} from {$s_view_name} where {$s_where} {$s_union} order by {$s_order}";
    }
    /**
     * constructs the sql snippet responsible for depth optimizations,
     * loads only selected category's siblings
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat selected category
     *
     * @return string
     */
    protected function get_depth_sql_snippet($o_cat)
    {
        $s_view_name = $this->get_base_object()->get_view_name();
        $depth_snippet = ' ( 0';
        // load complete tree of active category, if it exists
        if ($o_cat) {
            // select children here, siblings will be selected from union
            $depth_snippet .= " or ({$s_view_name}.oxparentid = " . Database_Provider::get_db()->quote($o_cat->oxcategories__oxid->value) . ')';
        }
        // load 1'st category level (roots)
        if ($this->get_load_level() >= 1) {
            $depth_snippet .= " or {$s_view_name}.oxparentid = 'oxrootid'";
        }
        // load 2'nd category level ()
        if ($this->get_load_level() >= 2) {
            $depth_snippet .= " or {$s_view_name}.oxrootid = {$s_view_name}.oxparentid or {$s_view_name}.oxid = {$s_view_name}.oxrootid";
        }
        return $depth_snippet . ' ) ';
    }
    /**
     * returns sql snippet for union of select category's and its upper level
     * siblings of the same root (siblings of the category, and parents and
     * grandparents etc)
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat     current category object
     * @param array                                        $aColumns required column names (optional)
     *
     * @return string
     */
    protected function get_depth_sql_union($o_cat, $a_columns = null)
    {
        if (!$o_cat) {
            return '';
        }
        $s_view_name = $this->get_base_object()->get_view_name();
        return 'UNION SELECT ' . $this->get_sql_select_fields_for_tree('maincats', $a_columns) . ' FROM oxcategories AS subcats' . " LEFT JOIN {$s_view_name} AS maincats on maincats.oxparentid = subcats.oxparentid" . ' WHERE subcats.oxrootid = ' . Database_Provider::get_db()->quote($o_cat->oxcategories__oxrootid->value) . ' AND subcats.oxleft <= ' . (int) $o_cat->oxcategories__oxleft->value . ' AND subcats.oxright >= ' . (int) $o_cat->oxcategories__oxright->value;
    }
    /**
     * Get data from db
     *
     * @return array
     */
    protected function load_from_db()
    {
        $s_sql = $this->get_select_string(false, null, 'oxparentid, oxsort, oxtitle');
        return Database_Provider::get_db()->get_all($s_sql);
    }
    /**
     * Load category list data
     */
    public function load(): void
    {
        $a_data = $this->load_from_db();
        $this->assign_array($a_data);
    }
    /**
     * Fetches reversed raw categories and does all necessary postprocessing for
     * removing invisible or forbidden categories, building oc navigation path,
     * adding content categories and building tree structure.
     *
     * @param string $sActCat Active category (default null)
     */
    public function build_tree($s_act_cat): void
    {
        start_profile('buildTree');
        $this->_s_act_cat = $s_act_cat;
        $this->load();
        // PostProcessing
        if (!$this->is_admin()) {
            // remove inactive categories
            $this->pp_remove_inactive_categories();
            // add active cat as full object
            $this->pp_load_full_category($s_act_cat);
            // builds navigation path
            $this->pp_add_path_info();
            // add content categories
            $this->pp_add_content_categories();
            // build tree structure
            $this->pp_build_tree();
        }
        stop_profile('buildTree');
    }
    /**
     * set full category object in tree
     *
     * @param string $sId category id
     */
    protected function pp_load_full_category($s_id)
    {
        if ($s_id !== null && isset($this->_a_array[$s_id])) {
            $o_new_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            if ($o_new_cat->load($s_id)) {
                // replace aArray object with fully loaded category
                $this->_a_array[$s_id] = $o_new_cat;
            }
        } else {
            $this->_s_act_cat = null;
        }
    }
    /**
     * Fetches raw categories and does postprocessing for adding depth information
     */
    public function load_list(): void
    {
        start_profile('buildCategoryList');
        $this->set_load_full(true);
        $this->select_string($this->get_select_string(false, null, 'oxparentid, oxsort, oxtitle'));
        // build tree structure
        $this->pp_build_tree();
        // PostProcessing
        // add tree depth info
        $this->pp_add_depth_information();
        stop_profile('buildCategoryList');
    }
    /**
     * setter for shopId
     *
     * @param int $sShopId ShopID
     */
    public function set_shop_id($s_shop_id): void
    {
        $this->_s_shop_id = $s_shop_id;
    }
    /**
     * Getter for active category path
     *
     * @return array
     */
    public function get_path()
    {
        return $this->_a_path;
    }
    /**
     * Getter for active category
     *
     * @return \OxidEsales\Eshop\Application\Model\Category
     */
    public function get_click_cat()
    {
        if (count($this->_a_path)) {
            return end($this->_a_path);
        }
    }
    /**
     * Getter for active root category
     *
     * @return array of oxCategory
     */
    public function get_click_root()
    {
        if (count($this->_a_path)) {
            return [reset($this->_a_path)];
        }
    }
    /**
     * Postprocess to remove inactive/forbidden categories and subcategories
     */
    protected function pp_remove_inactive_categories()
    {
        // Collect all items which must be remove
        $a_remove_list = [];
        foreach ($this->_a_array as $s_id => $o_cat) {
            if ($o_cat->oxcategories__oxppremove->value) {
                if (!isset($a_remove_list[$o_cat->oxcategories__oxrootid->value])) {
                    $a_remove_list[$o_cat->oxcategories__oxrootid->value] = [];
                }
                $a_remove_list[$o_cat->oxcategories__oxrootid->value][$o_cat->oxcategories__oxleft->value] = $o_cat->oxcategories__oxright->value;
                unset($this->_a_array[$s_id]);
            } else {
                unset($o_cat->oxcategories__oxppremove);
            }
        }
        // Remove collected item's children from the list too (in the ranges).
        foreach ($this->_a_array as $s_id => $o_cat) {
            if (isset($a_remove_list[$o_cat->oxcategories__oxrootid->value]) && is_array($a_remove_list[$o_cat->oxcategories__oxrootid->value])) {
                foreach ($a_remove_list[$o_cat->oxcategories__oxrootid->value] as $i_left => $i_right) {
                    if ($i_left <= $o_cat->oxcategories__oxleft->value && $i_right >= $o_cat->oxcategories__oxleft->value) {
                        // this is a child in an inactive range (parent already gone)
                        unset($this->_a_array[$s_id]);
                        break 1;
                    }
                }
            }
        }
    }
    /**
     * Category list postprocessing routine, responsible for generation of active category path
     */
    protected function pp_add_path_info()
    {
        if (is_null($this->_s_act_cat)) {
            return;
        }
        $a_path = [];
        $s_current_cat = $this->_s_act_cat;
        while ($s_current_cat != 'oxrootid' && isset($this[$s_current_cat])) {
            $o_cat = $this[$s_current_cat];
            $o_cat->set_expanded(true);
            $a_path[$s_current_cat] = $o_cat;
            $s_current_cat = $o_cat->oxcategories__oxparentid->value;
        }
        $this->_a_path = array_reverse($a_path);
    }
    /**
     * Category list postprocessing routine, responsible adding of content categories
     */
    protected function pp_add_content_categories()
    {
        // load content pages for adding them into menu tree
        $o_content_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Content_List::class);
        $o_content_list->load_cat_menues();
        foreach ($o_content_list as $s_cat_id => $a_content) {
            if (array_key_exists($s_cat_id, $this->_a_array)) {
                $this[$s_cat_id]->set_content_cats($a_content);
            }
        }
    }
    /**
     * Category list postprocessing routine, responsible building an sorting of hierarchical category tree
     */
    protected function pp_build_tree()
    {
        $a_tree = [];
        foreach ($this->_a_array as $o_cat) {
            $s_parent_id = $o_cat->oxcategories__oxparentid->value;
            if ($s_parent_id != 'oxrootid') {
                if (isset($this->_a_array[$s_parent_id])) {
                    $this->_a_array[$s_parent_id]->set_sub_cat($o_cat, $o_cat->get_id());
                }
            } else {
                $a_tree[$o_cat->get_id()] = $o_cat;
            }
        }
        $this->assign($a_tree);
    }
    /**
     * Category list postprocessing routine, responsible for making flat category tree and adding depth information.
     * Requires reversed category list!
     */
    protected function pp_add_depth_information()
    {
        $a_tree = [];
        foreach ($this->_a_array as $o_cat) {
            $a_tree[$o_cat->get_id()] = $o_cat;
            $a_sub_cats = $o_cat->get_sub_cats();
            if (count($a_sub_cats) > 0) {
                foreach ($a_sub_cats as $o_sub_cat) {
                    $a_tree = $this->add_depth_info($a_tree, $o_sub_cat);
                }
            }
        }
        $this->assign($a_tree);
    }
    /**
     * Recursive function to add depth information
     *
     * @param array  $aTree  new category tree
     * @param object $oCat   category object
     * @param string $sDepth string to show category depth
     *
     * @return array $aTree
     */
    protected function add_depth_info($a_tree, $o_cat, $s_depth = '')
    {
        $s_depth .= '-';
        $o_cat->oxcategories__oxtitle->set_value($s_depth . ' ' . $o_cat->oxcategories__oxtitle->value);
        $a_tree[$o_cat->get_id()] = $o_cat;
        $a_sub_cats = $o_cat->get_sub_cats();
        if (count($a_sub_cats) > 0) {
            foreach ($a_sub_cats as $o_sub_cat) {
                $a_tree = $this->add_depth_info($a_tree, $o_sub_cat, $s_depth);
            }
        }
        return $a_tree;
    }
    /**
     * Rebuilds nested sets information by updating oxLeft and oxRight category attributes, from oxParentId
     *
     * @param bool   $blVerbose Set to true for output the update status for user,
     * @param string $sShopID   the shop id
     */
    public function update_category_tree($bl_verbose = true, $s_shop_id = null): void
    {
        // Only called from admin and admin mode reads from master (see ESDEV-3804 and ESDEV-3822).
        $database = Database_Provider::get_db();
        $database->start_transaction();
        try {
            $s_where = $this->get_initial_update_category_tree_condition($bl_verbose);
            $database->execute("update oxcategories set oxleft = 0, oxright = 0 where {$s_where}");
            $database->execute("update oxcategories set oxleft = 1, oxright = 2 where oxparentid = 'oxrootid' and {$s_where}");
            // Get all root categories
            $categories = $database->select("select oxid, oxtitle from oxcategories where oxparentid = 'oxrootid'" . " and {$s_where} order by oxsort");
            if ($categories != false && $categories->count() > 0) {
                while (!$categories->EOF) {
                    $this->_a_update_info[] = '<b>Processing : ' . $categories->fields['oxtitle'] . '</b>(' . $categories->fields['oxid'] . ')<br>';
                    if ($bl_verbose) {
                        echo next($this->_a_update_info);
                    }
                    $ox_root_id = $categories->fields['oxid'];
                    $this->update_nodes($ox_root_id, true, $ox_root_id);
                    $categories->fetch_row();
                }
            }
            $database->commit_transaction();
        } catch (Exception $exception) {
            $database->rollback_transaction();
            throw $exception;
        }
        $this->on_update_category_tree();
    }
    /**
     * Triggering in the end of updateCategoryTree method
     */
    protected function on_update_category_tree()
    {
    }
    /**
     * Get Initial updateCategoryTree sql condition
     *
     * @param bool $blVerbose
     *
     * @return string
     */
    protected function get_initial_update_category_tree_condition($bl_verbose = false)
    {
        return '1';
    }
    /**
     * Returns update log data array
     *
     * @return array
     */
    public function get_update_info()
    {
        return $this->_a_update_info;
    }
    /**
     * Recursively updates root nodes, this method is used (only) in updateCategoryTree()
     *
     * @param string $oxRootId rootid of tree
     * @param bool   $isRoot   is the current node root?
     * @param string $thisRoot the id of the root
     */
    protected function update_nodes($ox_root_id, $is_root, $this_root)
    {
        // Called from inside a transaction so master is picked automatically (see ESDEV-3804 and ESDEV-3822).
        $database = Database_Provider::get_db();
        if ($is_root) {
            $this_root = $ox_root_id;
        }
        $database->execute('update oxcategories set oxrootid = :oxrootid where oxparentid = :oxparentid', ['oxrootid' => $this_root, 'oxparentid' => $ox_root_id]);
        $child_categories = $database->select('select oxid, oxparentid from oxcategories where oxparentid = :oxparentid order by oxsort', ['oxparentid' => $ox_root_id]);
        if ($child_categories != false && $child_categories->count() > 0) {
            while (!$child_categories->EOF) {
                $parent_id = $child_categories->fields['oxparentid'];
                $act_oxid = $child_categories->fields['oxid'];
                $parent_category = $database->select('select oxrootid, oxright from oxcategories where oxid = :oxid', ['oxid' => $parent_id]);
                if ($parent_category != false && $parent_category->count() > 0) {
                    while (!$parent_category->EOF) {
                        $parent_ox_root_id = $parent_category->fields['oxrootid'];
                        $parent_right = (int) $parent_category->fields['oxright'];
                        $parent_category->fetch_row();
                    }
                }
                $query = 'update oxcategories set oxleft = oxleft + 2 where oxrootid = :oxrootid and' . ' oxleft > :parentRight and oxright >= :parentRight and oxid != :oxid';
                $database->execute($query, ['oxrootid' => $parent_ox_root_id, 'parentRight' => $parent_right, 'oxid' => $act_oxid]);
                $query = 'update oxcategories set oxright = oxright + 2 where oxrootid = :oxrootid and' . ' oxright >= :oxright and oxid != :oxid';
                $database->execute($query, ['oxrootid' => $parent_ox_root_id, 'oxright' => $parent_right, 'oxid' => $act_oxid]);
                $query = 'update oxcategories set oxleft = :parentRight, oxright = (:parentRight + 1)' . ' where oxid = :oxid';
                $database->execute($query, ['parentRight' => $parent_right, 'oxid' => $act_oxid]);
                $this->update_nodes($act_oxid, false, $this_root);
                $child_categories->fetch_row();
            }
        }
    }
    /**
     * Extra getter to guarantee compatibility with templates
     *
     * @param string $sName variable name
     *
     * @return string
     */
    public function __get($s_name)
    {
        return match ($s_name) {
            'aPath', 'aFullPath' => $this->get_path(),
            default => parent::__get($s_name),
        };
    }
}