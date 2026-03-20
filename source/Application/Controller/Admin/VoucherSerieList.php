<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin voucherserie list manager.
 * Collects voucherserie base information (serie no., discount, valid from, etc.),
 * there is ability to filter them by deiscount, serie no. or delete them.
 * Admin Menu: Shop Settings -> Vouchers.
 */
class Voucher_Serie_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxvoucherserie';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'voucherserie_list';
    /**
     * Deletes selected Voucherserie.
     */
    public function delete_entry(): void
    {
        // first we remove vouchers
        $o_voucher_serie = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_Serie::class);
        $o_voucher_serie->load($this->get_edit_object_id());
        $o_voucher_serie->delete_voucher_list();
        parent::delete_entry();
    }
}