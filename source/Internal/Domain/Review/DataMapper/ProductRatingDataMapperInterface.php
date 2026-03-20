<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Product_Rating;
interface Product_Rating_Data_Mapper_Interface
{
    public function map(Product_Rating $product_rating, array $data): Product_Rating;
    public function get_data(Product_Rating $product_rating): array;
    public function get_primary_key(Product_Rating $product_rating): array;
}