<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin user history settings manager.
 * Collects user history settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> History.
 */
class User_Remark extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        $s_remox_id = Registry::get_request()->get_request_escaped_parameter('rem_oxid');
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $o_user->load($sox_id);
            $this->_a_view_data['edit'] = $o_user;
            // all remark
            $o_rems = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_rems->init('oxremark');
            $s_select = 'select * from oxremark where oxparentid = :oxparentid order by oxcreate desc';
            $o_rems->select_string($s_select, ['oxparentid' => $o_user->get_id()]);
            foreach ($o_rems as $key => $val) {
                if ($val->oxremark__oxid->value == $s_remox_id) {
                    $val->selected = 1;
                    $o_rems[$key] = $val;
                    break;
                }
            }
            $this->_a_view_data['allremark'] = $o_rems;
            if (isset($s_remox_id)) {
                $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
                $o_remark->load($s_remox_id);
                $this->_a_view_data['remarktext'] = $o_remark->oxremark__oxtext->value;
                $this->_a_view_data['remarkheader'] = $o_remark->oxremark__oxheader->value;
            }
        }
        return 'user_remark';
    }
    /**
     * Saves user history text changes.
     */
    public function save(): void
    {
        parent::save();
        $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
        // try to load if exists
        $o_remark->load(Registry::get_request()->get_request_escaped_parameter('rem_oxid'));
        $o_remark->oxremark__oxtext = new \Oxid_Esales\Eshop\Core\Field(Registry::get_request()->get_request_escaped_parameter('remarktext'));
        $o_remark->oxremark__oxheader = new \Oxid_Esales\Eshop\Core\Field(Registry::get_request()->get_request_escaped_parameter('remarkheader'));
        $o_remark->oxremark__oxparentid = new \Oxid_Esales\Eshop\Core\Field($this->get_edit_object_id());
        $o_remark->oxremark__oxtype = new \Oxid_Esales\Eshop\Core\Field('r');
        $o_remark->save();
    }
    /**
     * Deletes user actions history record.
     */
    public function delete(): void
    {
        $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
        $o_remark->delete(Registry::get_request()->get_request_escaped_parameter('rem_oxid'));
    }
}