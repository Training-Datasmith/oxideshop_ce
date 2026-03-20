<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

use Oxid_Esales\Eshop\Application\Model\Rating;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Exception\Rating_Permission_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
class User_Rating_Bridge implements User_Rating_Bridge_Interface
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
    public function delete_rating($user_id, $rating_id): void
    {
        $rating = $this->get_rating_by_id($rating_id);
        $this->validate_user_permissions_to_manage_rating($rating, $user_id);
        $rating = $this->disable_sub_shop_delete_protection_for_rating($rating);
        $rating->delete();
    }
    private function disable_sub_shop_delete_protection_for_rating(Rating $rating): Rating
    {
        $rating->set_is_derived(false);
        return $rating;
    }
    /**
     * @param string $userId
     * @throws RatingPermissionException
     */
    private function validate_user_permissions_to_manage_rating(Rating $rating, $user_id): void
    {
        if ($rating->oxratings__oxuserid->value !== $user_id) {
            throw new Rating_Permission_Exception();
        }
    }
    /**
     * @param string $ratingId
     *
     * @return Rating
     * @throws EntryDoesNotExistDaoException
     */
    private function get_rating_by_id($rating_id)
    {
        $rating = ox_new(Rating::class);
        $does_rating_exist = $rating->load($rating_id);
        if (!$does_rating_exist) {
            throw new Entry_Does_Not_Exist_Dao_Exception();
        }
        return $rating;
    }
}