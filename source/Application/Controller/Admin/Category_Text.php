<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article categories text manager.
 * Category text/description manager, enables editing of text.
 * Admin Menu: Manage Products -> Categories -> Text.
 */
class Category_Text extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $i_cat_lang = Registry::get_request()->get_request_escaped_parameter('catlang');
            if (!isset($i_cat_lang)) {
                $i_cat_lang = $this->_i_edit_lang;
            }
            $this->_a_view_data['catlang'] = $i_cat_lang;
            $o_category->load_in_lang($i_cat_lang, $sox_id);
            //Disable editing for derived items
            if ($o_category->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            foreach (\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names() as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
        }
        $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 300, $o_category, 'oxcategories__oxlongdesc', 'list.css');
        return 'category_text';
    }
    /**
     * Saves category description text to DB.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $i_cat_lang = Registry::get_request()->get_request_escaped_parameter('catlang');
        $i_cat_lang = $i_cat_lang ?: 0;
        if ($sox_id != '-1') {
            $o_category->load_in_lang($i_cat_lang, $sox_id);
        } else {
            $a_params['oxcategories__oxid'] = null;
        }
        //Disable editing for derived items
        if ($o_category->is_derived()) {
            return;
        }
        $o_category->set_language(0);
        $o_category->assign($a_params);
        $o_category->set_language($i_cat_lang);
        $o_category->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_category->get_id());
    }
}