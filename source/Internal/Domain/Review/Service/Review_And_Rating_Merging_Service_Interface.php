<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
interface Review_And_Rating_Merging_Service_Interface
{
    /**
     * Merges Reviews and Ratings to Collection of ReviewAndRating view objects.
     *
     *
     * @return ArrayCollection
     */
    public function merge_review_and_rating(Array_Collection $reviews, Array_Collection $ratings);
}