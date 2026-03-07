<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\GenericImport\ImportObject;

/**
 * Import object for Cross-selling.
 */
class CrossSelling extends \OxidEsales\Eshop\Core\GenericImport\ImportObject\ImportObject
{
    /** @var string Database table name. */
    protected $tableName = 'oxobject2article';

    /** @var array List of database key fields (i.e. oxid). */
    protected $keyFieldList = [
        'OXARTICLENID' => 'OXARTICLENID',
        'OXOBJECTID'   => 'OXOBJECTID',
    ];
}
