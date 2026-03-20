<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Module;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Module_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Shop_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Configuration_Not_Found_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Provider;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Bridge\Module_Activation_Bridge_Interface;
/**
 * Module class.
 *
 * @deprecated since v6.4.0 (2019-03-22); Use service 'OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ShopConfigurationDaoBridgeInterface'.
 * @internal Do not make a module extension for this class.
 */
class Module extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Metadata version as defined in metadata.php
     */
    protected $meta_data_version;
    /**
     * @param mixed $metaDataVersion
     */
    public function set_meta_data_version($meta_data_version): void
    {
        $this->meta_data_version = $meta_data_version;
    }
    /**
     * Modules info array
     *
     * @var array
     */
    protected $_a_module = [];
    /**
     * Defines if module has metadata file or not
     *
     * @var bool
     */
    protected $_bl_metadata = false;
    /**
     * Defines if module is registered in metadata or legacy storage
     *
     * @var bool
     */
    protected $_bl_registered = false;
    /**
     * Set passed module data
     *
     * @param array $aModule module data
     */
    public function set_module_data($a_module): void
    {
        $this->_a_module = $a_module;
    }
    /**
     * Get the modules metadata array
     *
     * @return  array Module meta data array
     */
    public function get_module_data()
    {
        return $this->_a_module;
    }
    /**
     * Load module info
     *
     * @param string $moduleId
     *
     * @return bool
     */
    public function load($module_id)
    {
        try {
            $this->_a_module['id'] = $module_id;
            $module_configuration = Container_Facade::get(Module_Configuration_Dao_Bridge_Interface::class)->get($module_id);
            $this->_a_module = $this->convert_module_configuration_to_array($module_configuration);
            $this->_bl_registered = true;
            $this->_bl_metadata = true;
            $this->_a_module['active'] = $this->is_active();
            return true;
        } catch (Module_Configuration_Not_Found_Exception) {
            return false;
        }
        return false;
    }
    /**
     * Get module description
     *
     * @return string
     */
    public function get_description()
    {
        $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_tpl_language();
        return $this->get_info('description', $i_lang);
    }
    /**
     * Get module title
     *
     * @return string
     */
    public function get_title()
    {
        $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_tpl_language();
        return $this->get_info('title', $i_lang);
    }
    /**
     * Get module ID
     *
     * @return string
     */
    public function get_id()
    {
        return $this->_a_module['id'];
    }
    /**
     * Returns array of module extensions.
     *
     * @return array
     */
    public function get_extensions()
    {
        $module_configuration = Container_Facade::get(Module_Configuration_Dao_Bridge_Interface::class)->get($this->get_id());
        $extensions = [];
        if ($module_configuration->has_class_extensions()) {
            foreach ($module_configuration->get_class_extensions() as $extension) {
                $extensions[$extension->get_shop_class_name()] = $extension->get_module_extension_class_name();
            }
        }
        return $extensions;
    }
    /**
     * Returns associative array of module controller ids and corresponding classes.
     *
     * @return array
     */
    public function get_controllers()
    {
        if (isset($this->_a_module['controllers']) && !is_array($this->_a_module['controllers'])) {
            throw new \InvalidArgumentException('Value for metadata key "controllers" must be an array');
        }
        return isset($this->_a_module['controllers']) ? array_change_key_case($this->_a_module['controllers']) : [];
    }
    /**
     * Get module ID
     *
     * @param string $module extension full path
     *
     * @return string
     */
    public function get_id_by_path($module)
    {
        $module_id = null;
        $module_file = $module;
        $module_id = $this->get_module_id_by_class_name($module);
        if (!$module_id) {
            $module_paths = $this->get_module_paths();
            if (is_array($module_paths)) {
                foreach ($module_paths as $id => $path) {
                    if (str_starts_with($module_file, $path . '/')) {
                        $module_id = $id;
                    }
                }
            }
        }
        if (!$module_id) {
            $module_id = substr($module_file, 0, strpos($module_file, '/'));
        }
        if (!$module_id) {
            return $module_file;
        }
        return $module_id;
    }
    /**
     * Get the module id for a given class name. If there are duplicates, the first module id will be returned.
     *
     * @param string $className
     *
     * @return string
     */
    public function get_module_id_by_class_name($class_name)
    {
        $module_id = '';
        foreach ($this->get_shop_configuration()->get_module_configurations() as $module) {
            if ($module->has_class_extension($class_name)) {
                return $module->get_id();
            }
        }
        return $module_id;
    }
    /**
     * Get module info item. If second param is passed, will try
     * to get value according selected language.
     *
     * @param string $sName name of info item to retrieve
     * @param string $iLang language ID
     *
     * @return mixed
     */
    public function get_info($s_name, $i_lang = null)
    {
        if (isset($this->_a_module[$s_name])) {
            if ($i_lang !== null && is_array($this->_a_module[$s_name])) {
                $s_value = null;
                $s_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_abbr($i_lang);
                if (!empty($this->_a_module[$s_name])) {
                    if (!empty($this->_a_module[$s_name][$s_lang])) {
                        $s_value = $this->_a_module[$s_name][$s_lang];
                    } elseif (!empty($this->_a_module['lang'])) {
                        // trying to get value according default language
                        $s_value = $this->_a_module[$s_name][$this->_a_module['lang']];
                    } else {
                        // returning first array value
                        $s_value = reset($this->_a_module[$s_name]);
                    }
                    return $s_value;
                }
            } else {
                return $this->_a_module[$s_name];
            }
        }
    }
    /**
     * Check if extension is active
     *
     * @return bool
     */
    public function is_active()
    {
        if ($this->get_id() === null) {
            return false;
        }
        return Container_Facade::get(Module_Activation_Bridge_Interface::class)->is_active($this->get_id(), Registry::get_config()->get_shop_id());
    }
    /**
     * Checks if has extend class.
     *
     * @return bool
     */
    public function has_extend_class()
    {
        return !empty($this->get_extensions());
    }
    /**
     * Checks if module is registered in any way
     *
     * @return bool
     */
    public function is_registered()
    {
        return $this->_bl_registered;
    }
    /**
     * Checks if module has metadata
     *
     * @return bool
     */
    public function has_metadata()
    {
        return $this->_bl_metadata;
    }
    /**
     * Get module id's with path
     *
     * @return array
     */
    public function get_module_paths()
    {
        $module_configurations = $this->get_installed_module_configurations();
        $paths = [];
        foreach ($module_configurations as $module_configuration) {
            $paths[$module_configuration->get_id()] = $module_configuration->get_module_source();
        }
        return $paths;
    }
    /**
     * Include data from metadata.php
     *
     * @param string $metadataPath Path to metadata.php
     *
     */
    protected function include_module_meta_data($metadata_path)
    {
        $s_metadata_version = null;
        include $metadata_path;
        /**
         * metadata.php should include a variable called $sMetadataVersion
         */
        if (isset($s_metadata_version)) {
            $this->set_meta_data_version($s_metadata_version);
        }
    }
    private function get_installed_module_configurations(): array
    {
        $shop_configuration = $this->get_shop_configuration();
        return $shop_configuration->get_module_configurations();
    }
    private function get_shop_configuration(): Shop_Configuration
    {
        return Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get();
    }
    /**
     * Convert ModuleConfiguration to Array
     *
     *
     */
    private function convert_module_configuration_to_array(Module_Configuration $configuration): array
    {
        $data = ['id' => $configuration->get_id(), 'version' => $configuration->get_version(), 'title' => $configuration->get_title(), 'description' => $configuration->get_description(), 'lang' => $configuration->get_lang(), 'thumbnail' => $configuration->get_thumbnail(), 'author' => $configuration->get_author(), 'url' => $configuration->get_url(), 'email' => $configuration->get_email()];
        foreach ($this->convert_module_settings_to_array($configuration) as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }
    private function convert_module_settings_to_array(Module_Configuration $module_configuration): array
    {
        $data = [];
        $data[Meta_Data_Provider::METADATA_EXTEND] = $this->convert_class_extensions_to_array($module_configuration);
        $data[Meta_Data_Provider::METADATA_CONTROLLERS] = $this->convert_controllers_to_array($module_configuration);
        $data[Meta_Data_Provider::METADATA_EVENTS] = $this->convert_events_to_array($module_configuration);
        $data[Meta_Data_Provider::METADATA_SETTINGS] = $this->convert_settings_to_array($module_configuration);
        return $data;
    }
    private function convert_class_extensions_to_array(Module_Configuration $module_configuration): array
    {
        $data = [];
        foreach ($module_configuration->get_class_extensions() as $extension) {
            $data[$extension->get_shop_class_name()] = $extension->get_module_extension_class_name();
        }
        return $data;
    }
    private function convert_controllers_to_array(Module_Configuration $module_configuration): array
    {
        $data = [];
        foreach ($module_configuration->get_controllers() as $controller) {
            $data[$controller->get_id()] = $controller->get_controller_class_name_space();
        }
        return $data;
    }
    private function convert_events_to_array(Module_Configuration $module_configuration): array
    {
        $data = [];
        foreach ($module_configuration->get_events() as $event) {
            $data[$event->get_action()] = $event->get_method();
        }
        return $data;
    }
    private function convert_settings_to_array(Module_Configuration $module_configuration): array
    {
        $data = [];
        foreach ($module_configuration->get_module_settings() as $index => $setting) {
            if ($setting->get_group_name()) {
                $data[$index]['group'] = $setting->get_group_name();
            }
            if ($setting->get_name()) {
                $data[$index]['name'] = $setting->get_name();
            }
            if ($setting->get_type()) {
                $data[$index]['type'] = $setting->get_type();
            }
            $data[$index]['value'] = $setting->get_value();
            if (!empty($setting->get_constraints())) {
                $data[$index]['constraints'] = $setting->get_constraints();
            }
            if ($setting->get_position_in_group() > 0) {
                $data[$index]['position'] = $setting->get_position_in_group();
            }
        }
        return $data;
    }
}