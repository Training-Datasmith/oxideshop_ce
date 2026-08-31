<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Autoload;

/**
 * This class autoloads backwards compatible classes by triggering the composer autoloader via a unified namespace
 * class.
 *
 * @internal Do not make a module extension for this class.
 */
class BackwardsCompatibilityAutoload
{
    /**
     * Register backwards-compatible aliases for unified classes that are already loaded.
     *
     * PHP 8.4 does not autoload parent class names in is_subclass_of(), so aliases must exist
     * before legacy class names are used in inheritance checks.
     */
    public static function registerAliasesForLoadedClasses(): void
    {
        foreach (static::getBackwardsCompatibilityClassMap() as $alias => $unifiedClass) {
            if (class_exists($unifiedClass, false) && !class_exists($alias, false)) {
                class_alias($unifiedClass, $alias);
            }
        }
    }
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

        $unifiedNamespaceClassName = static::getUnifiedNamespaceClassForBcAlias($class);
        if (!empty($unifiedNamespaceClassName)) {
            static::forceBackwardsCompatiblityClassLoading($unifiedNamespaceClassName, $class);

            return class_exists($class, false);
        }

        return false;
    }

    /**
     * Return the name of a Unified Namespace class for a given backwards compatible class
     *
     * @param string $bcAlias Name of the backwards compatible class like oxArticle
     *
     * @return string Name of the unified namespace class like OxidEsales\Eshop\Application\Model\Article
     */
    private static function getUnifiedNamespaceClassForBcAlias($bcAlias)
    {
        $classMap = static::getBackwardsCompatibilityClassMap();
        $bcAlias = strtolower($bcAlias);

        return $classMap[$bcAlias] ?? '';
    }

    /**
     * This triggers loading the unified namespace class via composer autoloader and also the
     * aliasing of the backwards compatible class.
     *
     * @param string $class Name of the class to load
     */
    private static function forceBackwardsCompatiblityClassLoading(string $class, string $bcAlias): void
    {
        if (!class_exists($class, false)) {
            class_exists($class);
        }

        if (!class_exists($bcAlias, false)) {
            class_alias($class, $bcAlias);
        }
    }

    /**
     * Return the backwards compatible class map.
     *
     * @return array Mapping of Unified Namespace to backwards compatible classes.
     */
    private static function getBackwardsCompatibilityClassMap(): array
    {
        return (new BackwardsCompatibilityClassMapProvider())->getMap();
    }
}
