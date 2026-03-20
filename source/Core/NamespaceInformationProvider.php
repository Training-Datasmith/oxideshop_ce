<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class NamespaceInformationProvider
 *
 * @package OxidEsales\EshopCommunity\Core
 *
 * @internal Do not make a module extension for this class.
 */
class Namespace_Information_Provider
{
    /**
     * Array contains names of the official OXID eShop edition namespaces.
     *
     * @var array
     */
    protected static $shop_edition_namespaces = ['CE' => 'OxidEsales\EshopCommunity\\', 'PE' => 'OxidEsales\EshopProfessional\\', 'EE' => 'OxidEsales\EshopEnterprise\\'];
    /**
     * Array contains names of the official OXID eShop edition namespaces for tests.
     *
     * @var array
     */
    protected static $shop_edition_test_namespaces = ['CE' => 'OxidEsales\EshopCommunity\Tests\\', 'PE' => 'OxidEsales\EshopProfessional\Tests\\', 'EE' => 'OxidEsales\EshopEnterprise\Tests\\'];
    /**
     * OXID eShop unified namespace.
     *
     * @var string
     */
    protected static $unified_namespace = 'OxidEsales\Eshop\\';
    /**
     * Getter for array with official OXID eShop Edition namespaces.
     *
     * @return array
     */
    public static function get_shop_edition_namespaces()
    {
        return static::$shop_edition_namespaces;
    }
    /**
     * Getter for official OXID eShop Unified Namespace.
     *
     * @return string
     */
    public static function get_unified_namespace()
    {
        return static::$unified_namespace;
    }
    /**
     * @param string $className
     */
    public static function is_namespaced_class($class_name): bool
    {
        return str_contains($class_name, '\\');
    }
    /**
     * Check if given class belongs to a shop edition namespace.
     *
     * @param string $className
     *
     * @return bool
     */
    public static function class_belongs_to_shop_edition_namespace($class_name)
    {
        return static::class_belongs_to_namespace($class_name, static::get_shop_edition_namespaces());
    }
    /**
     * Check if given class belongs to a shop edition namespace.
     *
     * @param string $className
     */
    public static function class_belongs_to_shop_unified_namespace($class_name): bool
    {
        $lc_class_name = strtolower(ltrim($class_name, '\\'));
        $unified_namespace = static::get_unified_namespace();
        return str_contains($lc_class_name, strtolower($unified_namespace));
    }
    /**
     * Check if given class belongs to one of the supplied namespaces.
     *
     * @param string $className
     * @param array  $namespaces
     *
     * @return bool
     */
    private static function class_belongs_to_namespace($class_name, $namespaces)
    {
        $belongs_to_namespace = false;
        $check = array_values($namespaces);
        $lc_class_name = strtolower(ltrim($class_name, '\\'));
        foreach ($check as $namespace) {
            if (str_contains($lc_class_name, strtolower((string) $namespace))) {
                $belongs_to_namespace = true;
                continue;
            }
        }
        return $belongs_to_namespace;
    }
}