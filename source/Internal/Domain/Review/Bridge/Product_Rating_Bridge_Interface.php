<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Product_Rating_Bridge_Interface
{
    /**
     * @param string $productId
     */
    public function update_product_rating($product_id);
}