<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Product_Rating;
class Product_Rating_Data_Mapper implements Product_Rating_Data_Mapper_Interface
{
    public function map(Product_Rating $product_rating, array $data): Product_Rating
    {
        $product_rating->set_product_id($data['OXID'])->set_rating_average($data['OXRATING'])->set_rating_count($data['OXRATINGCNT']);
        return $product_rating;
    }
    public function get_data(Product_Rating $product_rating): array
    {
        return ['OXID' => $product_rating->get_product_id(), 'OXRATING' => $product_rating->get_rating_average(), 'OXRATINGCNT' => $product_rating->get_rating_count()];
    }
    public function get_primary_key(Product_Rating $product_rating): array
    {
        return ['OXID' => $product_rating->get_product_id()];
    }
}