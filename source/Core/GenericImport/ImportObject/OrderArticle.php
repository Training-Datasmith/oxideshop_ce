<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Order Articles.
 */
class Order_Article extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxorderarticles';
    /** @var string Shop object name. */
    protected $shop_object_name = 'oxorderarticle';
    /**
     * issued before saving an object. can modify aData for saving
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $shopObject        oxBase child for object
     * @param array                                  $data              Data for object
     * @param bool                                   $allowCustomShopId If true then AllowCustomShopId
     *
     * @return array
     */
    protected function pre_assign_object($shop_object, $data, $allow_custom_shop_id)
    {
        $data = parent::pre_assign_object($shop_object, $data, $allow_custom_shop_id);
        // check if data is not serialized
        $pers_param_values = @unserialize($data['OXPERSPARAM']);
        if (!is_array($pers_param_values)) {
            // data is a string with | separation, prepare for oxid
            $pers_param_values = explode('|', (string) $data['OXPERSPARAM']);
            $data['OXPERSPARAM'] = serialize($pers_param_values);
        }
        if (array_key_exists('OXORDERSHOPID', $data)) {
            $data['OXORDERSHOPID'] = $this->get_order_shop_id($data['OXORDERSHOPID']);
        }
        return $data;
    }
    /**
     * Returns formed order shop id, which should be set to data array.
     *
     * @param string $currentShopId
     *
     * @return string
     */
    protected function get_order_shop_id($current_shop_id)
    {
        return 1;
    }
}