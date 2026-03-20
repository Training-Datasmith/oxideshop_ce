<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\View_Data_Object;

class Review_And_Rating
{
    /**
     * @var string
     */
    private $review_id;
    /**
     * @var string
     */
    private $rating_id;
    /**
     * @var int
     */
    private $rating;
    /**
     * @var string
     */
    private $review_text;
    /**
     * @var string
     */
    private $object_id;
    /**
     * @var string
     */
    private $object_type;
    /**
     * @var string
     */
    private $object_title;
    /**
     * @var string
     */
    private $created_at;
    /**
     * @param string $id
     *
     * @return $this
     */
    public function set_review_id($id): static
    {
        $this->review_id = $id;
        return $this;
    }
    /**
     * @return string
     */
    public function get_review_id()
    {
        return $this->review_id;
    }
    /**
     * @param int $id
     *
     * @return $this
     */
    public function set_rating_id($id): static
    {
        $this->rating_id = $id;
        return $this;
    }
    /**
     * @return string
     */
    public function get_rating_id()
    {
        return $this->rating_id;
    }
    /**
     * @param string $rating
     *
     * @return $this
     */
    public function set_rating($rating): static
    {
        $this->rating = $rating;
        return $this;
    }
    /**
     * @return int
     */
    public function get_rating()
    {
        return $this->rating;
    }
    /**
     * @param string $reviewText
     *
     * @return $this
     */
    public function set_review_text($review_text): static
    {
        $this->review_text = $review_text;
        return $this;
    }
    /**
     * @return string
     */
    public function get_review_text()
    {
        return $this->review_text;
    }
    /**
     * @param string $objectId
     *
     * @return $this
     */
    public function set_object_id($object_id): static
    {
        $this->object_id = $object_id;
        return $this;
    }
    /**
     * @return int
     */
    public function get_object_id()
    {
        return $this->object_id;
    }
    /**
     * @param string $objectType
     *
     * @return $this
     */
    public function set_object_type($object_type): static
    {
        $this->object_type = $object_type;
        return $this;
    }
    /**
     * @return string
     */
    public function get_object_type()
    {
        return $this->object_type;
    }
    /**
     * @param string $objectTitle
     *
     * @return $this
     */
    public function set_object_title($object_title): static
    {
        $this->object_title = $object_title;
        return $this;
    }
    /**
     * @return string
     */
    public function get_object_title()
    {
        return $this->object_title;
    }
    /**
     * @param string $date
     *
     * @return $this
     */
    public function set_created_at($date): static
    {
        $this->created_at = $date;
        return $this;
    }
    /**
     * @return string
     */
    public function get_created_at()
    {
        return $this->created_at;
    }
}