<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin theme manager.
 * Returns template, that arranges two other templates ("theme_list"
 * and "theme_main") to frame.
 * Admin Menu: Main Menu -> Theme.
 */
class Theme_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Executes parent method parent::render() and returns name of template
     * file "theme".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        return 'theme';
    }
}