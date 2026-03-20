<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao\Rating_Dao_Interface;
class User_Rating_Service implements User_Rating_Service_Interface
{
    public function __construct(private readonly Rating_Dao_Interface $rating_dao)
    {
    }
    /**
     * Returns user ratings.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function get_ratings($user_id)
    {
        return $this->rating_dao->get_ratings_by_user_id($user_id);
    }
}