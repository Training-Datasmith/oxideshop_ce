<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Review;
use OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating;

class ReviewAndRatingMergingService implements ReviewAndRatingMergingServiceInterface
{
    /**
     * Merges Reviews and Ratings to Collection of ReviewAndRating view objects.
     *
     *
     * @return ArrayCollection
     */
    public function mergeReviewAndRating(ArrayCollection $reviews, ArrayCollection $ratings)
    {
        $ratingAndReviewList = array_merge(
            $this->getReviewDataWithRating($reviews, $ratings),
            $this->getRatingWithoutReviewData($reviews, $ratings)
        );

        return $this->mapReviewAndRatingList($ratingAndReviewList);
    }

    private function getReviewDataWithRating(ArrayCollection $reviews, ArrayCollection $ratings): array
    {
        $reviewList = [];

        foreach ($reviews as $review) {
            $ratingAndReview = [
                'reviewId'      => $review->getId(),
                'text'          => $review->getText(),
                'createdAt'     => $review->getCreatedAt(),
                'objectId'      => $review->getObjectId(),
                'objectType'    => $review->getType(),
                'rating'        => false,
                'ratingId'      => false,
            ];

            foreach ($ratings as $rating) {
                if ($this->isReviewRating($review, $rating)) {
                    $ratingAndReview['rating'] = $rating->getRating();
                    $ratingAndReview['ratingId'] = $rating->getId();

                    break;
                }
            }

            $reviewList[] = $ratingAndReview;
        }

        return $reviewList;
    }

    private function getRatingWithoutReviewData(ArrayCollection $reviews, ArrayCollection $ratings): array
    {
        $ratingList = [];

        foreach ($ratings as $rating) {
            if ($this->isRatingWithoutReview($rating, $reviews)) {
                $ratingList[] = [
                    'ratingId'      => $rating->getId(),
                    'reviewId'      => false,
                    'rating'        => $rating->getRating(),
                    'text'          => '',
                    'objectId'      => $rating->getObjectId(),
                    'objectType'    => $rating->getType(),
                    'createdAt'     => $rating->getCreatedAt(),
                ];
            }
        }

        return $ratingList;
    }

    /**
     * Returns true if Rating doesn't belong to any review.
     *
     *
     * @return bool
     */
    private function isRatingWithoutReview(Rating $rating, ArrayCollection $reviews)
    {
        $withoutReview = true;

        foreach ($reviews as $review) {
            if ($this->isReviewRating($review, $rating)) {
                $withoutReview = false;
                break;
            }
        }

        return $withoutReview;
    }

    /**
     * Returns true if Rating belongs to Review.
     *
     *
     */
    private function isReviewRating(Review $review, Rating $rating): bool
    {
        return $rating->getType() === $review->getType()
            && $rating->getObjectId() === $review->getObjectId()
            && $rating->getRating() === $review->getRating()
            && $rating->getUserId() === $review->getUserId();
    }

    /**
     * Maps Reviews and Ratings data to Collection of ReviewAndRating view objects.
     *
     *
     */
    private function mapReviewAndRatingList(array $reviewAndRatingDataList): \Doctrine\Common\Collections\ArrayCollection
    {
        $mappedReviewAndRating = new ArrayCollection();

        foreach ($reviewAndRatingDataList as $reviewAndRatingData) {
            $mappedReviewAndRating[] = $this->mapReviewAndRating($reviewAndRatingData);
        }

        return $mappedReviewAndRating;
    }

    /**
     * Maps Review and Rating data to ReviewAndRating view object.
     *
     *
     */
    private function mapReviewAndRating(array $reviewAndRatingData): \OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject\ReviewAndRating
    {
        $reviewAndRating = new ReviewAndRating();
        $reviewAndRating
            ->setReviewId($reviewAndRatingData['reviewId'])
            ->setRatingId($reviewAndRatingData['ratingId'])
            ->setRating($reviewAndRatingData['rating'])
            ->setReviewText($reviewAndRatingData['text'])
            ->setObjectId($reviewAndRatingData['objectId'])
            ->setObjectType($reviewAndRatingData['objectType'])
            ->setCreatedAt($reviewAndRatingData['createdAt']);

        return $reviewAndRating;
    }
}
