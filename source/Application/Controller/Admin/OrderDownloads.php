<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin order article manager.
 * Collects order articles information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Articles.
 */
class Order_Downloads extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Active order object
     *
     * @var \OxidEsales\Eshop\Application\Model\Order
     */
    protected $_o_edit_object;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        if ($o_order = $this->get_edit_object()) {
            $this->_a_view_data['edit'] = $o_order;
        }
        return 'order_downloads';
    }
    /**
     * Returns editable order object
     *
     * @return \OxidEsales\Eshop\Application\Model\Order
     */
    public function get_edit_object()
    {
        $sox_id = $this->get_edit_object_id();
        if ($this->_o_edit_object === null && isset($sox_id) && $sox_id != '-1') {
            $this->_o_edit_object = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_File_List::class);
            $this->_o_edit_object->load_order_files($sox_id);
        }
        return $this->_o_edit_object;
    }
    /**
     * Returns editable order object
     */
    public function reset_download_link(): void
    {
        $s_order_file_id = Registry::get_request()->get_request_escaped_parameter('oxorderfileid');
        $o_order_file = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_File::class);
        if ($o_order_file->load($s_order_file_id)) {
            $o_order_file->reset();
            $o_order_file->save();
        }
    }
}