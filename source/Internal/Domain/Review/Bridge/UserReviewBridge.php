<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

use Oxid_Esales\Eshop\Application\Model\Review;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Exception\Review_Permission_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
class User_Review_Bridge implements User_Review_Bridge_Interface
{
    /**
     * Delete a Review.
     *
     * @param string $userId
     * @param string $reviewId
     *
     * @throws ReviewPermissionException
     * @throws EntryDoesNotExistDaoException
     */
    public function delete_review($user_id, $review_id): void
    {
        $review = $this->get_review_by_id($review_id);
        $this->validate_user_permissions_to_manage_review($review, $user_id);
        $review->delete();
    }
    /**
     * @param string $userId
     * @throws ReviewPermissionException
     */
    private function validate_user_permissions_to_manage_review(Review $review, $user_id): void
    {
        if ($review->oxreviews__oxuserid->value !== $user_id) {
            throw new Review_Permission_Exception();
        }
    }
    /**
     * @param string $reviewId
     *
     * @return Review
     * @throws EntryDoesNotExistDaoException
     */
    private function get_review_by_id($review_id)
    {
        $review = ox_new(Review::class);
        $does_review_exist = $review->load($review_id);
        if (!$does_review_exist) {
            throw new Entry_Does_Not_Exist_Dao_Exception();
        }
        return $review;
    }
}