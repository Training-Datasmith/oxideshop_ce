<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Unsupported_Meta_Data_Version_Exception;
/**
 * @deprecated will be removed in v7.0
 */
class Meta_Data_Schemata_Provider implements Meta_Data_Schemata_Provider_Interface
{
    public function __construct(private array $meta_data_schemata)
    {
    }
    public function get_meta_data_schemata(): array
    {
        return $this->meta_data_schemata;
    }
    /**
     *
     * @throws UnsupportedMetaDataVersionException
     *
     */
    public function get_meta_data_schema_for_version(string $meta_data_version): array
    {
        if (false === array_key_exists($meta_data_version, $this->meta_data_schemata)) {
            throw new Unsupported_Meta_Data_Version_Exception("Metadata version {$meta_data_version} is not supported");
        }
        return $this->meta_data_schemata[$meta_data_version];
    }
    /**
     *
     * @throws UnsupportedMetaDataVersionException
     *
     */
    public function get_flipped_meta_data_schema_for_version(string $meta_data_version): array
    {
        if (false === array_key_exists($meta_data_version, $this->meta_data_schemata)) {
            throw new Unsupported_Meta_Data_Version_Exception("Metadata version {$meta_data_version} is not supported");
        }
        return $this->array_flip_recursive($this->meta_data_schemata[$meta_data_version]);
    }
    /**
     * Recursively exchange keys and values for a given array
     *
     *
     */
    private function array_flip_recursive(array $meta_data_version): array
    {
        $transposed_array = [];
        foreach ($meta_data_version as $key => $item) {
            if (is_numeric($key) && \is_string($item)) {
                $transposed_array[$item] = $key;
            } elseif (\is_string($key) && \is_array($item)) {
                $transposed_array[$key] = $this->array_flip_recursive($item);
            }
        }
        return $transposed_array;
    }
}