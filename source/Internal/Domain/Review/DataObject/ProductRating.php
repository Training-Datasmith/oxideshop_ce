<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object;

class Product_Rating
{
    /**
     * @var string
     */
    private $product_id;
    /**
     * @var float
     */
    private $rating_average;
    /**
     * @var int
     */
    private $rating_count;
    /**
     * @return string
     */
    public function get_product_id()
    {
        return $this->product_id;
    }
    /**
     * @param string $productId
     *
     * @return $this
     */
    public function set_product_id($product_id): static
    {
        $this->product_id = $product_id;
        return $this;
    }
    /**
     * @return float
     */
    public function get_rating_average()
    {
        return $this->rating_average;
    }
    /**
     * @param float $ratingAverage
     *
     * @return $this
     */
    public function set_rating_average($rating_average): static
    {
        $this->rating_average = $rating_average;
        return $this;
    }
    /**
     * @return int
     */
    public function get_rating_count()
    {
        return $this->rating_count;
    }
    /**
     * @param int $ratingCount
     *
     * @return $this
     */
    public function set_rating_count($rating_count): static
    {
        $this->rating_count = $rating_count;
        return $this;
    }
}