<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Container;

use Oxid_Esales\Eshop\Core\Shop_Id_Calculator;
use Oxid_Esales\Eshop\Core\Utils_Server;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Container_Builder;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service\Container_Cache_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service\Filesystem_Container_Cache;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context;
use Psr\Container\Container_Interface;
use Symfony\Component\Filesystem\Filesystem;
/**
 * @deprecated use OxidEsales\EshopCommunity\Core\Di\ContainerFacade
 */
class Container_Factory implements Container_Provider_Interface
{
    private static ?\Oxid_Esales\Eshop_Community\Internal\Container\Container_Factory $instance = null;
    private Container_Interface $symfony_container;
    private readonly Container_Cache_Interface $cache;
    private static ?int $shop_id;
    /**
     * The constructor's private to make class a singleton
     */
    private function __construct()
    {
        $this->cache = new Filesystem_Container_Cache(new Basic_Context(), new Filesystem());
    }
    public static function get(): Container_Interface
    {
        return self::get_instance()->get_container();
    }
    public function get_container(): Container_Interface
    {
        $custom_container_provider = getenv('OXID_CONTAINER_PROVIDER');
        if ($custom_container_provider) {
            return $custom_container_provider::get();
        }
        if (!isset($this->symfony_container)) {
            $this->initialize_container();
        }
        return $this->symfony_container;
    }
    private function initialize_container(): void
    {
        if ($this->cache->exists(self::get_shop_id())) {
            $this->symfony_container = $this->cache->get(self::get_shop_id());
        } else {
            $this->compile_symfony_container();
            $this->cache->put($this->symfony_container, self::get_shop_id());
        }
    }
    private function compile_symfony_container(): void
    {
        $container_builder = new Container_Builder(new Basic_Context(), self::get_shop_id());
        $this->symfony_container = $container_builder->get_container();
        $this->symfony_container->compile(true);
    }
    public static function get_instance(): Container_Factory
    {
        if (self::$instance === null) {
            self::$instance = new Container_Factory();
        }
        return self::$instance;
    }
    public static function reset_container(): void
    {
        $custom_container_provider = getenv('OXID_CONTAINER_PROVIDER');
        if ($custom_container_provider) {
            $custom_container_provider::reset_container();
        }
        self::$shop_id = null;
        self::get_instance()->cache->invalidate(self::get_shop_id());
        self::$instance = null;
    }
    private static function get_shop_id(): int
    {
        if (!isset(self::$shop_id)) {
            self::$shop_id = (new Shop_Id_Calculator(new Utils_Server()))->get_shop_id();
        }
        return self::$shop_id;
    }
}