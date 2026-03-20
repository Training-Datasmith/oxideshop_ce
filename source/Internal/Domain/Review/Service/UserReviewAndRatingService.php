<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\View_Data_Object\Review_And_Rating;
class User_Review_And_Rating_Service implements User_Review_And_Rating_Service_Interface
{
    public function __construct(private readonly User_Review_Service_Interface $user_review_service, private readonly User_Rating_Service_Interface $user_rating_service, private readonly Review_And_Rating_Merging_Service_Interface $review_and_rating_merging_service)
    {
    }
    /**
     * Get number of reviews by given user.
     *
     * @param string $userId
     *
     * @return int
     */
    public function get_review_and_rating_list_count($user_id)
    {
        return $this->get_merged_review_and_rating_list($user_id)->count();
    }
    /**
     * Returns Collection of User Ratings and Reviews.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function get_review_and_rating_list($user_id)
    {
        $review_and_rating_list = $this->get_merged_review_and_rating_list($user_id);
        return $this->sort_review_and_rating_list($review_and_rating_list);
    }
    /**
     * Returns merged Rating and Review.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    private function get_merged_review_and_rating_list($user_id)
    {
        $reviews = $this->user_review_service->get_reviews($user_id);
        $ratings = $this->user_rating_service->get_ratings($user_id);
        return $this->review_and_rating_merging_service->merge_review_and_rating($reviews, $ratings);
    }
    /**
     * Sorts ReviewAndRating list.
     *
     *
     * @return ArrayCollection
     */
    private function sort_review_and_rating_list(Array_Collection $review_and_rating_list)
    {
        $review_and_rating_list_array = $review_and_rating_list->to_array();
        usort($review_and_rating_list_array, fn(Review_And_Rating $first, Review_And_Rating $second): int => $first->get_created_at() < $second->get_created_at() ? 1 : -1);
        return new Array_Collection($review_and_rating_list_array);
    }
}