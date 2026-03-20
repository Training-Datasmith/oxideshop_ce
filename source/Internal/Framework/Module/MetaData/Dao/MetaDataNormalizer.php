<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

use function is_string;
class Meta_Data_Normalizer implements Meta_Data_Normalizer_Interface
{
    /**
     * Normalize the array aModule in metadata.php
     *
     *
     */
    public function normalize_data(array $data): array
    {
        $normalized_meta_data = $data;
        if (isset($normalized_meta_data[Meta_Data_Provider::METADATA_SETTINGS])) {
            $normalized_meta_data[Meta_Data_Provider::METADATA_SETTINGS] = $this->convert_module_setting_constraints_to_array($normalized_meta_data[Meta_Data_Provider::METADATA_SETTINGS]);
        }
        if (isset($normalized_meta_data[Meta_Data_Provider::METADATA_TITLE])) {
            $normalized_meta_data = $this->normalize_multi_language_field($normalized_meta_data, Meta_Data_Provider::METADATA_TITLE);
        }
        if (isset($normalized_meta_data[Meta_Data_Provider::METADATA_DESCRIPTION])) {
            return $this->normalize_multi_language_field($normalized_meta_data, Meta_Data_Provider::METADATA_DESCRIPTION);
        }
        return $normalized_meta_data;
    }
    private function convert_module_setting_constraints_to_array(array $metadata_module_settings): array
    {
        foreach ($metadata_module_settings as $key => $setting) {
            if (isset($setting['constraints'])) {
                $metadata_module_settings[$key]['constraints'] = explode('|', $setting['constraints']);
            }
        }
        return $metadata_module_settings;
    }
    private function normalize_multi_language_field(array $normalized_meta_data, string $field_name): array
    {
        $title = $normalized_meta_data[$field_name];
        if (is_string($title)) {
            $default_language = $normalized_meta_data[Meta_Data_Provider::METADATA_LANG] ?? 'en';
            $normalized_title = [$default_language => $title];
            $normalized_meta_data[$field_name] = $normalized_title;
        }
        return $normalized_meta_data;
    }
}