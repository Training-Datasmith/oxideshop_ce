<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\View_Helper;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class for preparing JavaScript.
 */
class Java_Script_Registrator extends Base_Registrator
{
    public const SNIPPETS_PARAMETER_NAME = 'scripts';
    public const FILES_PARAMETER_NAME = 'includes';
    public const TAG_NAME = 'oxscript';
    /**
     * Register JavaScript code snippet for rendering.
     *
     * @param string $script
     * @param bool   $isDynamic
     */
    public function add_snippet($script, $is_dynamic = false): void
    {
        $suffix = $is_dynamic ? '_dynamic' : '';
        $scripts_parameter_name = static::SNIPPETS_PARAMETER_NAME . $suffix;
        $scripts = (array) $this->config->get_global_parameter($scripts_parameter_name);
        $script = trim($script);
        if (!in_array($script, $scripts)) {
            $scripts[] = $script;
        }
        $this->config->set_global_parameter($scripts_parameter_name, $scripts);
    }
    /**
     * Register JavaScript file (local or remote) for rendering.
     *
     * @param string $file
     * @param int    $priority
     * @param bool   $isDynamic
     */
    public function add_file($file, $priority, $is_dynamic = false): void
    {
        $suffix = $is_dynamic ? '_dynamic' : '';
        $files_parameter_name = static::FILES_PARAMETER_NAME . $suffix;
        $includes = (array) $this->config->get_global_parameter($files_parameter_name);
        if (!preg_match('#^https?://#', $file) || Registry::get_utils_url()->is_current_shop_host($file)) {
            $file = $this->form_local_file_url($file);
        }
        if ($file) {
            $includes[$priority][] = $file;
            $includes[$priority] = array_unique($includes[$priority]);
            $this->config->set_global_parameter($files_parameter_name, $includes);
        }
    }
}