<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\GenericImport\ImportObject;

/**
 * Import object for Article Extends.
 */
class ArticleExtends extends \OxidEsales\Eshop\Core\GenericImport\ImportObject\ImportObject
{
    /** @var string Database table name. */
    protected $tableName = 'oxartextends';

    /** @var string Shop object name. */
    protected $shopObjectName = 'oxI18n';

    /**
     * Creates shop object.
     *
     * @return \OxidEsales\Eshop\Core\Model\MultiLanguageModel
     */
    protected function createShopObject()
    {
        $shopObject = parent::createShopObject();
        $shopObject->init('oxartextends');

        return $shopObject;
    }
}
