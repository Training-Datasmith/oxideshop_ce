<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\View_Helper;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Core\Registry;
/**
 * Base class for preparing JavaScript and Stylesheets.
 */
abstract class Base_Registrator
{
    public const TAG_NAME = 'base';
    /** @var \OxidEsales\Eshop\Core\Config */
    protected $config;
    /**
     * BaseRegistrator constructor.
     *
     * provide config as class-property
     */
    public function __construct()
    {
        $this->config = Registry::get_config();
    }
    /**
     * Separate query part, appends query part if needed, append file modification timestamp.
     *
     * @param string $fullUrl
     *
     * @return string
     */
    protected function form_local_file_url($full_url)
    {
        $parts = explode('?', $full_url);
        $url = $parts[0];
        $parameters = $parts[1] ?? '';
        if (empty($parameters)) {
            if (preg_match('#^(https?:)?//#', $full_url) && Registry::get_utils_url()->is_current_shop_host($url)) {
                $path = $this->get_path_by_url($url);
            } else {
                $path = $this->config->get_resource_path($url, $this->config->is_admin());
                $url = $this->config->get_resource_url($url, $this->config->is_admin());
            }
            $parameters = $this->get_file_modification_time($path);
        }
        if (empty($url) && Container_Facade::get_parameter('oxid_esales.debug_mode')) {
            $error = '{' . static::TAG_NAME . '} resource not found: ' . \Oxid_Esales\Eshop\Core\Str::get_str()->htmlspecialchars($url);
            trigger_error($error, E_USER_WARNING);
        }
        return $url . ($parameters ? '?' . $parameters : '');
    }
    /**
     * Returns modification time for given file
     *
     * @param string $file path to file
     *
     * @return string UNIX-timestamp or empty string
     */
    protected function get_file_modification_time($file)
    {
        if (file_exists($file)) {
            return filemtime($file);
        }
        return '';
    }
    /**
     * get absolute path to file from url
     *
     * @param string $url url to file
     *
     * @return string path to file
     */
    protected function get_path_by_url($url)
    {
        $config = Registry::get_config();
        return str_replace(rtrim((string) $config->get_current_shop_url(false), '/'), rtrim((string) Container_Facade::get_parameter('oxid_esales.shop_source_directory'), '/'), $url);
    }
}