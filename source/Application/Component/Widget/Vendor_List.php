<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Vendor list widget.
 * Forms vendor list.
 */
class Vendor_List extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/footer/vendorlist';
    /**
     * Template variable getter. Returns vendorlist for search
     *
     * @return array
     */
    public function get_vendorlist()
    {
        if ($this->_a_vendorlist === null) {
            $o_vendor_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor_List::class);
            $o_vendor_tree->build_vendor_tree('vendorlist', null, \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_home_url());
            $this->_a_vendorlist = $o_vendor_tree;
        }
        return $this->_a_vendorlist;
    }
}