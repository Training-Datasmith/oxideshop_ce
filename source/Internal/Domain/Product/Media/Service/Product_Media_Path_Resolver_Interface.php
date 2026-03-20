<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
interface Product_Media_Path_Resolver_Interface
{
    public function resolve(string $product_id, string $filename): Media_Path;
}