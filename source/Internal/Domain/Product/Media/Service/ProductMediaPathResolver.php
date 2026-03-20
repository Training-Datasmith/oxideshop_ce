<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Filesystem\Path;
readonly class Product_Media_Path_Resolver implements Product_Media_Path_Resolver_Interface
{
    public function __construct(private Context_Interface $context)
    {
    }
    public function resolve(string $product_id, string $filename): Media_Path
    {
        return new Media_Path(Path::join(Path::make_relative($this->context->get_out_path(), $this->context->get_source_path()), 'pictures', 'media', 'products', $product_id, $filename));
    }
}