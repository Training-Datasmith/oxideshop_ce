<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Remark manager.
 */
class Remark extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxremark';
    /**
     * Skip update fields
     *
     * @var array
     */
    protected $_a_skip_save_fields = ['oxtimestamp'];
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxremark');
    }
    /**
     * Loads object information from DB. Returns true on success.
     *
     * @param string $oxID ID of object to load
     *
     * @return bool
     */
    public function load($ox_id)
    {
        if ($bl_ret = parent::load($ox_id)) {
            // convert date's to international format
            $this->assign(['oxcreate' => \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->format_db_date($this->oxremark__oxcreate->value)]);
        }
        return $bl_ret;
    }
    /**
     * Inserts object data fields in DB. Returns true on success.
     *
     * @return bool
     */
    protected function insert()
    {
        // set oxcreate
        $s_now = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        $this->oxremark__oxcreate = new \Oxid_Esales\Eshop\Core\Field($s_now, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxremark__oxheader = new \Oxid_Esales\Eshop\Core\Field($s_now, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        return parent::insert();
    }
}