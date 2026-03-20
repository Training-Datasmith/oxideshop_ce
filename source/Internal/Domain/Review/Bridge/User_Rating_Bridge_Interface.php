<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Exception\Rating_Permission_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface User_Rating_Bridge_Interface
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
    public function delete_rating($user_id, $rating_id);
}