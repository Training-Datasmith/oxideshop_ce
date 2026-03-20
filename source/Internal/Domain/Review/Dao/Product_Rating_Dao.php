<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper\Product_Rating_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Product_Rating;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Invalid_Object_Id_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
class Product_Rating_Dao implements Product_Rating_Dao_Interface
{
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory, private readonly Product_Rating_Data_Mapper_Interface $product_rating_mapper)
    {
    }
    public function update(Product_Rating $product_rating): void
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->update('oxarticles')->set('OXRATING', ':OXRATING')->set('OXRATINGCNT', ':OXRATINGCNT')->where('OXID = :OXID')->set_parameters($this->product_rating_mapper->get_data($product_rating));
        $query_builder->execute_statement();
    }
    /**
     * @param string $productId
     *
     * @throws InvalidObjectIdDaoException
     */
    public function get_product_rating_by_id($product_id): \Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Product_Rating
    {
        $this->validate_product_id($product_id);
        $query_builder = $this->query_builder_factory->create();
        $query_builder->select('OXID', 'OXRATING', 'OXRATINGCNT')->from('oxarticles')->where('oxid = :productId')->set_max_results(1)->set_parameter('productId', $product_id);
        return $this->product_rating_mapper->map(new Product_Rating(), $query_builder->fetch_associative());
    }
    /**
     * @param string $productId
     *
     * @throws InvalidObjectIdDaoException
     */
    private function validate_product_id($product_id): void
    {
        if (empty($product_id) || !is_string($product_id)) {
            throw new Invalid_Object_Id_Dao_Exception();
        }
    }
}