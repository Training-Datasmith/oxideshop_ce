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
interface Setting_Validator_Interface
{
    public function validate(string $metadata_version, array $module_settings);
}