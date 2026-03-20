<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao\Product_Rating_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao\Rating_Dao_Interface;
class Product_Rating_Service implements Product_Rating_Service_Interface
{
    public function __construct(private readonly Rating_Dao_Interface $rating_dao, private readonly Product_Rating_Dao_Interface $product_rating_dao, private readonly Rating_Calculator_Service_Interface $rating_calculator)
    {
    }
    /**
     * @param string $productId
     */
    public function update_product_rating($product_id): void
    {
        $ratings = $this->rating_dao->get_ratings_by_product_id($product_id);
        $rating_average = $this->rating_calculator->get_average($ratings);
        $rating_count = $ratings->count();
        $product_rating = $this->product_rating_dao->get_product_rating_by_id($product_id);
        $product_rating->set_rating_average($rating_average)->set_rating_count($rating_count);
        $this->product_rating_dao->update($product_rating);
    }
}