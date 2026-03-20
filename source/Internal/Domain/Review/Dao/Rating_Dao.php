<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper\Rating_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Rating;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
class Rating_Dao implements Rating_Dao_Interface
{
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory, private readonly Rating_Data_Mapper_Interface $rating_data_mapper)
    {
    }
    /**
     * Returns User Ratings.
     *
     * @param string $userId
     */
    public function get_ratings_by_user_id($user_id): \Doctrine\Common\Collections\Array_Collection
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->select('r.*')->from('oxratings', 'r')->where('r.oxuserid = :userId')->order_by('r.oxtimestamp', 'DESC')->set_parameter('userId', $user_id);
        return $this->map_ratings($query_builder->fetch_all_associative());
    }
    public function delete(Rating $rating): void
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->delete('oxratings')->where('oxid = :id')->set_parameter('id', $rating->get_id())->execute_statement();
    }
    /**
     * Returns Ratings for a product.
     *
     * @param string $productId
     */
    public function get_ratings_by_product_id($product_id): \Doctrine\Common\Collections\Array_Collection
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->select('r.*')->from('oxratings', 'r')->where('r.oxobjectid = :productId')->and_where('r.oxtype = :productType')->order_by('r.oxtimestamp', 'DESC')->set_parameters(['productId' => $product_id, 'productType' => 'oxarticle']);
        return $this->map_ratings($query_builder->fetch_all_associative());
    }
    /**
     * Maps rating data from database to Ratings Collection.
     *
     * @param array $ratingsData
     */
    private function map_ratings($ratings_data): \Doctrine\Common\Collections\Array_Collection
    {
        $ratings = new Array_Collection();
        foreach ($ratings_data as $rating_data) {
            $rating = new Rating();
            $ratings->add($this->rating_data_mapper->map($rating, $rating_data));
        }
        return $ratings;
    }
}