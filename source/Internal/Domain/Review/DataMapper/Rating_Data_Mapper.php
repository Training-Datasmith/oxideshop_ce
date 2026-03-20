<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Rating;
class Rating_Data_Mapper implements Rating_Data_Mapper_Interface
{
    public function map(Rating $rating, array $data): Rating
    {
        $rating->set_id($data['OXID'])->set_rating($data['OXRATING'])->set_object_id($data['OXOBJECTID'])->set_user_id($data['OXUSERID'])->set_type($data['OXTYPE'])->set_created_at($data['OXTIMESTAMP']);
        return $rating;
    }
    public function get_data(Rating $rating): array
    {
        return ['OXID' => $rating->get_id(), 'OXRATING' => $rating->get_rating(), 'OXOBJECTID' => $rating->get_object_id(), 'OXUSERID' => $rating->get_user_id(), 'OXTYPE' => $rating->get_type(), 'OXTIMESTAMP' => $rating->get_created_at()];
    }
    public function get_primary_key(Rating $object): array
    {
        return ['OXID' => $object->get_id()];
    }
}