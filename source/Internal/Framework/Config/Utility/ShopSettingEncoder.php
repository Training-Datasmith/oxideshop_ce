<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Config\Utility;

use OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject\ShopSettingType;
use OxidEsales\EshopCommunity\Internal\Framework\Config\Exception\InvalidShopSettingValueException;

use function serialize;
use function unserialize;

class ShopSettingEncoder implements ShopSettingEncoderInterface
{
    /**
     * @param mixed  $value
     * @return mixed
     */
    public function encode(string $encodingType, $value)
    {
        $this->validateSettingValue($value);

        return match ($encodingType) {
            ShopSettingType::ARRAY, ShopSettingType::ASSOCIATIVE_ARRAY => serialize($value),
            ShopSettingType::BOOLEAN => $value === true ? '1' : '',
            default => $value,
        };
    }

    /**
     * @param mixed  $value
     * @return mixed
     */
    public function decode(string $encodingType, $value)
    {
        // phpcs:disable
        return match ($encodingType) {
            ShopSettingType::ARRAY, ShopSettingType::ASSOCIATIVE_ARRAY => unserialize($value, ['allowed_classes' => false]),
            ShopSettingType::BOOLEAN => $value === 'true' || $value === '1',
            default => $value,
        };
        // phpcs:enable
    }

    /**
     * @param mixed $value
     * @throws InvalidShopSettingValueException
     */
    private function validateSettingValue($value): void
    {
        if (is_object($value)) {
            throw new InvalidShopSettingValueException(
                'Shop setting value must not be an object.'
            );
        }
    }
}
