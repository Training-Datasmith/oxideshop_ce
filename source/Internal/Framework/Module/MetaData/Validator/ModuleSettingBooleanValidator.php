<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Provider;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Setting_Not_Valid_Exception;
class Module_Setting_Boolean_Validator implements Meta_Data_Validator_Interface
{
    private const ALLOWED_VALUES = [0, 1, '0', '1', 'true', 'false', true, false];
    /**
     * @throws SettingNotValidException
     */
    public function validate(array $meta_data): void
    {
        if (isset($meta_data[Meta_Data_Provider::METADATA_SETTINGS])) {
            $settings = $meta_data[Meta_Data_Provider::METADATA_SETTINGS];
            foreach ($settings as $setting) {
                $this->validate_setting($meta_data, $setting);
            }
        }
    }
    /**
     * @throws SettingNotValidException
     */
    private function validate_setting(array $meta_data, array $setting): void
    {
        if (isset($setting['type']) && $setting['type'] === 'bool') {
            $value = is_string($setting['value']) ? strtolower($setting['value']) : $setting['value'];
            if (!in_array($value, self::ALLOWED_VALUES, true)) {
                throw new Setting_Not_Valid_Exception('Invalid boolean value- "' . $setting['value'] . '" was used for module setting. ' . 'Please update setting value in module "' . $meta_data[Meta_Data_Provider::METADATA_ID] . '".');
            }
        }
    }
}