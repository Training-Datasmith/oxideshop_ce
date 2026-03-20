<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
class Module_Configuration_Validator_Aggregate implements Module_Configuration_Validator_Interface
{
    private readonly array $validators;
    public function __construct(Module_Configuration_Validator_Interface ...$validators)
    {
        $this->validators = $validators;
    }
    public function validate(Module_Configuration $configuration, int $shop_id): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($configuration, $shop_id);
        }
    }
}