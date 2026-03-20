<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_View;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
interface Product_Media_View_Service_Interface
{
    public function get_by_role(Id $product_id, Product_Media_Role $role): Product_Media_View;
    /** @return array<string, ProductMediaView> */
    public function get_all_by_role(Id $product_id, Product_Media_Role $role): array;
    public function get_by_position(Id $product_id, int $position): Product_Media_View;
}