<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\ViewDataObject;

class ReviewAndRating
{
    /**
     * @var string
     */
    private $reviewId;

    /**
     * @var string
     */
    private $ratingId;

    /**
     * @var int
     */
    private $rating;

    /**
     * @var string
     */
    private $reviewText;

    /**
     * @var string
     */
    private $objectId;

    /**
     * @var string
     */
    private $objectType;

    /**
     * @var string
     */
    private $objectTitle;

    /**
     * @var string
     */
    private $createdAt;

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setReviewId($id): static
    {
        $this->reviewId = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getReviewId()
    {
        return $this->reviewId;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setRatingId($id): static
    {
        $this->ratingId = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getRatingId()
    {
        return $this->ratingId;
    }

    /**
     * @param string $rating
     *
     * @return $this
     */
    public function setRating($rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * @return int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param string $reviewText
     *
     * @return $this
     */
    public function setReviewText($reviewText): static
    {
        $this->reviewText = $reviewText;

        return $this;
    }

    /**
     * @return string
     */
    public function getReviewText()
    {
        return $this->reviewText;
    }

    /**
     * @param string $objectId
     *
     * @return $this
     */
    public function setObjectId($objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param string $objectType
     *
     * @return $this
     */
    public function setObjectType($objectType): static
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @param string $objectTitle
     *
     * @return $this
     */
    public function setObjectTitle($objectTitle): static
    {
        $this->objectTitle = $objectTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getObjectTitle()
    {
        return $this->objectTitle;
    }

    /**
     * @param string $date
     *
     * @return $this
     */
    public function setCreatedAt($date): static
    {
        $this->createdAt = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
