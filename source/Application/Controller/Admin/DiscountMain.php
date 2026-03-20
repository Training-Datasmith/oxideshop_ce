<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Exception\Input_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Admin article main discount manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Discounts -> Main.
 */
class Discount_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $s_ox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($s_ox_id) && $s_ox_id != '-1') {
            // load object
            $o_discount = ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class);
            $o_discount->load_in_lang($this->_i_edit_lang, $s_ox_id);
            $o_other_lang = $o_discount->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_discount->load_in_lang(key($o_other_lang), $s_ox_id);
            }
            $this->_a_view_data['edit'] = $o_discount;
            //disabling derived items
            if ($o_discount->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // remove already created languages
            $a_lang = array_diff(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names(), $o_other_lang);
            if (count($a_lang)) {
                $this->_a_view_data['posslang'] = $a_lang;
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
        }
        if ($i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc')) {
            if ($i_aoc == '1') {
                $o_discount_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Discount_Main_Ajax::class);
                $this->_a_view_data['oxajax'] = $o_discount_main_ajax->get_columns();
                return 'popups/discount_main';
            }
            if ($i_aoc == '2') {
                // generating category tree for artikel choose select list
                $this->create_category_tree('artcattree');
                $o_discount_item_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Discount_Item_Ajax::class);
                $this->_a_view_data['oxajax'] = $o_discount_item_ajax->get_columns();
                return 'popups/discount_item';
            }
        }
        return 'discount_main';
    }
    /**
     * Returns item discount product title
     *
     * @return string
     */
    public function get_item_discount_product_title()
    {
        $s_title = false;
        $s_ox_id = $this->get_edit_object_id();
        if (isset($s_ox_id) && $s_ox_id != '-1') {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxarticles', $this->_i_edit_lang);
            // Reading from slave is ok here (see ESDEV-3804 and ESDEV-3822).
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = "select concat( {$s_view_name}.oxartnum, ' ', {$s_view_name}.oxtitle ) from oxdiscount\n                   left join {$s_view_name} on {$s_view_name}.oxid=oxdiscount.oxitmartid\n                   where oxdiscount.oxitmartid != '' and oxdiscount.oxid = :oxid";
            $s_title = $database->get_one($s_q, ['oxid' => $s_ox_id]);
        }
        return $s_title ?: ' -- ';
    }
    /**
     * Saves changed selected discount parameters.
     */
    public function save(): void
    {
        parent::save();
        $s_ox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_discount = ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class);
        if ($s_ox_id != '-1') {
            $o_discount->load($s_ox_id);
        } else {
            $a_params['oxdiscount__oxid'] = null;
        }
        // checkbox handling
        if (!isset($a_params['oxdiscount__oxactive'])) {
            $a_params['oxdiscount__oxactive'] = 0;
        }
        //disabling derived items
        if ($o_discount->is_derived()) {
            return;
        }
        //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        $o_discount->set_language(0);
        $o_discount->assign($a_params);
        $o_discount->set_language($this->_i_edit_lang);
        $o_discount = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_discount);
        try {
            $o_discount->save();
        } catch (Input_Exception $exception) {
            $new_exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $new_exception->set_message($exception->get_message());
            $this->add_tpl_param('discount_title', $a_params['oxdiscount__oxtitle']);
            if (str_contains($exception->get_message(), 'DISCOUNT_ERROR_OXSORT')) {
                $message_argument = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('DISCOUNT_MAIN_SORT', \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_tpl_language(), true);
                $new_exception->set_message_args($message_argument);
            }
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($new_exception);
            return;
        }
        // set oxid if inserted
        $this->set_edit_object_id($o_discount->get_id());
    }
    /**
     * Saves changed selected discount parameters in different language.
     */
    public function saveinnlang(): void
    {
        parent::save();
        $s_ox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class);
        if ($s_ox_id != '-1') {
            $o_attr->load($s_ox_id);
        } else {
            $a_params['oxdiscount__oxid'] = null;
        }
        // checkbox handling
        if (!isset($a_params['oxdiscount__oxactive'])) {
            $a_params['oxdiscount__oxactive'] = 0;
        }
        //disabling derived items
        if ($o_attr->is_derived()) {
            return;
        }
        //$aParams = $oAttr->ConvertNameArray2Idx( $aParams);
        $o_attr->set_language(0);
        $o_attr->assign($a_params);
        $o_attr->set_language($this->_i_edit_lang);
        $o_attr = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_attr);
        $o_attr->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_attr->get_id());
    }
    /**
     * Increment the maximum value of oxsort found in the database by certain amount and return it.
     *
     * @return int The incremented oxsort.
     */
    public function get_next_oxsort()
    {
        $shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        return ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class)->get_next_oxsort($shop_id);
    }
}