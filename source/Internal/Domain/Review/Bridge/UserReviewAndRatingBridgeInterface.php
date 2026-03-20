<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface User_Review_And_Rating_Bridge_Interface
{
    /**
     * Get number of reviews by given user.
     *
     * @param string $userId
     *
     * @return int
     */
    public function get_review_and_rating_list_count($user_id);
    /**
     * Returns Collection of User Ratings and Reviews.
     *
     * @param string $userId
     *
     * @return array
     */
    public function get_review_and_rating_list($user_id);
}