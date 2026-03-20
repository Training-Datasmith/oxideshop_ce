<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Categories.
 */
class Category extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxcategories';
    /** @var string Shop object name. */
    protected $shop_object_name = 'oxcategory';
    /**
     * Issued before saving an object. can modify aData for saving.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $shopObject        Shop object.
     * @param array                                  $data              Data to prepare.
     * @param bool                                   $allowCustomShopId If allow custom shop id.
     *
     * @return array
     */
    protected function pre_assign_object($shop_object, $data, $allow_custom_shop_id)
    {
        $data = parent::pre_assign_object($shop_object, $data, $allow_custom_shop_id);
        if (!$data['OXPARENTID']) {
            $data['OXPARENTID'] = 'oxrootid';
        }
        return $data;
    }
}