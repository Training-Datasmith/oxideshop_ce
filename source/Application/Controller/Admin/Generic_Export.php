<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin dyn General export manager.
 */
class Generic_Export extends \Oxid_Esales\Eshop\Application\Controller\Admin\Dynamic_Export_Base_Controller
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = 'genexport_do';
    /**
     * Export ui class name
     *
     * @var string
     */
    public $s_class_main = 'genexport_main';
    /**
     * Current view ID getter helps to identify navigation position.
     * Bypassing dynexportbase::getViewId
     *
     * @return string
     */
    public function get_view_id()
    {
        return \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller::get_view_id();
    }
}