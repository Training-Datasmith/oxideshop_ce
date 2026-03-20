<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Generates view name for given table name.
 */
class Table_View_Name_Generator
{
    /** @var \OxidEsales\Eshop\Core\Config */
    private $config;
    /** @var \OxidEsales\Eshop\Core\Language */
    private $language;
    /**
     * @param \OxidEsales\Eshop\Core\Config   $config
     * @param \OxidEsales\Eshop\Core\Language $language
     */
    public function __construct($config = null, $language = null)
    {
        if (!$config) {
            $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        }
        $this->config = $config;
        if (!$language) {
            $language = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        }
        $this->language = $language;
    }
    /**
     * Return the view name of the given table if a view exists, otherwise the table name itself.
     * Views usage can be disabled with blSkipViewUsage config option in case admin area is not reachable
     * due to broken views, so that they could be regenerated.
     *
     * @param string $table      Table name
     * @param int    $languageId Language id [optional]
     * @param int    $shopId     Shop id, otherwise config->getShopId() is used [optional]
     *
     * @return string
     */
    public function get_view_name($table, $language_id = null, $shop_id = null)
    {
        $config = $this->get_config();
        if (!Container_Facade::get_parameter('oxid_esales.skip_database_views_usage')) {
            $language = $this->get_language();
            $language_id ??= $language->get_base_language();
            $shop_id ??= $config->get_shop_id();
            $is_multi_lang = in_array($table, $language->get_multi_lang_tables());
            $view_suffix = $this->get_view_suffix($table, $language_id, $shop_id, $is_multi_lang);
            if ($view_suffix || ($language_id == -1 || $shop_id == -1) && $is_multi_lang) {
                return "oxv_{$table}{$view_suffix}";
            }
        }
        return $table;
    }
    /**
     * Generates view suffix.
     *
     * @param string $table
     * @param int    $languageId
     * @param int    $shopId
     * @param bool   $isMultiLang
     */
    protected function get_view_suffix($table, $language_id, $shop_id, $is_multi_lang): string
    {
        $view_suffix = '';
        if ($language_id != -1 && $is_multi_lang) {
            $language_abbreviation = $this->get_language()->get_language_abbr($language_id);
            $view_suffix .= "_{$language_abbreviation}";
        }
        return $view_suffix;
    }
    /**
     * @return \OxidEsales\Eshop\Core\Config
     */
    protected function get_config()
    {
        return $this->config;
    }
    /**
     * @return \OxidEsales\Eshop\Core\Language
     */
    protected function get_language()
    {
        return $this->language;
    }
}