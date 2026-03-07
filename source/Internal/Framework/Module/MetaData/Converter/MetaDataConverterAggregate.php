<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Converter;

class MetaDataConverterAggregate implements MetaDataConverterInterface
{
    /**
     * @var MetaDataConverterInterface[]
     */
    private readonly array $converters;

    public function __construct(MetaDataConverterInterface ...$converters)
    {
        $this->converters = $converters;
    }

    public function convert(array $metaData): array
    {
        foreach ($this->converters as $converter) {
            $metaData = $converter->convert($metaData);
        }

        return $metaData;
    }
}
