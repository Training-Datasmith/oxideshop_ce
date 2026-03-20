<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\View_Helper;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class for preparing Stylesheets.
 */
class Style_Registrator extends Base_Registrator
{
    public const CONDITIONAL_STYLES_PARAMETER_NAME = 'conditional_styles';
    public const STYLES_PARAMETER_NAME = 'styles';
    public const TAG_NAME = 'oxstyle';
    /**
     * Separate query part #3305.
     *
     * @param string $style
     * @param string $condition
     * @param bool   $isDynamic
     */
    public function add_file($style, $condition, $is_dynamic): void
    {
        $suffix = $is_dynamic ? '_dynamic' : '';
        if (!preg_match('#^https?://#', $style) || Registry::get_utils_url()->is_current_shop_host($style)) {
            $style = $this->form_local_file_url($style);
        }
        if ($style) {
            if (!empty($condition)) {
                $conditional_styles_parameter_name = static::CONDITIONAL_STYLES_PARAMETER_NAME . $suffix;
                $conditional_styles = (array) $this->config->get_global_parameter($conditional_styles_parameter_name);
                $conditional_styles[$style] = $condition;
                $this->config->set_global_parameter($conditional_styles_parameter_name, $conditional_styles);
            } else {
                $styles_parameter_name = static::STYLES_PARAMETER_NAME . $suffix;
                $styles = (array) $this->config->get_global_parameter($styles_parameter_name);
                $styles[] = $style;
                $styles = array_unique($styles);
                $this->config->set_global_parameter($styles_parameter_name, $styles);
            }
        }
    }
}