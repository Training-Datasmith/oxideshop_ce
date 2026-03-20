<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Dao\Media_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao\Product_Media_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Sorting;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
readonly class Product_Media_Service implements Product_Media_Service_Interface
{
    public function __construct(private Product_Media_Dao_Interface $product_media_dao, private Media_Dao_Interface $media_dao)
    {
    }
    public function add(Product_Media $product_media): void
    {
        $this->media_dao->add($product_media->get_media());
        $this->product_media_dao->add($product_media);
    }
    public function get(Id $media_id): Product_Media
    {
        return $this->product_media_dao->get($media_id);
    }
    public function remove(Id $media_id): void
    {
        $this->product_media_dao->delete($media_id);
    }
    public function update(Product_Media $product_media): void
    {
        $this->product_media_dao->update($product_media);
    }
    public function sort(array $ids_sorted): void
    {
        $this->product_media_dao->sort(new Product_Media_Sorting($ids_sorted));
    }
    public function activate(Product_Media $product_media): void
    {
        if (!$product_media->is_active()) {
            $product_media->activate();
            $this->product_media_dao->update($product_media);
        }
    }
    public function deactivate(Product_Media $product_media): void
    {
        if ($product_media->is_active()) {
            $product_media->deactivate();
            $this->product_media_dao->update($product_media);
        }
    }
}