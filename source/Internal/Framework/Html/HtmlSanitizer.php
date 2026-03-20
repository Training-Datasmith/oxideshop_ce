<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Html;

use Symfony\Component\Html_Sanitizer\Html_Sanitizer_Interface as SymfonyHtmlSanitizerInterface;
readonly class Html_Sanitizer implements Html_Sanitizer_Interface
{
    public function __construct(private Symfony_Html_Sanitizer_Interface $sanitizer)
    {
    }
    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}