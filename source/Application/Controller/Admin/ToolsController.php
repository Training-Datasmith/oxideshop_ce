<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin systeminfo manager.
 * Returns template, that arranges two other templates ("delivery_list"
 * and "delivery_main") to frame.
 */
class Tools_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Executes parent method parent::render(), prints shop and
     * PHP configuration information.
     *
     * @return string
     */
    public function render()
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->is_demo_shop()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils()->show_message_and_exit('Access denied !');
        }
        parent::render();
        return 'tools';
    }
}