<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Article Extends.
 */
class Article_Extends extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxartextends';
    /** @var string Shop object name. */
    protected $shop_object_name = 'oxI18n';
    /**
     * Creates shop object.
     *
     * @return \OxidEsales\Eshop\Core\Model\MultiLanguageModel
     */
    protected function create_shop_object()
    {
        $shop_object = parent::create_shop_object();
        $shop_object->init('oxartextends');
        return $shop_object;
    }
}