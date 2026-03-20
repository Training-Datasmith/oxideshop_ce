<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin deliveryset payment manager.
 * There is possibility to assign set to payment method
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling Set -> Payment
 */
class Delivery_Set_Payment extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $odeliveryset = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
            $odeliveryset->set_language($this->_i_edit_lang);
            $odeliveryset->load($sox_id);
            $o_other_lang = $odeliveryset->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $odeliveryset->set_language(key($o_other_lang));
                $odeliveryset->load($sox_id);
            }
            $this->_a_view_data['edit'] = $odeliveryset;
            //Disable editing for derived articles
            if ($odeliveryset->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        $i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
        if ($i_aoc == 1) {
            $o_deliveryset_payment_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Set_Payment_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_deliveryset_payment_ajax->get_columns();
            return 'popups/deliveryset_payment';
        }
        if ($i_aoc == 2) {
            $o_deliveryset_country_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Set_Country_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_deliveryset_country_ajax->get_columns();
            return 'popups/deliveryset_country';
        }
        return 'deliveryset_payment';
    }
}