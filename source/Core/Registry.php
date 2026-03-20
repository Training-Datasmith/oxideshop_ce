<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop_Community\Core\Autoload\Backwards_Compatibility_Class_Map_Provider;
/**
 * Object registry design pattern implementation. Stores the instances of objects
 */
class Registry
{
    /**
     * Instance array
     *
     * @var array
     */
    protected static $instances = [];
    /**
     * Hold BC class to Unified Namespace class map
     *
     * @var null|array
     */
    protected static $backwards_compatibility_class_map;
    /**
     * Instance getter. Return an existing or new instance for a given class name.
     * Consider using the getter methods over the generic Registry::get() method.
     * In order to avoid issues with different shop editions, the given class name must be from the Unified Namespace.
     *
     * For reasons of backwards compatibility old class names like 'oxconfig' are still supported and equivalent
     * to the corresponding class name from the Unified Namespace, as they store and retrieve the same instances.
     * But be aware, that support for old class names will be dropped in the future.
     *
     * @template T
     * @param class-string<T> $className The class name from the Unified Namespace.
     * param mixed  ...$args   constructor arguments
     *
     * @static
     *
     * @return T
     */
    public static function get($class_name)
    {
        $key = self::get_storage_key($class_name);
        return self::get_object($key);
    }
    /**
     * Instance setter
     *
     * @param string       $className Class name
     * @param null|object  $instance  Object instance
     *
     * @static
     */
    public static function set($class_name, $instance): void
    {
        $key = self::get_storage_key($class_name);
        if (is_null($instance)) {
            unset(self::$instances[$key]);
            return;
        }
        self::$instances[$key] = $instance;
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\Config
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\Config
     */
    public static function get_config()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Config::class);
    }
    /**
     * Returns an instance of \OxidEsales\Eshop\Core\Session
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\Session
     */
    public static function get_session()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Session::class);
    }
    /**
     * Returns an instance of \OxidEsales\Eshop\Core\Language
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\Language
     */
    public static function get_lang()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Language::class);
    }
    /**
     * Returns an instance of \OxidEsales\Eshop\Core\Utils
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\Utils
     */
    public static function get_utils()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils::class);
    }
    /**
     * Returns an instance of OxidEsales\Eshop\Core\UtilsObject
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsObject
     */
    public static function get_utils_object()
    {
        return \Oxid_Esales\Eshop\Core\Utils_Object::get_instance();
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\InputValidator
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\InputValidator
     */
    public static function get_input_validator()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Input_Validator::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\PictureHandler
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\PictureHandler
     */
    public static function get_picture_handler()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Picture_Handler::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\Request
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\Request
     */
    public static function get_request()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Request::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\SeoEncoder
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\SeoEncoder
     */
    public static function get_seo_encoder()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Seo_Encoder::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\SeoDecoder
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\SeoDecoder
     */
    public static function get_seo_decoder()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Seo_Decoder::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsCount
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsCount
     */
    public static function get_utils_count()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_Count::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsDate
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsDate
     */
    public static function get_utils_date()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_Date::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsFile
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsFile
     */
    public static function get_utils_file()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_File::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsPic
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsPic
     */
    public static function get_utils_pic()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_Pic::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsServer
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsServer
     */
    public static function get_utils_server()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_Server::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsString
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsString
     */
    public static function get_utils_string()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_String::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsUrl
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsUrl
     */
    public static function get_utils_url()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_Url::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsXml
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsXml
     */
    public static function get_utils_xml()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_Xml::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\UtilsView
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\UtilsView
     */
    public static function get_utils_view()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Utils_View::class);
    }
    /**
     * Return an instance of \OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver
     *
     * @static
     *
     * @return \OxidEsales\Eshop\Core\Routing\ControllerClassNameResolver
     */
    public static function get_controller_class_name_resolver()
    {
        return self::get_object(\Oxid_Esales\Eshop\Core\Routing\Controller_Class_Name_Resolver::class);
    }
    /**
     * Returns Logger
     *
     * @static
     * @return \Psr\Log\LoggerInterface
     */
    public static function get_logger()
    {
        if (!self::instance_exists('logger')) {
            self::set('logger', get_logger());
        }
        return self::get('logger');
    }
    /**
     * Return all class instances, which are currently set in the registry
     */
    public static function get_keys(): array
    {
        return array_keys(self::$instances);
    }
    /**
     * Check if an instance of a given class is set in the registry
     *
     * @param string $className
     */
    public static function instance_exists($class_name): bool
    {
        $key = self::get_storage_key($class_name);
        return isset(self::$instances[$key]);
    }
    /**
     * Get backwardsCompatibilityClassMap
     *
     * @return array
     */
    public static function get_backwards_compatibility_class_map()
    {
        if (is_null(self::$backwards_compatibility_class_map)) {
            $class_map = (new Backwards_Compatibility_Class_Map_Provider())->get_map();
            self::$backwards_compatibility_class_map = $class_map;
        }
        return self::$backwards_compatibility_class_map;
    }
    /**
     * Translate a given old class name like 'oxconfig' into a storage key as known by the Registry.
     * If a new class name is used, the method just returns it as it is.
     *
     * @param string $className Class name to be converted.
     *
     * @return string
     */
    public static function get_storage_key($class_name)
    {
        $key = $class_name;
        if (!\Oxid_Esales\Eshop\Core\Namespace_Information_Provider::is_namespaced_class($class_name)) {
            $bc_map = self::get_backwards_compatibility_class_map();
            $key = $bc_map[strtolower($key)] ?? strtolower($key);
        }
        return $key;
    }
    /**
     * Special case handling: The recommended way to get an instance of UtilsObject is to use Registry::getUtilsObject
     * IMPORTANT: UtilsObject is not delivered from Registry::instances this way, so Registry::set
     *            will have no effect on which UtilsObject is delivered.
     *            Also Registry::instanceExists will always return false for UtilsObject.
     * This does only affect BC class name and unified namespace class names, not the edition own classes atm.
     *
     * @param string $className Class name.
     *
     * @return object
     */
    protected static function create_object($class_name)
    {
        if ('oxutilsobject' === strtolower($class_name) || \Oxid_Esales\Eshop\Core\Utils_Object::class === $class_name) {
            return \Oxid_Esales\Eshop\Core\Utils_Object::get_instance();
        }
        return ox_new($class_name);
    }
    /**
     * Return a well known object from the registry
     *
     * @template T
     * @param class-string<T> $className A unified namespace class name
     * param mixed  ...$args   constructor arguments
     *
     * @static
     *
     * @return T
     */
    protected static function get_object($class_name)
    {
        if (!isset(self::$instances[$class_name])) {
            self::$instances[$class_name] = self::create_object($class_name);
        }
        return self::$instances[$class_name];
    }
}