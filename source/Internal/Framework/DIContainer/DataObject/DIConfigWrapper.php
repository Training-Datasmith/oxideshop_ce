<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Data_Object;

use function array_key_exists;
class Di_Config_Wrapper
{
    private const SERVICE_SECTION = 'services';
    private const RESOURCE_KEY = 'resource';
    private const IMPORTS_SECTION = 'imports';
    private array $section_defaults = [self::SERVICE_SECTION => ['_defaults' => ['autowire' => true]]];
    public function __construct(private array $config_array)
    {
    }
    public function add_import(string $import_file_path): void
    {
        $this->add_section_if_missing(static::IMPORTS_SECTION);
        foreach ($this->get_imports() as $import) {
            if ($import[static::RESOURCE_KEY] === $import_file_path) {
                return;
            }
        }
        $this->config_array[static::IMPORTS_SECTION][] = [static::RESOURCE_KEY => $import_file_path];
    }
    public function get_import_file_names(): array
    {
        $import_file_names = [];
        foreach ($this->get_imports() as $import) {
            $import_file_names[] = $import[static::RESOURCE_KEY];
        }
        return $import_file_names;
    }
    public function remove_import(string $import_file_path): void
    {
        $imports = [];
        foreach ($this->get_imports() as $import) {
            if ($import[static::RESOURCE_KEY] !== $import_file_path) {
                $imports[] = $import;
            }
        }
        $this->config_array[static::IMPORTS_SECTION] = $imports;
    }
    public function get_config_as_array(): array
    {
        $this->clean_up_config();
        return $this->config_array;
    }
    private function get_imports(): array
    {
        if (!array_key_exists(static::IMPORTS_SECTION, $this->config_array)) {
            return [];
        }
        return $this->config_array[static::IMPORTS_SECTION];
    }
    /**
     * Removes not activated services and
     * empty import or service sections from the array
     */
    private function clean_up_config(): void
    {
        $this->remove_empty_sections();
    }
    /**
     * Removes section entries when they are empty
     */
    private function remove_empty_sections(): void
    {
        $sections = [static::IMPORTS_SECTION];
        foreach ($sections as $section) {
            if (array_key_exists($section, $this->config_array) && (!$this->config_array[$section] || !count($this->config_array[$section]))) {
                unset($this->config_array[$section]);
            }
        }
    }
    /**
     * @param string $section
     */
    private function add_section_if_missing($section): void
    {
        if (!array_key_exists($section, $this->config_array)) {
            if (array_key_exists($section, $this->section_defaults)) {
                $this->config_array[$section] = $this->section_defaults[$section];
            } else {
                $this->config_array[$section] = [];
            }
        }
    }
}