<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Cross-selling.
 */
class Cross_Selling extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxobject2article';
    /** @var array List of database key fields (i.e. oxid). */
    protected $key_field_list = ['OXARTICLENID' => 'OXARTICLENID', 'OXOBJECTID' => 'OXOBJECTID'];
}