<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Invalid_Meta_Data_Exception;
interface Meta_Data_Provider_Interface
{
    /**
     * @throws InvalidMetaDataException
     */
    public function get_data(string $file_path): array;
}