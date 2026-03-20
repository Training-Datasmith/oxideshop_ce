<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service;

use Doctrine\Common\Collections\Array_Collection;
interface Rating_Calculator_Service_Interface
{
    /**
     * @return float
     */
    public function get_average(Array_Collection $ratings);
}