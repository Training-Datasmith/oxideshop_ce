<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Group manager.
 * Base class for user groups. Does nothing special yet.
 */
class Groups extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Name of current class
     *
     * @var string
     */
    protected $_s_class_name = 'oxgroups';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxgroups');
    }
    /**
     * Deletes user group from database. Returns true/false, according to deleting status.
     *
     * @param string $sOXID Object ID (default null)
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (!$s_oxid) {
            return false;
        }
        $parent_result = parent::delete($s_oxid);
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // deleting related data records
        $s_delete = 'delete from oxobject2group where oxobject2group.oxgroupsid = :oxid';
        $o_db->execute($s_delete, ['oxid' => $s_oxid]);
        $s_delete = 'delete from oxobject2delivery where oxobject2delivery.oxobjectid = :oxid';
        $o_db->execute($s_delete, ['oxid' => $s_oxid]);
        $s_delete = 'delete from oxobject2discount where oxobject2discount.oxobjectid = :oxid';
        $o_db->execute($s_delete, ['oxid' => $s_oxid]);
        $s_delete = 'delete from oxobject2payment where oxobject2payment.oxobjectid = :oxid';
        $o_db->execute($s_delete, ['oxid' => $s_oxid]);
        return $parent_result;
    }
}