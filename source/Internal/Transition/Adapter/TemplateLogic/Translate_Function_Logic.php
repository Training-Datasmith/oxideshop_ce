<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

use function is_array;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Exception\TranslationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Translator\TranslatorInterface;

use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class TranslateFunctionLogic
{
    /**
     * TranslateFunctionLogic constructor.
     */
    public function __construct(private readonly ContextInterface $context, private readonly TranslatorInterface $translator)
    {
    }

    public function getTranslation(array $params): string
    {
        $ident = $params['ident'] ?? 'IDENT MISSING';
        $suffix = $params['suffix'] ?? 'NO_SUFFIX';
        $translation = $ident;
        $suffixTranslation = $suffix;
        $translationFound = true;

        try {
            $translation = $this->translator->translate($ident);
            if ($this->isTranslatableSuffix($suffix)) {
                $suffixTranslation = $this->translator->translate($suffix);
            }
        } catch (TranslationNotFoundException) {
            $translationFound = false;
        }

        if (!$translationFound && isset($params['alternative'])) {
            $translation = $params['alternative'];
            $translationFound = true;
        }
        if ($translationFound) {
            $translation = $this->assignArgumentsToTranslation($translation, $params);
            if ($this->isTranslatableSuffix($suffix)) {
                $translation .= $suffixTranslation;
            }
        } elseif ($this->showError($params)) {
            $translation = sprintf(
                'ERROR: Translation for %s%s not found!',
                $ident,
                $this->isTranslatableSuffix($suffixTranslation) ? $suffixTranslation : ''
            );
        } else {
            Registry::getLogger()->warning(
                "translation for $ident not found"
            );
        }

        return $translation;
    }

    private function isTranslatableSuffix(string $suffix): bool
    {
        return !empty($suffix) && $suffix !== 'NO_SUFFIX';
    }

    private function assignArgumentsToTranslation(string $translation, array $params): string
    {
        if (isset($params['args']) && $params['args'] !== false) {
            return is_array($params['args']) ?
                vsprintf($translation, $params['args']) :
                sprintf($translation, $params['args']);
        }
        return $translation;
    }

    private function showError(array $params): bool
    {
        if (!$this->context->isAdmin() && $this->context->isShopInProductiveMode()) {
            return false;
        }
        return isset($params['noerror']) ? !$params['noerror'] : true;
    }
}
