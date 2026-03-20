<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Dao\Review_Dao_Interface;
class User_Review_Service implements User_Review_Service_Interface
{
    public function __construct(private readonly Review_Dao_Interface $review_dao)
    {
    }
    /**
     * Returns User Reviews.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function get_reviews($user_id)
    {
        return $this->review_dao->get_reviews_by_user_id($user_id);
    }
}