<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Converter;

class Meta_Data_Converter_Aggregate implements Meta_Data_Converter_Interface
{
    /**
     * @var MetaDataConverterInterface[]
     */
    private readonly array $converters;
    public function __construct(Meta_Data_Converter_Interface ...$converters)
    {
        $this->converters = $converters;
    }
    public function convert(array $meta_data): array
    {
        foreach ($this->converters as $converter) {
            $meta_data = $converter->convert($meta_data);
        }
        return $meta_data;
    }
}