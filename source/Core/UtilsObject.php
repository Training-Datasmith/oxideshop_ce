<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop\Core\Module\Module_Chains_Generator;
/**
 * Object Factory implementation (oxNew() method is implemented in this class).
 *
 * @internal Do not make a module extension for this class.
 */
class Utils_Object
{
    /**
     * Cache class names
     *
     * @var array
     */
    protected $_a_class_name_cache = [];
    /**
     * The array of already loaded articles
     *
     * @var array
     */
    protected static $_a_loaded_articles = [];
    /**
     * The array of already initialised instances
     *
     * @var array
     */
    protected static $_a_instance_cache = [];
    /**
     * UtilsObject class instance.
     *
     * @var UtilsObject instance
     */
    protected static $_instance;
    private ?\Oxid_Esales\Eshop_Community\Core\Backwards_Compatible_Class_Name_Provider $class_name_provider = null;
    /** @var ModuleChainsGenerator */
    private $module_chains_generator;
    private ?\Oxid_Esales\Eshop_Community\Core\Shop_Id_Calculator $shop_id_calculator = null;
    /**
     * This class is a singleton and should be instantiated with getInstance()
     */
    private function __construct()
    {
    }
    /**
     * Returns object instance
     *
     * @return UtilsObject
     */
    public static function get_instance()
    {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }
    /**
     * Factory instance setter. Sets the instance to be returned over later called oxNew().
     * This method is mostly intended to be used by phpUnit tests.
     *
     * @param string $className Class name expected to be later supplied over oxNew
     * @param object $instance  Instance object
     */
    public static function set_class_instance($class_name, $instance): void
    {
        //Get storage key as the class might be aliased.
        $storage_key = Registry::get_storage_key($class_name);
        static::$_a_class_instances[$storage_key] = $instance;
    }
    /**
     * Resets previously set instances
     */
    public static function reset_class_instances(): void
    {
        static::$_a_class_instances = [];
    }
    /**
     * Resets instance cache
     *
     * @param string $className class name in the cache
     */
    public function reset_instance_cache($class_name = null): void
    {
        if ($class_name && isset(static::$_a_instance_cache[$class_name])) {
            unset(static::$_a_instance_cache[$class_name]);
            return;
        }
        //Get storage key as the class might be aliased.
        $storage_key = Registry::get_storage_key($class_name);
        if ($class_name && isset(static::$_a_instance_cache[$storage_key])) {
            unset(static::$_a_instance_cache[$storage_key]);
            return;
        }
        //looping due to possible memory "leak".
        if (is_array(static::$_a_instance_cache)) {
            foreach (static::$_a_instance_cache as $key => $instance) {
                unset(static::$_a_instance_cache[$key]);
            }
        }
        static::$_a_instance_cache = [];
    }
    /**
     * Creates and returns new object. If creation is not available, dies and outputs
     * error message.
     *
     * @param string $className Name of class
     * @param array  $arguments constructor arguments
     *
     * @throws SystemComponentException in case that class does not exists
     *
     * @return object
     */
    public function ox_new($class_name, ...$arguments)
    {
        $arguments_count = count($arguments);
        $should_use_cache = $this->should_cache_object($class_name, $arguments);
        if (!\Oxid_Esales\Eshop\Core\Namespace_Information_Provider::is_namespaced_class($class_name)) {
            $class_name = strtolower($class_name);
        }
        //Get storage key as the class might be aliased.
        $storage_key = Registry::get_storage_key($class_name);
        if ($should_use_cache) {
            $cache_key = $arguments_count ? $storage_key . md5(serialize($arguments)) : $storage_key;
            if (isset(static::$_a_instance_cache[$cache_key])) {
                return clone static::$_a_instance_cache[$cache_key];
            }
        }
        if (!defined('OXID_PHP_UNIT') && isset($this->_a_class_name_cache[$class_name])) {
            $real_class_name = $this->_a_class_name_cache[$class_name];
        } else {
            $real_class_name = $this->get_class_name($class_name);
            //expect __autoload() (oxfunctions.php) to do its job when class_exists() is called
            if (!class_exists($real_class_name)) {
                $exception = new \Oxid_Esales\Eshop\Core\Exception\System_Component_Exception();
                /** Use setMessage here instead of passing it in constructor in order to test exception message */
                $exception->set_message('EXCEPTION_SYSTEMCOMPONENT_CLASSNOTFOUND' . ' ' . $real_class_name);
                throw $exception;
            }
            $this->_a_class_name_cache[$class_name] = $real_class_name;
        }
        $object = new $real_class_name(...$arguments);
        if (isset($cache_key) && $should_use_cache && $object instanceof \Oxid_Esales\Eshop\Core\Model\Base_Model) {
            static::$_a_instance_cache[$cache_key] = clone $object;
        }
        return $object;
    }
    /**
     * Returns generated unique ID.
     *
     * @deprecated use Id::generate() instead
     */
    public function generate_u_id(): string
    {
        return md5(uniqid('', true) . '|' . microtime());
    }
    /**
     * Returns name of class file, according to class name.
     *
     * @param string $classAlias Class name
     *
     * @return string
     */
    public function get_class_name($class_alias)
    {
        $class_name_provider = $this->get_class_name_provider();
        $class = $class_name_provider->get_class_name($class_alias);
        /**
         * Backwards compatibility for ox... classes,
         * when a class is instance build upon the unified namespace
         */
        if ($class == $class_alias) {
            $class_alias = $class_name_provider->get_class_alias_name($class);
        }
        return $this->get_module_chains_generator()->create_class_chain($class, $class_alias);
    }
    /**
     * Method returns class alias by given class name.
     *
     * @param string $className with namespace.
     *
     * @return string|null
     */
    public function get_class_alias_name($class_name): int|string|null
    {
        return $this->get_class_name_provider()->get_class_alias_name($class_name);
    }
    protected function get_class_name_provider(): \Oxid_Esales\Eshop_Community\Core\Backwards_Compatible_Class_Name_Provider
    {
        if (is_null($this->class_name_provider)) {
            $backwards_compatible_class_map = include 'Autoload/BackwardsCompatibilityClassMap.php';
            $this->class_name_provider = new Backwards_Compatible_Class_Name_Provider($backwards_compatible_class_map);
        }
        return $this->class_name_provider;
    }
    /**
     * @return ModuleChainsGenerator
     */
    protected function get_module_chains_generator()
    {
        if (is_null($this->module_chains_generator)) {
            $this->module_chains_generator = new \Oxid_Esales\Eshop\Core\Module\Module_Chains_Generator();
        }
        return $this->module_chains_generator;
    }
    protected function get_shop_id_calculator(): \Oxid_Esales\Eshop_Community\Core\Shop_Id_Calculator
    {
        if (is_null($this->shop_id_calculator)) {
            $this->shop_id_calculator = new Shop_Id_Calculator(new \Oxid_Esales\Eshop\Core\File_Cache(), new \Oxid_Esales\Eshop\Core\Utils_Server());
        }
        return $this->shop_id_calculator;
    }
    /**
     * Checks whether class with arguments should be cached.
     * Cache only when object has none or one scalar argument.
     *
     * @param string $className
     *
     */
    protected function should_cache_object($class_name, array $arguments): bool
    {
        return count($arguments) < 2 && (!isset($arguments[0]) || is_scalar($arguments[0]));
    }
}