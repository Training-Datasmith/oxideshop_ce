<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Class for extending
 */
class User_Overview extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $o_user->load($sox_id);
            $this->_a_view_data['edit'] = $o_user;
        }
        return 'user_overview';
    }
}