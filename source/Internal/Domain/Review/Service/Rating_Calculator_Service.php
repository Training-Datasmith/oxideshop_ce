<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Rating;
class Rating_Calculator_Service implements Rating_Calculator_Service_Interface
{
    /**
     * @return float
     */
    public function get_average(Array_Collection $ratings): int|float
    {
        if ($ratings->count() === 0) {
            return 0;
        }
        return $this->get_sum($ratings) / $ratings->count();
    }
    private function get_sum(Array_Collection $ratings): int
    {
        $sum = 0;
        $ratings->for_all(function ($key, Rating $rating) use (&$sum): true {
            $sum += $rating->get_rating();
            return true;
        });
        return $sum;
    }
}