<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object;

class Rating
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var int
     */
    private $rating;
    /**
     * @var string
     */
    private $object_id;
    /**
     * @var int
     */
    private $user_id;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $created_at;
    /**
     * @param string $id
     *
     * @return $this
     */
    public function set_id($id): static
    {
        $this->id = $id;
        return $this;
    }
    /**
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }
    /**
     * @param int $rating
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
     * @return string
     */
    public function get_object_id()
    {
        return $this->object_id;
    }
    /**
     * @param int $userId
     *
     * @return $this
     */
    public function set_user_id($user_id): static
    {
        $this->user_id = $user_id;
        return $this;
    }
    /**
     * @return string
     */
    public function get_user_id()
    {
        return $this->user_id;
    }
    /**
     * @param string $type
     *
     * @return $this
     */
    public function set_type($type): static
    {
        $this->type = $type;
        return $this;
    }
    /**
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }
    /**
     * @param string $createdAt
     *
     * @return $this
     */
    public function set_created_at($created_at): static
    {
        $this->created_at = $created_at;
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