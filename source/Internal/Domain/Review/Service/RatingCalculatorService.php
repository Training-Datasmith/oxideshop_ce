<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;

class RatingCalculatorService implements RatingCalculatorServiceInterface
{
    /**
     * @return float
     */
    public function getAverage(ArrayCollection $ratings): int|float
    {
        if ($ratings->count() === 0) {
            return 0;
        }

        return $this->getSum($ratings) / $ratings->count();
    }

    private function getSum(ArrayCollection $ratings): int
    {
        $sum = 0;

        $ratings->forAll(function ($key, Rating $rating) use (&$sum): true {
            $sum += $rating->getRating();
            return true;
        });

        return $sum;
    }
}
