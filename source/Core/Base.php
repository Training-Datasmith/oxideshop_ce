<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use ReflectionMethod;
/**
 * Basic class which is used as parent class by other OXID eShop classes.
 * It provides access to some basic objects and some basic functionality.
 */
class Base
{
    /**
     * oxuser object
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected static $_o_act_user;
    /**
     * Admin mode marker
     *
     * @var bool
     */
    protected static $_bl_is_admin;
    /**
     * Only used for convenience in UNIT tests by doing so we avoid
     * writing extended classes for testing protected or private methods
     *
     * @param string $method Methods name
     * @param array  $arguments Argument array
     * @throws SystemComponentException
     * @return false|mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this, $method) && (new ReflectionMethod($this, $method))->is_public()) {
            return call_user_func_array([&$this, $method], $arguments);
        }
        throw new System_Component_Exception("Function '{$method}' does not exist or is not accessible! (" . static::class . ')' . PHP_EOL);
    }
    /**
     * Class constructor. The constructor is defined in order to be possible to call parent::__construct() in modules.
     */
    public function __construct()
    {
    }
    /**
     * Active user getter
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function get_user()
    {
        if (self::$_o_act_user === null) {
            self::$_o_act_user = false;
            $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            if ($user->load_active_user()) {
                self::$_o_act_user = $user;
            }
        }
        return self::$_o_act_user;
    }
    /**
     * Active oxuser object setter
     *
     * @param \OxidEsales\Eshop\Application\Model\User $user user object
     */
    public function set_user($user): void
    {
        self::$_o_act_user = $user;
    }
    /**
     * Admin mode status getter
     *
     * @return bool
     */
    public function is_admin()
    {
        if (self::$_bl_is_admin === null) {
            self::$_bl_is_admin = is_admin();
        }
        return self::$_bl_is_admin;
    }
    /**
     * Admin mode setter
     *
     * @param bool $isAdmin admin mode
     */
    public function set_admin_mode($is_admin): void
    {
        self::$_bl_is_admin = $is_admin;
    }
    /**
     * @template T of object
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function get_service(string $id): object
    {
        return Container_Facade::get($id);
    }
}