<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object;

use ArrayIterator;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
readonly class Product_Media_Sorting implements \Stringable
{
    private ArrayIterator $sorting;
    public function __construct(array $sorted_ids)
    {
        $this->sorting = new ArrayIterator([]);
        foreach ($sorted_ids as $id) {
            $this->sorting->append(Id::from_string($id));
        }
    }
    public function get_sorting(): ArrayIterator
    {
        return $this->sorting;
    }
    public function __toString(): string
    {
        $ids = '';
        foreach ($this->sorting as $id) {
            $ids .= "'{$id}',";
        }
        return rtrim($ids, ',');
    }
}