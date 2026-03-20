<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Env;

use Symfony\Component\Filesystem\Path;
class Env_Url_Formatter
{
    public static function to_env_url(string $url): string
    {
        return sprintf('%s.%s', Path::canonicalize($url), getenv('OXID_ENV'));
    }
}