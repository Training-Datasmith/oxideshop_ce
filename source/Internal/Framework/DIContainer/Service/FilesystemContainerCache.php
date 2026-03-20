<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Psr\Container\Container_Interface;
use Symfony\Component\Dependency_Injection\Container_Builder;
use Symfony\Component\Dependency_Injection\Dumper\Php_Dumper;
use Symfony\Component\Filesystem\Filesystem;
class Filesystem_Container_Cache implements Container_Cache_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context, private readonly Filesystem $filesystem)
    {
    }
    public function put(Container_Builder $container, int $shop_id): void
    {
        $dumper = new Php_Dumper($container);
        $this->filesystem->dump_file($this->context->get_container_cache_file_path($shop_id), $dumper->dump());
    }
    /**
     * @psalm-suppress UndefinedClass
     */
    public function get(int $shop_id): Container_Interface
    {
        include_once $this->context->get_container_cache_file_path($shop_id);
        return new \Project_Service_Container();
    }
    public function exists(int $shop_id): bool
    {
        $path = $this->context->get_container_cache_file_path($shop_id);
        return $this->filesystem->exists($path);
    }
    public function invalidate(int $shop_id): void
    {
        if ($this->filesystem->exists($this->context->get_container_cache_file_path($shop_id))) {
            $this->filesystem->remove($this->context->get_container_cache_file_path($shop_id));
        }
    }
}