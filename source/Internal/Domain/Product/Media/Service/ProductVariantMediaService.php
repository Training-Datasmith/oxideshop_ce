<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao\Product_Media_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role_Set;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
readonly class Product_Variant_Media_Service implements Product_Variant_Media_Service_Interface
{
    public function __construct(private Product_Media_Dao_Interface $product_media_dao)
    {
    }
    public function assign_from_parent_to_variant(Id $parent_product_id, Id $variant_product_id): void
    {
        $parent_media_collection = $this->product_media_dao->get_all($parent_product_id);
        foreach ($parent_media_collection as $parent_media) {
            $variant_media = $this->create_variant_media($parent_media, $variant_product_id);
            $this->product_media_dao->add($variant_media);
        }
    }
    private function create_variant_media(Product_Media $parent_media, Id $variant_product_id): Product_Media
    {
        $variant_media = new Product_Media(Id::generate(), $variant_product_id, $parent_media->get_media(), new Product_Media_Role_Set(...$parent_media->get_role_set()->get_roles()->get_values()));
        if ($parent_media->has_position()) {
            $variant_media->set_position($parent_media->get_position());
        }
        if (!$parent_media->is_active()) {
            $variant_media->deactivate();
        }
        return $variant_media;
    }
}