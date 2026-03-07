<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge;

use OxidEsales\EshopCommunity\Internal\Domain\Review\Service\ProductRatingServiceInterface;

class ProductRatingBridge implements ProductRatingBridgeInterface
{
    public function __construct(private readonly ProductRatingServiceInterface $productRatingService)
    {
    }

    /**
     * @param string $productId
     */
    public function updateProductRating($productId): void
    {
        $this->productRatingService->updateProductRating($productId);
    }
}
