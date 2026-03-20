<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Exception\TranslationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Translator\TranslatorInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class TranslateFilterLogic
{
    /**
     * TranslateFilterLogic constructor.
     */
    public function __construct(private readonly ContextInterface $context, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param mixed  $args
     *
     */
    public function multiLang(string $ident, $args = []): string
    {
        $ident ??= 'IDENT MISSING';

        $translation = $ident;
        $translationFound = true;

        try {
            $translation = $this->translator->translate($ident);
        } catch (TranslationNotFoundException) {
            $translationFound = false;
        }

        if ($translationFound) {
            $translation = $this->assignArgumentsToTranslation($translation, $args);
        } elseif (!$this->context->isShopInProductiveMode()) {
            $translation = 'ERROR: Translation for ' . $ident . ' not found!';
        }

        return $translation;
    }

    /**
     * @param mixed  $args
     */
    private function assignArgumentsToTranslation(string $translation, $args): string
    {
        if ($args) {
            if (is_array($args)) {
                $translation = vsprintf($translation, $args);
            } else {
                $translation = sprintf($translation, $args);
            }
        }
        return $translation;
    }
}
