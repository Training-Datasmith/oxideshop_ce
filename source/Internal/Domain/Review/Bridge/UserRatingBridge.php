<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Bridge;

use OxidEsales\Eshop\Application\Model\Rating;
use OxidEsales\EshopCommunity\Internal\Domain\Review\Exception\RatingPermissionException;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\EntryDoesNotExistDaoException;

class UserRatingBridge implements UserRatingBridgeInterface
{
    /**
     * Delete a Rating.
     *
     * @param string $userId
     * @param string $ratingId
     *
     * @throws RatingPermissionException
     * @throws EntryDoesNotExistDaoException
     */
    public function deleteRating($userId, $ratingId): void
    {
        $rating = $this->getRatingById($ratingId);

        $this->validateUserPermissionsToManageRating($rating, $userId);

        $rating = $this->disableSubShopDeleteProtectionForRating($rating);
        $rating->delete();
    }

    private function disableSubShopDeleteProtectionForRating(Rating $rating): Rating
    {
        $rating->setIsDerived(false);

        return $rating;
    }

    /**
     * @param string $userId
     * @throws RatingPermissionException
     */
    private function validateUserPermissionsToManageRating(Rating $rating, $userId): void
    {
        if ($rating->oxratings__oxuserid->value !== $userId) {
            throw new RatingPermissionException();
        }
    }

    /**
     * @param string $ratingId
     *
     * @return Rating
     * @throws EntryDoesNotExistDaoException
     */
    private function getRatingById($ratingId)
    {
        $rating = oxNew(Rating::class);
        $doesRatingExist = $rating->load($ratingId);

        if (!$doesRatingExist) {
            throw new EntryDoesNotExistDaoException();
        }

        return $rating;
    }
}
