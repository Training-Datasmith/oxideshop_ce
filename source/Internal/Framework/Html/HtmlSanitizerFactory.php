<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Html;

readonly class HtmlSanitizerFactory
{
    public function __construct(
        private bool $enabled,
        private HtmlSanitizerInterface $sanitizer,
        private HtmlSanitizerInterface $allowAllSanitizer,
    ) {
    }

    public function create(): HtmlSanitizerInterface
    {
        if ($this->enabled) {
            return $this->sanitizer;
        }

        return $this->allowAllSanitizer;
    }
}
