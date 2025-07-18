<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Autoload;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopIdCalculator;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\ContainerBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ActiveModulesDataProviderBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext;

/**
 * Autoloader for module classes and extensions.
 *
 * @internal Do not make a module extension for this class.
 */
class ModuleAutoload
{
    /** @var array Classes, for which extension class chain was created. */
    public $triedClasses = [];

    /**
     * @var null|ModuleAutoload A singleton instance of this class or a sub class of this class
     */
    private static $instance = null;

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
         * This autoloader cannot load classes from unified namespace or DI Services.
         * Do not try to load them to avoid strange errors in edge cases.
         */
        if (preg_match('/^OxidEsales\\\\Eshop(?:\\\\|[A-Za-z]+\\\\Internal\\\\)/', $class)) {
            return false;
        }

        $instance = static::getInstance();
        $processedClassName = strtolower(basename($class));
        $processedClassName = preg_replace('/_parent$/i', '', $processedClassName);

        if (!in_array($processedClassName, $instance->triedClasses)) {
            $instance->triedClasses[] = $processedClassName;
            $instance->createExtensionClassChain($processedClassName);
        }
    }

    /**
     * Returns the singleton instance of this class or of a sub class of this class.
     *
     * @return ModuleAutoload The singleton instance.
     */
    public static function getInstance()
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
    protected function createExtensionClassChain($class)
    {
        $container = (new ContainerBuilder(new BasicContext(), (new ShopIdCalculator(new UtilsServer()))->getShopId()))
            ->getContainer();
        $container->compile(true);
        $extensions = $container->get(ActiveModulesDataProviderBridgeInterface::class)->getClassExtensions();

        if (is_array($extensions)) {
            $class = preg_quote($class, '/');

            foreach ($extensions as $parentClass => $extensionPaths) {
                foreach ($extensionPaths as $extensionPath) {
                    if (preg_match('/\b' . $class . '($|\&)/i', $extensionPath)) {
                        Registry::getUtilsObject()->getClassName($parentClass);
                        break;
                    }
                }
            }
        }
    }
}
