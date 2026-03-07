<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\ViewHelper;

use OxidEsales\Eshop\Core\Registry;

/**
 * Class for preparing Stylesheets.
 */
class StyleRegistrator extends BaseRegistrator
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
    public function addFile($style, $condition, $isDynamic): void
    {
        $suffix = $isDynamic ? '_dynamic' : '';

        if (!preg_match('#^https?://#', $style) || Registry::getUtilsUrl()->isCurrentShopHost($style)) {
            $style = $this->formLocalFileUrl($style);
        }

        if ($style) {
            if (!empty($condition)) {
                $conditionalStylesParameterName = static::CONDITIONAL_STYLES_PARAMETER_NAME . $suffix;
                $conditionalStyles = (array) $this->config->getGlobalParameter($conditionalStylesParameterName);
                $conditionalStyles[$style] = $condition;
                $this->config->setGlobalParameter($conditionalStylesParameterName, $conditionalStyles);
            } else {
                $stylesParameterName = static::STYLES_PARAMETER_NAME . $suffix;
                $styles = (array) $this->config->getGlobalParameter($stylesParameterName);
                $styles[] = $style;
                $styles = array_unique($styles);
                $this->config->setGlobalParameter($stylesParameterName, $styles);
            }
        }
    }
}
