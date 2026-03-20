<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Data_Object\Review;
interface Review_Data_Mapper_Interface
{
    public function map(Review $review, array $data): Review;
    public function get_data(Review $review): array;
    public function get_primary_key(Review $review): array;
}