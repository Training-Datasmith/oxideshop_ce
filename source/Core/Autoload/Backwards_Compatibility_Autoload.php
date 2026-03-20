<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Autoload;

/**
 * This class autoloads backwards compatible classes by triggering the composer autoloader via a unified namespace
 * class.
 *
 * @internal Do not make a module extension for this class.
 */
class Backwards_Compatibility_Autoload
{
    /**
     * Autoload method.
     *
     * @param string $class Name of the class to be loaded
     *
     * @return bool
     */
    public static function autoload($class)
    {
        /**
         * Classes from unified namespace canot be loaded by this auto loader.
         * Do not try to load them in order to avoid strange errors in edge cases.
         */
        if (str_contains($class, 'OxidEsales\Eshop\\')) {
            return false;
        }
        $unified_namespace_class_name = static::get_unified_namespace_class_for_bc_alias($class);
        if (!empty($unified_namespace_class_name)) {
            static::force_backwards_compatiblity_class_loading($unified_namespace_class_name);
        }
    }
    /**
     * Return the name of a Unified Namespace class for a given backwards compatible class
     *
     * @param string $bcAlias Name of the backwards compatible class like oxArticle
     *
     * @return string Name of the unified namespace class like OxidEsales\Eshop\Application\Model\Article
     */
    private static function get_unified_namespace_class_for_bc_alias($bc_alias)
    {
        $class_map = static::get_backwards_compatibility_class_map();
        $bc_alias = strtolower($bc_alias);
        return $class_map[$bc_alias] ?? '';
    }
    /**
     * This triggers loading the unified namespace class via composer autoloader and also the
     * aliasing of the backwards compatible class.
     *
     * @param string $class Name of the class to load
     */
    private static function force_backwards_compatiblity_class_loading($class): void
    {
        class_exists($class);
    }
    /**
     * Return the backwards compatible class map.
     *
     * @return array Mapping of Unified Namespace to backwards compatible classes.
     */
    private static function get_backwards_compatibility_class_map(): array
    {
        return (new Backwards_Compatibility_Class_Map_Provider())->get_map();
    }
}