<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Articles assignment to actions.
 */
class Article2Action extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxactions2article';
}