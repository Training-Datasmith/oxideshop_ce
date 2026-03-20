<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Voucher serie manager.
 * Manages list of available Vouchers (fetches, deletes, etc.).
 */
class Voucher_Serie extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * User groups array (default null).
     *
     * @var object
     */
    protected $_o_groups;
    /**
     * @var string name of current class
     */
    protected $_s_class_name = 'oxvoucherserie';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxvoucherseries');
    }
    /**
     * Override delete function so we can delete user group and article or category relations first.
     *
     * @param string $sOxId object ID (default null)
     */
    public function delete($s_ox_id = null)
    {
        if (!$s_ox_id) {
            $s_ox_id = $this->get_id();
        }
        $this->unset_discount_relations();
        $this->unset_user_groups();
        $this->delete_voucher_list();
        return parent::delete($s_ox_id);
    }
    /**
     * Collects and returns user group list.
     *
     * @return object
     */
    public function set_user_groups()
    {
        if ($this->_o_groups === null) {
            $this->_o_groups = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $this->_o_groups->init('oxgroups');
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_view_name = $table_view_name_generator->get_view_name('oxgroups');
            $s_select = "select gr.* from {$s_view_name} as gr, oxobject2group as o2g where\n                         o2g.oxobjectid = :oxobjectid and gr.oxid = o2g.oxgroupsid ";
            $this->_o_groups->select_string($s_select, ['oxobjectid' => $this->get_id()]);
        }
        return $this->_o_groups;
    }
    /**
     * Removes user groups relations.
     */
    public function unset_user_groups(): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delete = 'delete from oxobject2group where oxobjectid = :oxobjectid';
        $o_db->execute($s_delete, ['oxobjectid' => $this->get_id()]);
    }
    /**
     * Removes product or dategory relations.
     */
    public function unset_discount_relations(): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delete = 'delete from oxobject2discount where oxobject2discount.oxdiscountid = :oxdiscountid';
        $o_db->execute($s_delete, ['oxdiscountid' => $this->get_id()]);
    }
    /**
     * Returns array of a vouchers assigned to this serie.
     *
     * @return array
     */
    public function get_voucher_list()
    {
        $o_voucher_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_List::class);
        $s_select = 'select * from oxvouchers 
            where oxvoucherserieid = :oxvoucherserieid';
        $o_voucher_list->select_string($s_select, ['oxvoucherserieid' => $this->get_id()]);
        return $o_voucher_list;
    }
    /**
     * Deletes assigned voucher list.
     */
    public function delete_voucher_list(): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delete = 'delete from oxvouchers where oxvoucherserieid = :oxvoucherserieid';
        $o_db->execute($s_delete, ['oxvoucherserieid' => $this->get_id()]);
    }
    /**
     * Returns array of vouchers counts.
     *
     * @return array
     */
    public function count_vouchers()
    {
        $a_status = [];
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_query = 'select count(*) as total from oxvouchers 
            where oxvoucherserieid = :oxvoucherserieid';
        $a_status['total'] = $o_db->get_one($s_query, ['oxvoucherserieid' => $this->get_id()]);
        $s_query = 'select count(*) as used from oxvouchers 
            where oxvoucherserieid = :oxvoucherserieid 
                and ((oxorderid is not NULL and oxorderid != "") or (oxdateused is not NULL and oxdateused != 0))';
        $a_status['used'] = $o_db->get_one($s_query, ['oxvoucherserieid' => $this->get_id()]);
        $a_status['available'] = $a_status['total'] - $a_status['used'];
        return $a_status;
    }
    /**
     * Get voucher status base on given date (if nothing was passed, current datetime will be used as a measure).
     *
     * @param string|null $sNow Date
     *
     * @return int
     */
    public function get_voucher_status_by_datetime($s_now = null)
    {
        //return content
        $i_active = 1;
        $i_inactive = 0;
        $o_utils_date = \Oxid_Esales\Eshop\Core\Registry::get_utils_date();
        //current object datetime
        $s_begin_date = $this->oxvoucherseries__oxbegindate->value;
        $s_end_date = $this->oxvoucherseries__oxenddate->value;
        //If nothing pass, use current server time
        if ($s_now == null) {
            $s_now = date('Y-m-d H:i:s', $o_utils_date->get_time());
        }
        //Check for active status.
        if ($s_begin_date == '0000-00-00 00:00:00' && $s_end_date == '0000-00-00 00:00:00' || $s_begin_date == '0000-00-00 00:00:00' && $s_now <= $s_end_date || $s_begin_date <= $s_now && $s_end_date == '0000-00-00 00:00:00' || $s_begin_date <= $s_now && $s_now <= $s_end_date) {
            //check for both start date and end date.
            return $i_active;
        }
        //If active status code was reached, return as inactive
        return $i_inactive;
    }
}