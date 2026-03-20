<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Provider;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Schemata_Provider_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Unsupported_Meta_Data_Key_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Unsupported_Meta_Data_Value_Type_Exception;
/**
 * @deprecated will be removed in v7.0
 */
class Meta_Data_Schema_Validator implements Meta_Data_Schema_Validator_Interface
{
    private static array $sections_excluded_from_item_validation = [Meta_Data_Provider::METADATA_EXTEND, Meta_Data_Provider::METADATA_CONTROLLERS, Meta_Data_Provider::METADATA_EVENTS];
    private string $current_validation_meta_data_version;
    public function __construct(private readonly Meta_Data_Schemata_Provider_Interface $meta_data_schemata_provider)
    {
    }
    /**
     * Validate that a given metadata meets the specifications of a given metadata version
     *
     *
     * @throws UnsupportedMetaDataValueTypeException
     * @throws UnsupportedMetaDataKeyException
     */
    public function validate(string $meta_data_file_path, string $meta_data_version, array $meta_data): void
    {
        $this->current_validation_meta_data_version = $meta_data_version;
        $supported_meta_data_keys = $this->meta_data_schemata_provider->get_flipped_meta_data_schema_for_version($this->current_validation_meta_data_version);
        foreach ($meta_data as $meta_data_key => $value) {
            if (is_scalar($value)) {
                $this->validate_meta_data_key($supported_meta_data_keys, (string) $meta_data_key);
            } elseif (true === \is_array($value)) {
                $this->validate_meta_data_section($supported_meta_data_keys, $meta_data_key, $value);
            } else {
                throw new Unsupported_Meta_Data_Value_Type_Exception('The value type "' . \gettype($value) . '" is not supported in metadata version ' . $this->current_validation_meta_data_version);
            }
        }
    }
    /**
     *
     * @throws UnsupportedMetaDataKeyException
     */
    private function validate_meta_data_key(array $supported_meta_data_keys, string $meta_data_key): void
    {
        if (false === array_key_exists($meta_data_key, $supported_meta_data_keys)) {
            throw new Unsupported_Meta_Data_Key_Exception('The metadata key "' . $meta_data_key . '" is not supported in metadata version "' . $this->current_validation_meta_data_version . '".');
        }
    }
    /**
     * Validate well defined section items
     *
     *
     * @throws UnsupportedMetaDataKeyException
     */
    private function validate_meta_data_section_items(array $supported_meta_data_keys, string $section_name, array $section_data): void
    {
        foreach ($section_data as $section_item) {
            if (\is_array($section_item)) {
                $meta_data_keys = array_keys($section_item);
                foreach ($meta_data_keys as $meta_data_key) {
                    $this->validate_meta_data_key($supported_meta_data_keys[$section_name], $meta_data_key);
                }
            }
        }
    }
    /**
     * Validate a section of metadata like 'blocks' or 'settings', which are multidimensional arrays of well
     * defined items. There are sections (e.g. extend), which are arrays or multidimensional arrays
     * of not well defined items. In these cases the items cannot be validated.
     *
     * @throws UnsupportedMetaDataKeyException
     */
    private function validate_meta_data_section(array $supported_meta_data_keys, string $section_name, array $section_data): void
    {
        $this->validate_meta_data_key($supported_meta_data_keys, $section_name);
        if (\in_array($section_name, static::$sections_excluded_from_item_validation, true)) {
            return;
        }
        $this->validate_meta_data_section_items($supported_meta_data_keys, $section_name, $section_data);
    }
}