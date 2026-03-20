<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin general export manager.
 */
class Generic_Export_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Dynamic_Export_Base_Controller
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = 'genExport_do';
    /**
     * Export ui class name
     *
     * @var string
     */
    public $s_class_main = 'genExport_main';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'dyn_exportdefault';
    /** @inheritdoc */
    public function render()
    {
        $this->create_main_export_view();
        return parent::render();
    }
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