<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject;

class ProductRating
{
    /**
     * @var string
     */
    private $productId;

    /**
     * @var float
     */
    private $ratingAverage;

    /**
     * @var int
     */
    private $ratingCount;

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     *
     * @return $this
     */
    public function setProductId($productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @return float
     */
    public function getRatingAverage()
    {
        return $this->ratingAverage;
    }

    /**
     * @param float $ratingAverage
     *
     * @return $this
     */
    public function setRatingAverage($ratingAverage): static
    {
        $this->ratingAverage = $ratingAverage;

        return $this;
    }

    /**
     * @return int
     */
    public function getRatingCount()
    {
        return $this->ratingCount;
    }

    /**
     * @param int $ratingCount
     *
     * @return $this
     */
    public function setRatingCount($ratingCount): static
    {
        $this->ratingCount = $ratingCount;

        return $this;
    }
}
