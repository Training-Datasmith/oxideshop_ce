<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Review;
interface Review_Dao_Interface
{
    /**
     * Returns User Reviews.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function get_reviews_by_user_id($user_id);
    public function delete(Review $review);
}