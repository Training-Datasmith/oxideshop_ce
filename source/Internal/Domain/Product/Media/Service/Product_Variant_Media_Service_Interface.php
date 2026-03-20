<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
interface Product_Variant_Media_Service_Interface
{
    public function assign_from_parent_to_variant(Id $parent_product_id, Id $variant_product_id): void;
}