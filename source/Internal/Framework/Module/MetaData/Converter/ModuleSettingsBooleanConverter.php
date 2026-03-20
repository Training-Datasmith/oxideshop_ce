<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Converter;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Provider;
class Module_Settings_Boolean_Converter implements Meta_Data_Converter_Interface
{
    private const CONVERSION_MAP = ['true' => true, '1' => true, 'false' => false, '0' => false];
    public function convert(array $meta_data): array
    {
        $converted_meta_data = $meta_data;
        if (isset($meta_data[Meta_Data_Provider::METADATA_SETTINGS])) {
            $settings = $meta_data[Meta_Data_Provider::METADATA_SETTINGS];
            foreach ($settings as $key => $setting) {
                $converted_meta_data[Meta_Data_Provider::METADATA_SETTINGS][$key] = $this->update_value($setting);
            }
        }
        return $converted_meta_data;
    }
    /**
     * @param $setting
     */
    private function update_value(array $setting): array
    {
        if (isset($setting['type']) && $setting['type'] === 'bool') {
            $value = is_string($setting['value']) ? strtolower($setting['value']) : $setting['value'];
            $setting['value'] = self::CONVERSION_MAP[$value] ?? $setting['value'];
        }
        return $setting;
    }
}