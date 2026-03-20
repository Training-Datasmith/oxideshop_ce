<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Config\Utility;

use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Data_Object\Shop_Setting_Type;
use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Exception\Invalid_Shop_Setting_Value_Exception;
use function serialize;
use function unserialize;
class Shop_Setting_Encoder implements Shop_Setting_Encoder_Interface
{
    /**
     * @param mixed  $value
     * @return mixed
     */
    public function encode(string $encoding_type, $value)
    {
        $this->validate_setting_value($value);
        return match ($encoding_type) {
            Shop_Setting_Type::ARRAY, Shop_Setting_Type::ASSOCIATIVE_ARRAY => serialize($value),
            Shop_Setting_Type::BOOLEAN => $value === true ? '1' : '',
            default => $value,
        };
    }
    /**
     * @param mixed  $value
     * @return mixed
     */
    public function decode(string $encoding_type, $value)
    {
        // phpcs:disable
        return match ($encoding_type) {
            Shop_Setting_Type::ARRAY, Shop_Setting_Type::ASSOCIATIVE_ARRAY => unserialize($value, ['allowed_classes' => false]),
            Shop_Setting_Type::BOOLEAN => $value === 'true' || $value === '1',
            default => $value,
        };
        // phpcs:enable
    }
    /**
     * @param mixed $value
     * @throws InvalidShopSettingValueException
     */
    private function validate_setting_value($value): void
    {
        if (is_object($value)) {
            throw new Invalid_Shop_Setting_Value_Exception('Shop setting value must not be an object.');
        }
    }
}