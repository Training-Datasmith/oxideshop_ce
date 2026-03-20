<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Autoload;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade\Active_Modules_Data_Provider_Bridge_Interface;
/**
 * Autoloader for module classes and extensions.
 *
 * @internal Do not make a module extension for this class.
 */
class Module_Autoload
{
    /** @var array Classes, for which extension class chain was created. */
    public $tried_classes = [];
    /**
     * @var null|ModuleAutoload A singleton instance of this class or a sub class of this class
     */
    private static ?self $instance = null;
    /**
     * ModuleAutoload constructor.
     *
     * Make constructor protected to ensure Singleton pattern
     */
    protected function __construct()
    {
    }
    /**
     * Magic clone method.
     *
     * Make method private to ensure Singleton pattern
     */
    private function __clone()
    {
    }
    /**
     * Tries to autoload given class. Searches for the class in module extensions.
     *
     * @param string $class Class name.
     *
     * @return bool
     */
    public static function autoload($class)
    {
        /**
         * Classes from unified namespace cannot be loaded by this auto loader.
         * Do not try to load them in order to avoid strange errors in edge cases.
         */
        if (str_contains($class, 'OxidEsales\Eshop\\')) {
            return false;
        }
        $instance = static::get_instance();
        $class = strtolower(basename($class));
        $class = preg_replace('/_parent$/i', '', $class);
        if (!in_array($class, $instance->tried_classes)) {
            $instance->tried_classes[] = $class;
            $instance->create_extension_class_chain($class);
        }
    }
    /**
     * Returns the singleton instance of this class or of a sub class of this class.
     *
     * @return ModuleAutoload The singleton instance.
     */
    public static function get_instance(): \Oxid_Esales\Eshop_Community\Core\Autoload\Module_Autoload
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * When module is extending other module's extension (module class, which is extending shop class),
     * this class comes to autoload and class chain has to be created.
     *
     * @param string $class
     */
    protected function create_extension_class_chain($class)
    {
        $extensions = Container_Facade::get(Active_Modules_Data_Provider_Bridge_Interface::class)->get_class_extensions();
        if (is_array($extensions)) {
            $class = preg_quote($class, '/');
            foreach ($extensions as $parent_class => $extension_paths) {
                foreach ($extension_paths as $extension_path) {
                    if (preg_match('/\b' . $class . '($|\&)/i', (string) $extension_path)) {
                        Registry::get_utils_object()->get_class_name($parent_class);
                        break;
                    }
                }
            }
        }
    }
}