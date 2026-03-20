<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Html;

use Symfony\Component\Html_Sanitizer\Html_Sanitizer_Config;
readonly class Html_Sanitizer_Config_Factory implements Html_Sanitizer_Config_Factory_Interface
{
    public function create(): Html_Sanitizer_Config
    {
        return (new Html_Sanitizer_Config())->allow_static_elements()->allow_relative_medias()->allow_relative_links()->allow_media_schemes(['http', 'https']);
    }
}