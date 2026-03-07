<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\ViewHelper;

use OxidEsales\Eshop\Core\Registry;

/**
 * Class for preparing JavaScript.
 */
class JavaScriptRegistrator extends BaseRegistrator
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
    public function addSnippet($script, $isDynamic = false): void
    {
        $suffix = $isDynamic ? '_dynamic' : '';
        $scriptsParameterName = static::SNIPPETS_PARAMETER_NAME . $suffix;
        $scripts = (array) $this->config->getGlobalParameter($scriptsParameterName);
        $script = trim($script);
        if (!in_array($script, $scripts)) {
            $scripts[] = $script;
        }
        $this->config->setGlobalParameter($scriptsParameterName, $scripts);
    }

    /**
     * Register JavaScript file (local or remote) for rendering.
     *
     * @param string $file
     * @param int    $priority
     * @param bool   $isDynamic
     */
    public function addFile($file, $priority, $isDynamic = false): void
    {
        $suffix = $isDynamic ? '_dynamic' : '';
        $filesParameterName = static::FILES_PARAMETER_NAME . $suffix;
        $includes = (array) $this->config->getGlobalParameter($filesParameterName);

        if (!preg_match('#^https?://#', $file) || Registry::getUtilsUrl()->isCurrentShopHost($file)) {
            $file = $this->formLocalFileUrl($file);
        }

        if ($file) {
            $includes[$priority][] = $file;
            $includes[$priority] = array_unique($includes[$priority]);
            $this->config->setGlobalParameter($filesParameterName, $includes);
        }
    }
}
