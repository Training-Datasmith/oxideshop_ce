<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator;

class Meta_Data_Validator_Aggregate implements Meta_Data_Validator_Interface
{
    /**
     * @var MetaDataValidatorInterface[]
     */
    private readonly array $meta_data_validators;
    public function __construct(Meta_Data_Validator_Interface ...$meta_data_validators)
    {
        $this->meta_data_validators = $meta_data_validators;
    }
    public function validate(array $meta_data): void
    {
        foreach ($this->meta_data_validators as $meta_data_validator) {
            $meta_data_validator->validate($meta_data);
        }
    }
}