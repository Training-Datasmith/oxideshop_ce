<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Html;

readonly class Allow_All_Html_Sanitizer implements Html_Sanitizer_Interface
{
    public function sanitize(string $html): string
    {
        return $html;
    }
}