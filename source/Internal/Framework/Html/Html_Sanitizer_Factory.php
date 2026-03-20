<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Html;

readonly class Html_Sanitizer_Factory
{
    public function __construct(private bool $enabled, private Html_Sanitizer_Interface $sanitizer, private Html_Sanitizer_Interface $allow_all_sanitizer)
    {
    }
    public function create(): Html_Sanitizer_Interface
    {
        if ($this->enabled) {
            return $this->sanitizer;
        }
        return $this->allow_all_sanitizer;
    }
}