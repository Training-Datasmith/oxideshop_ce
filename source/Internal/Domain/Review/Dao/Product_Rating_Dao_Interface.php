<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Product_Rating;
interface Product_Rating_Dao_Interface
{
    public function update(Product_Rating $product_rating);
    /**
     * @param string $productId
     *
     * @return ProductRating
     */
    public function get_product_rating_by_id($product_id);
}