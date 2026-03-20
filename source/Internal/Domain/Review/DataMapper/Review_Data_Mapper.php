<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Review;
class Review_Data_Mapper implements Review_Data_Mapper_Interface
{
    public function map(Review $review, array $data): Review
    {
        $review->set_id($data['OXID'])->set_rating($data['OXRATING'])->set_text($data['OXTEXT'])->set_object_id($data['OXOBJECTID'])->set_user_id($data['OXUSERID'])->set_type($data['OXTYPE'])->set_created_at($data['OXTIMESTAMP']);
        return $review;
    }
    public function get_data(Review $review): array
    {
        return ['OXID' => $review->get_id(), 'OXRATING' => $review->get_rating(), 'OXTEXT' => $review->get_text(), 'OXOBJECTID' => $review->get_object_id(), 'OXUSERID' => $review->get_user_id(), 'OXTYPE' => $review->get_type(), 'OXTIMESTAMP' => $review->get_created_at()];
    }
    public function get_primary_key(Review $review): array
    {
        return ['OXID' => $review->get_id()];
    }
}