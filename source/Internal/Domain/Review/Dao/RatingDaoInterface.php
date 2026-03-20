<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Rating;
interface Rating_Dao_Interface
{
    /**
     * Returns User Ratings.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function get_ratings_by_user_id($user_id);
    /**
     * Returns Ratings for a product.
     *
     * @param string $productId
     *
     * @return ArrayCollection
     */
    public function get_ratings_by_product_id($product_id);
    public function delete(Rating $rating);
}