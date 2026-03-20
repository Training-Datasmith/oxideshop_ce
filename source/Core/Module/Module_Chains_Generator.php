<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Module;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade\Active_Modules_Data_Provider_Bridge_Interface;
/**
 * Generates class chains for extended classes by modules.
 * IMPORTANT: Due to the way the shop is prepared for testing, you must not use Registry::getConfig() in this class.
 *            oxNew will enter in an endless loop, if you try to do that.
 *
 * @internal Do not make a module extension for this class.
 */
class Module_Chains_Generator
{
    /**
     * Creates given class chains.
     *
     * @param string $className  Class name.
     * @param string $classAlias Class alias, used for searching module extensions. Class is used if no alias given.
     *
     * @return string
     */
    public function create_class_chain($class_name, $class_alias = null)
    {
        if (!$class_alias) {
            $class_alias = $class_name;
        }
        $active_chain = $this->get_active_chain($class_name, $class_alias);
        if (!empty($active_chain)) {
            return $this->create_class_extensions($active_chain, $class_alias);
        }
        return $class_name;
    }
    /**
     * Assembles class chains.
     *
     * @param string $className  Class name.
     * @param string $classAlias Class alias, used for searching module extensions. Class is used if no alias given.
     *
     * @return array
     */
    public function get_active_chain($class_name, $class_alias = null)
    {
        return $this->get_full_chain($class_name, $class_alias);
    }
    /**
     * Build full class chain.
     *
     * @param string $className
     * @param string $classAlias
     *
     * @return array
     */
    public function get_full_chain($class_name, $class_alias)
    {
        $chain = Container_Facade::get(Active_Modules_Data_Provider_Bridge_Interface::class)->get_class_extensions();
        $class_chain = $chain[$class_name] ?? [];
        return $class_alias && $class_alias !== $class_name ? array_merge($class_chain, $this->get_chain_for_backwards_compatibility_class_alias($chain, $class_alias)) : $class_chain;
    }
    /**
     * Creates middle classes if needed.
     *
     * @param array  $classChain Module names
     * @param string $baseClass  Oxid base class
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException missing system component exception
     *
     * @return string
     */
    protected function create_class_extensions($class_chain, $base_class): string|array
    {
        //security: just preventing string termination
        $last_class = str_replace(chr(0), '', $base_class);
        $parent_class = $last_class;
        foreach ($class_chain as $extension_path) {
            $extension_path = str_replace(chr(0), '', $extension_path);
            if ($this->create_class_extension($parent_class, $extension_path)) {
                if (\Oxid_Esales\Eshop\Core\Namespace_Information_Provider::is_namespaced_class($extension_path)) {
                    $parent_class = $extension_path;
                    $last_class = $extension_path;
                } else {
                    $parent_class = basename($extension_path);
                    $last_class = basename($extension_path);
                }
            }
        }
        //returning the last module from the chain
        return $last_class;
    }
    /**
     * Checks, if a given class can be loaded and create an alias for _parent.
     * If the class cannot be loaded, some error handling is done.
     *
     * @see self::onModuleExtensionCreationError
     * @see self::handleSpecialCases
     *
     * e.g. class suboutput1_parent extends oxoutput {}
     *      class suboutput2_parent extends suboutput1 {}
     *
     * @param string $parentClass
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException
     * @return bool Return on error
     */
    protected function create_class_extension($parent_class, string $module_class): bool
    {
        /**
         * Test if the class file could be loaded
         */
        /** @var \Composer\Autoload\ClassLoader $composerClassLoader */
        $composer_class_loader = include VENDOR_PATH . 'autoload.php';
        if (!strpos($module_class, '_parent') && !$composer_class_loader->find_file($module_class)) {
            $this->handle_special_cases($parent_class);
            $this->on_module_extension_creation_error($module_class);
            return false;
        }
        $module_class_parent_alias = $module_class . '_parent';
        if (!class_exists($module_class_parent_alias, false)) {
            class_alias($parent_class, $module_class_parent_alias);
        }
        return true;
    }
    /**
     * Special case is when oxconfig class is extended: we cant call "_disableModule" as it requires valid config object
     * but we can't create it as module class extending it does not exist. So we will use original oxConfig object instead.
     *
     * @param string $requestedClass Class, for which extension chain was generated.
     */
    protected function handle_special_cases($requested_class)
    {
        // We do actually have to check the whole inheritance chain in case two OXID modules each have an extension
        // on oxconfig. Checking for $requestedClass only would cover only one inheritance step.
        $is_config_class = false;
        $current_class = $requested_class;
        $safety_count = 0;
        do {
            if ($current_class == 'oxconfig' || $current_class == \Oxid_Esales\Eshop\Core\Config::class) {
                $is_config_class = true;
                break;
            }
            if ($safety_count++ === 200) {
                throw new \Oxid_Esales\Eshop\Core\Exception\System_Component_Exception('Recursion limit reached while traversing class inheritance chain.');
            }
            // We can be sure that the parent class of the current class is actually defined due to the way
            // the extension chain is traversed.
        } while ($current_class = get_parent_class($current_class));
        if ($is_config_class) {
            $config = new \Oxid_Esales\Eshop\Core\Config();
            \Oxid_Esales\Eshop\Core\Registry::set(\Oxid_Esales\Eshop\Core\Config::class, $config);
        }
    }
    /**
     * Writes/logs an error on module extension creation problem
     */
    protected function on_module_extension_creation_error(string $module_class)
    {
        $module_id = '(module id not availible)';
        if (class_exists("\\OxidEsales\\Eshop\\Core\\Module\\Module", false)) {
            $module = new \Oxid_Esales\Eshop\Core\Module\Module();
            $module_id = $module->get_id_by_path($module_class);
        }
        $message = sprintf('Module class %s not found. Module ID %s', $module_class, $module_id);
        $exception = new \Oxid_Esales\Eshop\Core\Exception\System_Component_Exception($message);
        \Oxid_Esales\Eshop\Core\Registry::get_logger()->error($exception->get_message(), [$exception]);
    }
    private function get_chain_for_backwards_compatibility_class_alias(array $chain, string $class_alias): array
    {
        return array_change_key_case($chain)[strtolower($class_alias)] ?? [];
    }
}