<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin actionss manager.
 * Sets list template, list object class ('oxactions') and default sorting
 * field ('oxactions.oxtitle').
 * Admin Menu: Manage Products -> Actions.
 */
class Theme_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Calls parent::render() and returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
        parent::render();
        // assign our list
        $this->_a_view_data['mylist'] = $o_theme->get_list();
        return 'theme_list';
    }
}