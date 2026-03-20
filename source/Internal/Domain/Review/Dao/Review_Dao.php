<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper\Review_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Review;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
class Review_Dao implements Review_Dao_Interface
{
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory, private readonly Review_Data_Mapper_Interface $review_data_mapper)
    {
    }
    /**
     * Returns User Reviews.
     *
     * @param string $userId
     */
    public function get_reviews_by_user_id($user_id): \Doctrine\Common\Collections\Array_Collection
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->select('r.*')->from('oxreviews', 'r')->where('r.oxuserid = :userId')->order_by('r.oxcreate', 'DESC')->set_parameter('userId', $user_id);
        return $this->map_reviews($query_builder->fetch_all_associative());
    }
    public function delete(Review $review): void
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->delete('oxreviews')->where('oxid = :id')->set_parameter('id', $review->get_id())->execute_statement();
    }
    /**
     * Maps rating data from database to Reviews Collection.
     *
     * @param array $reviewsData
     */
    private function map_reviews($reviews_data): \Doctrine\Common\Collections\Array_Collection
    {
        $reviews = new Array_Collection();
        foreach ($reviews_data as $review_data) {
            $review = new Review();
            $reviews[] = $this->review_data_mapper->map($review, $review_data);
        }
        return $reviews;
    }
}