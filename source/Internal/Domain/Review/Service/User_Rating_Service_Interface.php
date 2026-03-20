<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
interface User_Rating_Service_Interface
{
    /**
     * Returns Ratings of User.
     *
     * @param string $userId
     *
     * @return ArrayCollection
     */
    public function get_ratings($user_id);
}