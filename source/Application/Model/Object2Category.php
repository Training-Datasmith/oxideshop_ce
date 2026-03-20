<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Manages product assignment to category.
 */
class Object2Category extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxobject2category';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()) and sets table name.
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxobject2category');
    }
    /**
     * Returns assigned product id
     *
     * @return string
     */
    public function get_product_id()
    {
        return $this->oxobject2category__oxobjectid->value;
    }
    /**
     * Sets assigned product id
     *
     * @param string $sId assigned product id
     */
    public function set_product_id($s_id): void
    {
        $this->oxobject2category__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_id);
    }
    /**
     * Returns assigned category id
     *
     * @return string
     */
    public function get_category_id()
    {
        return $this->oxobject2category__oxcatnid->value;
    }
    /**
     * Sets assigned category id
     *
     * @param string $sId assigned category id
     */
    public function set_category_id($s_id): void
    {
        $this->oxobject2category__oxcatnid = new \Oxid_Esales\Eshop\Core\Field($s_id);
    }
}