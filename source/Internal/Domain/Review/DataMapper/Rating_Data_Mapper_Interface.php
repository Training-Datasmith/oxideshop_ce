<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Rating;
interface Rating_Data_Mapper_Interface
{
    public function map(Rating $rating, array $data): Rating;
    public function get_data(Rating $rating): array;
    public function get_primary_key(Rating $rating): array;
}