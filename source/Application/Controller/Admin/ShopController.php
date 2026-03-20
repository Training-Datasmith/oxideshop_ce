<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Shop_Id_Calculator;
/**
 * Admin shop manager.
 * Returns template, that arranges two other templates ("shop_list"
 * and "shop_main") to frame.
 * Admin Menu: Main Menu -> Core Settings.
 */
class Shop_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    public const CURRENT_TEMPLATE = 'shop';
    /**
     * Executes parent method parent::render() and returns name of template
     * file "shop".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_a_view_data['currentadminshop'] = Shop_Id_Calculator::BASE_SHOP_ID;
        return static::CURRENT_TEMPLATE;
    }
}