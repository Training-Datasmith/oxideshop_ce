<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Converter\Meta_Data_Converter_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Invalid_Meta_Data_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator\Meta_Data_Validator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
class Meta_Data_Provider implements Meta_Data_Provider_Interface
{
    public const METADATA_ID = 'id';
    public const METADATA_METADATA_VERSION = 'metaDataVersion';
    public const METADATA_MODULE_DATA = 'moduleData';
    public const METADATA_TITLE = 'title';
    public const METADATA_DESCRIPTION = 'description';
    public const METADATA_LANG = 'lang';
    public const METADATA_THUMBNAIL = 'thumbnail';
    public const METADATA_AUTHOR = 'author';
    public const METADATA_URL = 'url';
    public const METADATA_EMAIL = 'email';
    public const METADATA_VERSION = 'version';
    public const METADATA_EXTEND = 'extend';
    public const METADATA_CONTROLLERS = 'controllers';
    public const METADATA_EVENTS = 'events';
    public const METADATA_SETTINGS = 'settings';
    /**
     * @deprecated will be removed in v7.0
     */
    public const METADATA_FILEPATH = 'metaDataFilePath';
    private string $file_path;
    public function __construct(private readonly Meta_Data_Normalizer_Interface $meta_data_normalizer, private readonly Basic_Context_Interface $context, private readonly Meta_Data_Validator_Interface $meta_data_validator_service, private readonly Meta_Data_Converter_Interface $meta_data_converter)
    {
    }
    /**
     * @throws InvalidMetaDataException
     */
    public function get_data(string $file_path): array
    {
        if (!is_readable($file_path) || is_dir($file_path)) {
            throw new \InvalidArgumentException('File ' . $file_path . ' is not readable or not even a file.');
        }
        $this->file_path = $file_path;
        $normalized_meta_data = $this->get_normalized_meta_data_file_content();
        return $this->add_file_path_to_data($normalized_meta_data);
    }
    /**
     * @throws InvalidMetaDataException
     */
    private function get_normalized_meta_data_file_content(): array
    {
        /**
         * The following variables will be overwritten when the metadata file is included.
         */
        $s_metadata_version = null;
        $a_module = null;
        include $this->file_path;
        $metadata_version = $s_metadata_version;
        $module_data = $a_module;
        $this->validate_meta_data_file_variables($metadata_version, $module_data);
        $this->meta_data_validator_service->validate($module_data);
        $module_data = $this->meta_data_converter->convert($module_data);
        $normalized_meta_data = $this->meta_data_normalizer->normalize_data($module_data);
        if (isset($normalized_meta_data[static::METADATA_EXTEND])) {
            $normalized_meta_data[static::METADATA_EXTEND] = $this->sanitize_extended_classes($normalized_meta_data);
        }
        return [static::METADATA_METADATA_VERSION => $metadata_version, static::METADATA_MODULE_DATA => $normalized_meta_data];
    }
    private function add_file_path_to_data(array $normalized_meta_data): array
    {
        $normalized_meta_data[static::METADATA_FILEPATH] = $this->file_path;
        return $normalized_meta_data;
    }
    /**
     * @param mixed $metaDataVersion
     * @param mixed $moduleData
     *
     * @throws InvalidMetaDataException
     */
    private function validate_meta_data_file_variables($meta_data_version, $module_data): void
    {
        if ($meta_data_version === null || !is_scalar($meta_data_version)) {
            throw new Invalid_Meta_Data_Exception('The variable $sMetadataVersion must be present in ' . $this->file_path . ' and it must be a scalar.');
        }
        if ($module_data === null || !\is_array($module_data)) {
            throw new Invalid_Meta_Data_Exception('The variable $aModule must be present in ' . $this->file_path . ' and it must be an array');
        }
    }
    private function sanitize_extended_classes(array $normalized_meta_data): array
    {
        $sanitized_extended_classes = [];
        $extended_classes = $normalized_meta_data[static::METADATA_EXTEND] ?? [];
        foreach ($extended_classes as $shop_class => $module_class) {
            if ($this->is_backwards_compatible_class($shop_class)) {
                $sanitized_shop_class = $this->get_backwards_compatibility_class_map()[strtolower($shop_class)];
            } else {
                $sanitized_shop_class = $shop_class;
            }
            $sanitized_extended_classes[$sanitized_shop_class] = $module_class;
        }
        return $sanitized_extended_classes;
    }
    private function is_backwards_compatible_class(string $class_name): bool
    {
        return \array_key_exists(strtolower($class_name), $this->get_backwards_compatibility_class_map());
    }
    private function get_backwards_compatibility_class_map(): array
    {
        return $this->context->get_backwards_compatibility_class_map();
    }
}