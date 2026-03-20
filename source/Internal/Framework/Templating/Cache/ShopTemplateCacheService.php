<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Cache;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Shop_Template_Cache_Service implements Shop_Template_Cache_Service_Interface
{
    public function __construct(private readonly Context_Interface $context, private readonly Filesystem $filesystem, private readonly string $compilation_directory)
    {
    }
    public function get_cache_directory(int $shop_id): string
    {
        return Path::join($this->compilation_directory, 'template_cache', 'shops', (string) $shop_id);
    }
    public function invalidate_cache(int $shop_id): void
    {
        if ($this->filesystem->exists($this->get_cache_directory($shop_id))) {
            $this->filesystem->remove($this->get_cache_directory($shop_id));
        }
    }
    public function invalidate_all_shops_cache(): void
    {
        $shops = $this->context->get_all_shop_ids();
        foreach ($shops as $shop) {
            $this->invalidate_cache($shop);
        }
    }
}