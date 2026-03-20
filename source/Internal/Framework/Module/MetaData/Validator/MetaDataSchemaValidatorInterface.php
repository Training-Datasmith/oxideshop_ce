<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator;

/**
 * @deprecated will be removed in v7.0
 */
interface Meta_Data_Schema_Validator_Interface
{
    public function validate(string $meta_data_file_path, string $meta_data_version, array $meta_data);
}