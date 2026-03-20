<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin voucherserie groups manager.
 * Collects and manages information about user groups, added to one or another
 * serie of vouchers.
 * Admin Menu: Shop Settings -> Vouchers -> Groups.
 */
class Voucher_Serie_Groups extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_voucher_serie = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_Serie::class);
            $o_voucher_serie->load($sox_id);
            $o_voucher_serie->set_user_groups();
            $this->_a_view_data['edit'] = $o_voucher_serie;
            //Disable editing for derived items
            if ($o_voucher_serie->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_voucher_serie_groups_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Voucher_Serie_Groups_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_voucher_serie_groups_ajax->get_columns();
            return 'popups/voucherserie_groups';
        }
        return 'voucherserie_groups';
    }
}