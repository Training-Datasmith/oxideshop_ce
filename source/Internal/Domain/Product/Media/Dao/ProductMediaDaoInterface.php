<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Sorting;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
interface Product_Media_Dao_Interface
{
    public function add(Product_Media $product_media): void;
    public function update(Product_Media $product_media): void;
    public function delete(Id $id): void;
    public function sort(Product_Media_Sorting $sorting): void;
    public function get(Id $id): Product_Media;
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all(Id $product_id): Array_Collection;
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all_active(Id $product_id): Array_Collection;
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all_by_role(Id $product_id, Product_Media_Role $role): Array_Collection;
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all_active_by_role(Id $product_id, Product_Media_Role $role): Array_Collection;
    public function get_by_role(Id $product_id, Product_Media_Role $role): ?Product_Media;
    public function get_active_by_role(Id $product_id, Product_Media_Role $role): ?Product_Media;
    public function get_active_by_position(Id $product_id, int $position): ?Product_Media;
    public function get_first_active(Id $product_id): ?Product_Media;
}