<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service\Product_Rating_Service_Interface;
class Product_Rating_Bridge implements Product_Rating_Bridge_Interface
{
    public function __construct(private readonly Product_Rating_Service_Interface $product_rating_service)
    {
    }
    /**
     * @param string $productId
     */
    public function update_product_rating($product_id): void
    {
        $this->product_rating_service->update_product_rating($product_id);
    }
}