<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * CVS export manager.
 * Performs export function according to user chosen categories.
 * Admin Menu: Maine Menu -> Im/Export -> Export.
 */
class Tools_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        if (Registry::get_config()->is_demo_shop()) {
            Registry::get_utils()->show_message_and_exit('Access denied !');
        }
        parent::render();
        $o_auth_user = ox_new(User::class);
        $o_auth_user->load_admin_user();
        $this->_a_view_data['blIsMallAdmin'] = $o_auth_user->oxuser__oxrights->value == 'malladmin';
        $this->_a_view_data['showViewUpdate'] = Container_Facade::get_parameter('oxid_esales.show_update_views_button');
        return 'tools_main';
    }
}