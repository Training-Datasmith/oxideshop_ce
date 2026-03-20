<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Exception\TranslationNotFoundException;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Translator\TranslatorInterface;

class TranslateSalutationLogic
{
    /**
     * TranslateSalutationLogic constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function translateSalutation(string $ident = ''): string
    {
        $translation = $ident;
        try {
            $translation = $this->translator->translate($ident);
        } catch (TranslationNotFoundException) {
        }

        return $translation;
    }
}
