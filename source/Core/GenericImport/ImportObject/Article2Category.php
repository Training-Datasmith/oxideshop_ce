<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Articles assignment to categories.
 */
class Article2Category extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxobject2category';
    /** @var array List of database key fields (i.e. oxid). */
    protected $key_field_list = ['OXOBJECTID' => 'OXOBJECTID', 'OXCATNID' => 'OXCATNID'];
}