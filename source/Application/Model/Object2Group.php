<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Exception\Database_Error_Exception;
/**
 * Manages object (users, discounts, deliveries...) assignment to groups.
 */
class Object2Group extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /** @var boolean Load the relation even if from other shop */
    protected $_bl_disable_shop_check = true;
    /** @var string Current class name */
    protected $_s_class_name = 'oxobject2group';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxobject2group');
        $this->oxobject2group__oxshopid = new \Oxid_Esales\Eshop\Core\Field(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
    }
    /**
     * Extends the default save method
     * to prevent from exception if same relationship already exist.
     * The table oxobject2group has an UNIQUE index on (OXGROUPSID, OXOBJECTID, OXSHOPID)
     * which ensures that a relationship would not be duplicated.
     *
     * @throws DatabaseErrorException
     *
     * @return bool
     */
    public function save()
    {
        try {
            return parent::save();
        } catch (\Oxid_Esales\Eshop\Core\Exception\Database_Error_Exception $exception) {
            if ($exception->get_code() !== \Oxid_Esales\Eshop\Core\Database\Adapter\Doctrine\Database::DUPLICATE_KEY_ERROR_CODE) {
                throw $exception;
            }
        }
    }
}