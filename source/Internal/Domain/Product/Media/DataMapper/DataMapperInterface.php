<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
interface Data_Mapper_Interface
{
    public function to_data(Product_Media $product_media): array;
    public function from_data(array $data): Product_Media;
}