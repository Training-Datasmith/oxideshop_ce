<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

interface Product_Rating_Service_Interface
{
    /**
     * @param string $productId
     */
    public function update_product_rating($product_id);
}