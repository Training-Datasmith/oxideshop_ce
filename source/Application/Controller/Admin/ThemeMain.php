<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
class Theme_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $sox_id = $this->get_edit_object_id();
        $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
        if (!$sox_id) {
            $sox_id = $o_theme->get_active_theme_id();
        }
        if ($o_theme->load($sox_id)) {
            $this->_a_view_data['oTheme'] = $o_theme;
        } else {
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display(ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class, 'EXCEPTION_THEME_NOT_LOADED'));
        }
        parent::render();
        if ($this->theme_in_config_file()) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('EXCEPTION_THEME_SHOULD_BE_ONLY_IN_DATABASE');
        }
        return 'theme_main';
    }
    /**
     * Check if theme config is in config file.
     *
     * @return bool
     */
    public function theme_in_config_file()
    {
        $bl_theme_set = isset(\Oxid_Esales\Eshop\Core\Registry::get_config()->s_theme);
        $bl_custom_theme_set = isset(\Oxid_Esales\Eshop\Core\Registry::get_config()->s_custom_theme);
        return $bl_theme_set || $bl_custom_theme_set;
    }
    /**
     * Set theme
     */
    public function set_theme(): void
    {
        $s_theme = $this->get_edit_object_id();
        /** @var \OxidEsales\Eshop\Core\Theme $oTheme */
        $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
        if (!$o_theme->load($s_theme)) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display(ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class, 'EXCEPTION_THEME_NOT_LOADED'));
            return;
        }
        try {
            $o_theme->activate();
            $this->reset_content_cache();
        } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
            Registry::get_utils_view()->add_error_to_display($exception);
            Registry::get_logger()->error($exception->get_message(), [$exception]);
        }
    }
}