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
 * Admin article main payment manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Shop Settings -> Payment Methods -> Main.
 */
class Payment_Country extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        // remove itm from list
        unset($this->_a_view_data['sumtype'][2]);
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
            $o_payment->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_payment->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_payment->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_payment;
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
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_payment_country_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Payment_Country_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_payment_country_ajax->get_columns();
            return 'popups/payment_country';
        }
        return 'payment_country';
    }
    /**
     * Adds chosen user group (groups) to delivery list
     */
    public function addcountry(): void
    {
        $s_ox_id = $this->get_edit_object_id();
        $a_chosen_cntr = Registry::get_request()->get_request_escaped_parameter('allcountries');
        if (isset($s_ox_id) && $s_ox_id != '-1' && is_array($a_chosen_cntr)) {
            foreach ($a_chosen_cntr as $s_chosen_cntr) {
                $o_object2payment = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2payment->init('oxobject2payment');
                $o_object2payment->oxobject2payment__oxpaymentid = new \Oxid_Esales\Eshop\Core\Field($s_ox_id);
                $o_object2payment->oxobject2payment__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_cntr);
                $o_object2payment->oxobject2payment__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxcountry');
                $o_object2payment->save();
            }
        }
    }
    /**
     * Removes chosen user group (groups) from delivery list
     */
    public function removecountry(): void
    {
        $s_ox_id = $this->get_edit_object_id();
        $a_chosen_cntr = Registry::get_request()->get_request_escaped_parameter('countries');
        if (isset($s_ox_id) && $s_ox_id != '-1' && is_array($a_chosen_cntr)) {
            foreach ($a_chosen_cntr as $s_chosen_cntr) {
                $o_object2payment = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2payment->init('oxobject2payment');
                $o_object2payment->delete($s_chosen_cntr);
            }
        }
    }
}