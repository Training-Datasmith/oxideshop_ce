<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Controller\Text_Editor_Handler;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Shop_Version;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Symfony\Component\Filesystem\Path;
class Admin_Details_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $s_return = parent::render();
        // generate help link
        Registry::get_config();
        $s_dir = Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'documentation', 'admin');
        if (is_dir($s_dir)) {
            $s_dir = Container_Facade::get_parameter('oxid_esales.shop_url') . 'documentation/admin';
        } else {
            $language_id = $this->get_documentation_language_id();
            $shop_version = ox_new(Shop_Version::class)->get_version();
            $s_dir = "http://docu.oxid-esales.com/PE/{$shop_version}/" . $language_id . '/admin';
        }
        $this->_a_view_data['sHelpURL'] = $s_dir;
        return $s_return;
    }
    /**
     * Get language id for documentation by current language id.
     *
     * @return int
     */
    protected function get_documentation_language_id()
    {
        $language = Registry::get_lang();
        $language_abbr = $language->get_language_abbr($language->get_tpl_language());
        return $language_abbr === 'de' ? 0 : 1;
    }
    /**
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $object
     * @param string $fieldName
     *
     * @return string
     * @deprecated method will be removed in v7.0
     */
    protected function get_edit_value($object, $field_name)
    {
        if (!$object || !$field_name || !isset($object->{$field_name})) {
            return '';
        }
        if (!$object->{$field_name} instanceof Field) {
            $object->{$field_name} = new Field($object->{$field_name}->value, Field::T_RAW);
        }
        return $object->{$field_name}->get_raw_value();
    }
    /**
     * Generates Text editor html code.
     *
     * @param int                                    $width      editor width
     * @param int                                    $height     editor height
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $object     object passed to editor
     * @param string                                 $field      object field which content is passed to editor
     * @param string                                 $stylesheet stylesheet to use in editor
     *
     * @return string Editor output
     */
    protected function generate_text_editor($width, $height, $object, $field, $stylesheet = null)
    {
        $object_value = $this->get_edit_value($object, $field);
        $text_editor_handler = $this->create_text_editor_handler();
        $this->configure_text_editor_handler($text_editor_handler, $object, $field, $stylesheet);
        return $text_editor_handler->render_text_editor($width, $height, $object_value, $field);
    }
    /**
     * Resets number of articles in current shop categories.
     */
    public function reset_nr_of_cat_articles(): void
    {
        // resetting categories article count cache
        $this->reset_content_cache();
    }
    /**
     * Resets number of articles in current shop vendors.
     */
    public function reset_nr_of_vendor_articles(): void
    {
        // resetting vendors cache
        $this->reset_content_cache();
    }
    /**
     * Resets number of articles in current shop manufacturers.
     */
    public function reset_nr_of_manufacturer_articles(): void
    {
        // resetting manufacturers cache
        $this->reset_content_cache();
    }
    /**
     * Function creates category tree for select list used in "Category main", "Article extend" etc.
     *
     * @param string $sTplVarName     name of template variable where is stored category tree
     * @param string $sEditCatId      ID of category witch we are editing
     * @param bool   $blForceNonCache Set to true to disable caching
     * @param int    $iTreeShopId     tree shop id
     *
     * @return string
     */
    protected function create_category_tree($s_tpl_var_name, $s_edit_cat_id = '', $bl_force_non_cache = false, $i_tree_shop_id = null)
    {
        // caching category tree, to load it once, not many times
        if (!isset($this->o_cat_tree) || $bl_force_non_cache) {
            $this->o_cat_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
            $this->o_cat_tree->set_shop_id($i_tree_shop_id);
            // setting language
            $o_base = $this->o_cat_tree->get_base_object();
            $o_base->set_language($this->_i_edit_lang);
            $this->o_cat_tree->load_list();
        }
        // copying tree
        $o_cat_tree = $this->o_cat_tree;
        //removing current category
        if ($s_edit_cat_id && isset($o_cat_tree[$s_edit_cat_id])) {
            unset($o_cat_tree[$s_edit_cat_id]);
        }
        // add first fake category for not assigned articles
        $o_root = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $o_root->oxcategories__oxtitle = new Field('--');
        $o_cat_tree->assign(array_merge(['' => $o_root], $o_cat_tree->get_array()));
        // passing to view
        $this->_a_view_data[$s_tpl_var_name] = $o_cat_tree;
        return $o_cat_tree;
    }
    /**
     * Function creates category tree for select list used in "Category main", "Article extend" etc.
     * Returns ID of selected category if available.
     *
     * @param string $sTplVarName     name of template variable where is stored category tree
     * @param string $sSelectedCatId  ID of category witch was selected in select list
     * @param string $sEditCatId      ID of category witch we are editing
     * @param bool   $blForceNonCache Set to true to disable caching
     * @param int    $iTreeShopId     tree shop id
     *
     * @return string
     */
    protected function get_category_tree($s_tpl_var_name, $s_selected_cat_id, $s_edit_cat_id = '', $bl_force_non_cache = false, $i_tree_shop_id = null)
    {
        $o_cat_tree = $this->create_category_tree($s_tpl_var_name, $s_edit_cat_id, $bl_force_non_cache, $i_tree_shop_id);
        // mark selected
        if ($s_selected_cat_id) {
            // fixed parent category in select list
            foreach ($o_cat_tree as $o_category) {
                if (strcmp((string) $o_category->get_id(), $s_selected_cat_id) == 0) {
                    $o_category->selected = 1;
                    break;
                }
            }
        } else {
            // no category selected - opening first available
            $o_cat_tree->rewind();
            if ($o_cat = $o_cat_tree->current()) {
                $o_cat->selected = 1;
                $s_selected_cat_id = $o_cat->get_id();
            }
        }
        // passing to view
        $this->_a_view_data[$s_tpl_var_name] = $o_cat_tree;
        return $s_selected_cat_id;
    }
    /**
     * Updates object folder parameters.
     */
    public function change_folder(): void
    {
        $s_folder = Registry::get_request()->get_request_escaped_parameter('setfolder');
        $s_folder_class = Registry::get_request()->get_request_escaped_parameter('folderclass');
        if ($s_folder_class == 'oxcontent' && $s_folder == 'CMSFOLDER_NONE') {
            $s_folder = '';
        }
        $o_object = ox_new($s_folder_class);
        if ($o_object->load($this->get_edit_object_id())) {
            $o_object->{$o_object->get_core_table_name() . '__oxfolder'} = new Field($s_folder);
            $o_object->save();
        }
    }
    /**
     * Sets-up navigation parameters.
     *
     * @param string $sNode active view id
     */
    protected function setup_navigation($s_node)
    {
        // navigation according to class
        if ($s_node) {
            $my_admin_navig = $this->get_navigation();
            // default tab
            $this->_a_view_data['default_edit'] = $my_admin_navig->get_active_tab($s_node, $this->_i_def_edit);
            // buttons
            $this->_a_view_data['bottom_buttons'] = $my_admin_navig->get_btn($s_node);
        }
    }
    /**
     * Resets count of vendor/manufacturer category items.
     *
     * @param array $aIds to reset type => id
     */
    protected function reset_counts($a_ids)
    {
        foreach ($a_ids as $s_type => $a_reset_info) {
            foreach ($a_reset_info as $s_reset_id => $i_pos) {
                switch ($s_type) {
                    case 'vendor':
                        $this->reset_counter('vendorArticle', $s_reset_id);
                        break;
                    case 'manufacturer':
                        $this->reset_counter('manufacturerArticle', $s_reset_id);
                        break;
                }
            }
        }
    }
    /**
     * Create the handler for the text editor.
     *
     * Note: the parameters editedObject and field are not used here but in the enterprise edition.
     *
     * @param mixed             $editedObject      The object we want to edit, either type of
     *                                             \OxidEsales\Eshop\Core\BaseModel if you want to persist or anything
     *                                             else
     * @param string            $field             The input field we want to edit
     * @param string            $stylesheet        The name of the CSS file
     */
    protected function configure_text_editor_handler(Text_Editor_Handler $text_editor_handler, $edited_object, $field, $stylesheet)
    {
        $text_editor_handler->set_style_sheet($stylesheet);
    }
    /**
     * Create the handler for the text editor.
     *
     * @return TextEditorHandler The text editor handler
     */
    protected function create_text_editor_handler()
    {
        return ox_new(Text_Editor_Handler::class);
    }
}