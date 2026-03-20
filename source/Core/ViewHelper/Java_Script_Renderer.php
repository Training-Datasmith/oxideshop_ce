<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\View_Helper;

/**
 * Class for preparing JavaScript.
 */
class Java_Script_Renderer
{
    /**
     * Renders all registered JavaScript snippets and files.
     *
     * @param string $widget      Widget name
     * @param bool   $forceRender Force rendering of scripts.
     * @param bool   $isDynamic   Force rendering of scripts.
     */
    public function render($widget, $force_render, $is_dynamic = false): string
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $output = '';
        $suffix = $is_dynamic ? '_dynamic' : '';
        $files_parameter_name = \Oxid_Esales\Eshop\Core\View_Helper\Java_Script_Registrator::FILES_PARAMETER_NAME . $suffix;
        $scripts_parameter_name = \Oxid_Esales\Eshop\Core\View_Helper\Java_Script_Registrator::SNIPPETS_PARAMETER_NAME . $suffix;
        $is_ajax_request = $this->is_ajax_request();
        $force_render = $this->should_force_render($force_render, $is_ajax_request);
        if (!$widget || $force_render) {
            if (!$is_ajax_request) {
                $files = $this->prepare_files_for_rendering($config->get_global_parameter($files_parameter_name), $widget);
                $output .= $this->form_files_output($files, $widget);
                $config->set_global_parameter($files_parameter_name, null);
                if ($widget) {
                    $dynamic_includes = (array) $config->get_global_parameter(\Oxid_Esales\Eshop\Core\View_Helper\Java_Script_Registrator::FILES_PARAMETER_NAME . '_dynamic');
                    $output .= $this->form_files_output($dynamic_includes, $widget);
                    $config->set_global_parameter(\Oxid_Esales\Eshop\Core\View_Helper\Java_Script_Registrator::FILES_PARAMETER_NAME . '_dynamic', null);
                }
            }
            // Form output for adds.
            $snippets = (array) $config->get_global_parameter($scripts_parameter_name);
            $script_output = $this->form_snippets_output($snippets, $widget, $is_ajax_request);
            $config->set_global_parameter($scripts_parameter_name, null);
            if ($widget) {
                $dynamic_scripts = (array) $config->get_global_parameter(\Oxid_Esales\Eshop\Core\View_Helper\Java_Script_Registrator::SNIPPETS_PARAMETER_NAME . '_dynamic');
                $script_output .= $this->form_snippets_output($dynamic_scripts, $widget, $is_ajax_request);
                $config->set_global_parameter(\Oxid_Esales\Eshop\Core\View_Helper\Java_Script_Registrator::SNIPPETS_PARAMETER_NAME . '_dynamic', null);
            }
            $output .= $this->enclose($script_output, $widget, $is_ajax_request);
        }
        return $output;
    }
    /**
     * Returns if it is ajax request.
     */
    protected function is_ajax_request(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    /**
     * Returns whether rendering of scripts should be forced.
     *
     * @param bool $forceRender
     * @param bool $isAjaxRequest
     *
     * @return bool
     */
    protected function should_force_render($force_render, $is_ajax_request)
    {
        return $is_ajax_request ? true : $force_render;
    }
    /**
     * Returns files list for rendering.
     *
     * @param array  $files
     * @param string $widget
     */
    protected function prepare_files_for_rendering($files, $widget): array
    {
        return (array) $files;
    }
    /**
     * Form output for includes.
     *
     * @param array  $includes String files to include.
     * @param string $widget   Widget name.
     */
    protected function form_files_output($includes, $widget): string
    {
        if (!count($includes)) {
            return '';
        }
        ksort($includes);
        // Sort by priority.
        $used_sources = [];
        $widgets = [];
        $widget_template = "WidgetsHandler.registerFile('%s', '%s');";
        $script_template = '<script src="%s"></script>';
        foreach ($includes as $priority) {
            foreach ($priority as $source) {
                if (!in_array($source, $used_sources)) {
                    $widgets[] = sprintf($widget ? $widget_template : $script_template, $source, $widget);
                    $used_sources[] = $source;
                }
            }
        }
        $output = implode(PHP_EOL, $widgets);
        if ($widget && !empty($output)) {
            return <<<JS
            <script>
                window.addEventListener('load', function() {
                    {$output}
                }, false)
            </script>
            JS;
        }
        return $output;
    }
    /**
     * Forms how javascript should look like when output.
     * If varnish is active, javascript should be passed to WidgetsHandler instead of direct call.
     *
     * @param array  $scripts     Scripts to execute (from add).
     * @param string $widgetName  Widget name.
     * @param bool   $ajaxRequest Is ajax request.
     */
    protected function form_snippets_output($scripts, $widget_name, $ajax_request): string
    {
        $prepared_scripts = [];
        foreach ($scripts as $script) {
            if ($widget_name && !$ajax_request) {
                $sanitized_script = $this->sanitize($script);
                $script = "WidgetsHandler.registerFunction('{$sanitized_script}', '{$widget_name}');";
            }
            $prepared_scripts[] = $script;
        }
        return implode(PHP_EOL, $prepared_scripts);
    }
    /**
     * Sanitize javascript, which will be passed to WidgetsHandler.
     *
     * @param string $scripts
     */
    protected function sanitize($scripts): string
    {
        return strtr($scripts, ['\\' => '\\\\', "'" => "\\'", "\r" => '', "\n" => '\n']);
    }
    /**
     * Enclose with script tag or add in function for wiget.
     *
     * @param string $scriptsOutput javascript to be enclosed.
     * @param string $widget        widget name.
     * @param bool   $isAjaxRequest is ajax request
     *
     * @return string
     */
    protected function enclose($scripts_output, $widget, $is_ajax_request)
    {
        if ($scripts_output) {
            if ($widget && !$is_ajax_request) {
                $scripts_output = "window.addEventListener('load', function() { {$scripts_output} }, false )";
            }
            return "<script>{$scripts_output}</script>";
        }
    }
}