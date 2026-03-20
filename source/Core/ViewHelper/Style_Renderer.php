<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\View_Helper;

/**
 * Class for preparing Stylesheets.
 */
class Style_Renderer
{
    /**
     * @param string $widget
     * @param bool   $forceRender
     * @param bool   $isDynamic
     */
    public function render($widget, $force_render, $is_dynamic): string
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $suffix = $is_dynamic ? '_dynamic' : '';
        $output = '';
        if (!$widget || $this->should_force_render($force_render)) {
            $styles = (array) $config->get_global_parameter(\Oxid_Esales\Eshop\Core\View_Helper\Style_Registrator::STYLES_PARAMETER_NAME . $suffix);
            $output .= $this->form_styles_output($styles);
            $output .= PHP_EOL;
            $conditional_styles = (array) $config->get_global_parameter(\Oxid_Esales\Eshop\Core\View_Helper\Style_Registrator::CONDITIONAL_STYLES_PARAMETER_NAME . $suffix);
            $output .= $this->form_conditional_styles_output($conditional_styles);
        }
        return $output;
    }
    /**
     * Returns whether rendering of scripts should be forced.
     *
     * @param bool $forceRender
     *
     * @return bool
     */
    protected function should_force_render($force_render)
    {
        return $force_render;
    }
    /**
     * @param array $styles
     */
    protected function form_styles_output($styles): string
    {
        $prepared_styles = [];
        $template = '<link rel="stylesheet" type="text/css" href="%s" />';
        foreach ($styles as $style) {
            $prepared_styles[] = sprintf($template, $style);
        }
        return implode(PHP_EOL, $prepared_styles);
    }
    /**
     * @param array $styles
     */
    protected function form_conditional_styles_output($styles): string
    {
        $prepared_styles = [];
        $template = '<!--[if %s]><link rel="stylesheet" type="text/css" href="%s"><![endif]-->';
        foreach ($styles as $style => $condition) {
            $prepared_styles[] = sprintf($template, $condition, $style);
        }
        return implode(PHP_EOL, $prepared_styles);
    }
}