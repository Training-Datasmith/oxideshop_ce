<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin user extended settings manager.
 * Collects user extended settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> Extended.
 */
class User_Extend extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Executes parent method parent::render(), creates oxuser object and
     * returns name of template file "user_extend".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $o_user->load($sox_id);
            //show country in active language
            $o_country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
            $o_country->load_in_lang(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_object_tpl_language(), $o_user->oxuser__oxcountryid->value);
            $o_user->oxuser__oxcountry = new \Oxid_Esales\Eshop\Core\Field($o_country->oxcountry__oxtitle->value);
            $this->_a_view_data['edit'] = $o_user;
        }
        if (!$this->allow_admin_edit($sox_id)) {
            $this->_a_view_data['readonly'] = true;
        }
        return 'user_extend';
    }
    /**
     * Saves user extended information.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        if (!$this->allow_admin_edit($sox_id)) {
            return false;
        }
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        if ($sox_id != '-1') {
            $o_user->load($sox_id);
        } else {
            $a_params['oxuser__oxid'] = null;
        }
        // checkbox handling
        $a_params['oxuser__oxactive'] = $o_user->oxuser__oxactive->value;
        $bl_news_params = Registry::get_request()->get_request_escaped_parameter('editnews');
        if (isset($bl_news_params)) {
            $o_news_subscription = $o_user->get_news_subscription();
            $o_news_subscription->set_opt_in_status((int) $bl_news_params);
            $o_news_subscription->set_opt_in_email_status((int) Registry::get_request()->get_request_escaped_parameter('emailfailed'));
        }
        $o_user->assign($a_params);
        $o_user->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_user->get_id());
    }
}