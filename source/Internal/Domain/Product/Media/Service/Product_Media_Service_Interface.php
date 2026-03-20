<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
interface Product_Media_Service_Interface
{
    public function add(Product_Media $product_media): void;
    public function get(Id $media_id): Product_Media;
    public function remove(Id $media_id): void;
    public function update(Product_Media $product_media): void;
    public function sort(array $ids_sorted): void;
    public function activate(Product_Media $product_media): void;
    public function deactivate(Product_Media $product_media): void;
}