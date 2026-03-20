<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Env;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;
class Dotenv_Loader
{
    private string $env_key = 'OXID_ENV';
    private string $debug_key = 'OXID_DEBUG';
    private string $env_file = '.env';
    public function __construct(private readonly string $path_to_env_files)
    {
    }
    public function load_environment_variables(): void
    {
        $dot_env = new Dotenv($this->env_key, $this->debug_key);
        $dot_env->use_putenv()->load_env(Path::join($this->path_to_env_files, $this->env_file));
    }
}