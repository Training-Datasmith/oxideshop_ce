<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Rating;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Review;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\View_Data_Object\Review_And_Rating;
class Review_And_Rating_Merging_Service implements Review_And_Rating_Merging_Service_Interface
{
    /**
     * Merges Reviews and Ratings to Collection of ReviewAndRating view objects.
     *
     */
    public function merge_review_and_rating(Array_Collection $reviews, Array_Collection $ratings): \Doctrine\Common\Collections\Array_Collection
    {
        $rating_and_review_list = array_merge($this->get_review_data_with_rating($reviews, $ratings), $this->get_rating_without_review_data($reviews, $ratings));
        return $this->map_review_and_rating_list($rating_and_review_list);
    }
    private function get_review_data_with_rating(Array_Collection $reviews, Array_Collection $ratings): array
    {
        $review_list = [];
        foreach ($reviews as $review) {
            $rating_and_review = ['reviewId' => $review->get_id(), 'text' => $review->get_text(), 'createdAt' => $review->get_created_at(), 'objectId' => $review->get_object_id(), 'objectType' => $review->get_type(), 'rating' => false, 'ratingId' => false];
            foreach ($ratings as $rating) {
                if ($this->is_review_rating($review, $rating)) {
                    $rating_and_review['rating'] = $rating->get_rating();
                    $rating_and_review['ratingId'] = $rating->get_id();
                    break;
                }
            }
            $review_list[] = $rating_and_review;
        }
        return $review_list;
    }
    private function get_rating_without_review_data(Array_Collection $reviews, Array_Collection $ratings): array
    {
        $rating_list = [];
        foreach ($ratings as $rating) {
            if ($this->is_rating_without_review($rating, $reviews)) {
                $rating_list[] = ['ratingId' => $rating->get_id(), 'reviewId' => false, 'rating' => $rating->get_rating(), 'text' => '', 'objectId' => $rating->get_object_id(), 'objectType' => $rating->get_type(), 'createdAt' => $rating->get_created_at()];
            }
        }
        return $rating_list;
    }
    /**
     * Returns true if Rating doesn't belong to any review.
     *
     *
     * @return bool
     */
    private function is_rating_without_review(Rating $rating, Array_Collection $reviews)
    {
        $without_review = true;
        foreach ($reviews as $review) {
            if ($this->is_review_rating($review, $rating)) {
                $without_review = false;
                break;
            }
        }
        return $without_review;
    }
    /**
     * Returns true if Rating belongs to Review.
     *
     *
     */
    private function is_review_rating(Review $review, Rating $rating): bool
    {
        return $rating->get_type() === $review->get_type() && $rating->get_object_id() === $review->get_object_id() && $rating->get_rating() === $review->get_rating() && $rating->get_user_id() === $review->get_user_id();
    }
    /**
     * Maps Reviews and Ratings data to Collection of ReviewAndRating view objects.
     *
     *
     */
    private function map_review_and_rating_list(array $review_and_rating_data_list): \Doctrine\Common\Collections\Array_Collection
    {
        $mapped_review_and_rating = new Array_Collection();
        foreach ($review_and_rating_data_list as $review_and_rating_data) {
            $mapped_review_and_rating[] = $this->map_review_and_rating($review_and_rating_data);
        }
        return $mapped_review_and_rating;
    }
    /**
     * Maps Review and Rating data to ReviewAndRating view object.
     *
     *
     */
    private function map_review_and_rating(array $review_and_rating_data): \Oxid_Esales\Eshop_Community\Internal\Domain\Review\View_Data_Object\Review_And_Rating
    {
        $review_and_rating = new Review_And_Rating();
        $review_and_rating->set_review_id($review_and_rating_data['reviewId'])->set_rating_id($review_and_rating_data['ratingId'])->set_rating($review_and_rating_data['rating'])->set_review_text($review_and_rating_data['text'])->set_object_id($review_and_rating_data['objectId'])->set_object_type($review_and_rating_data['objectType'])->set_created_at($review_and_rating_data['createdAt']);
        return $review_and_rating;
    }
}