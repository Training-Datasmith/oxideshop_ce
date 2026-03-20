<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Mapper\Data_Mapper_Interface as MediaDataMapperInterface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role_Set;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
readonly class Data_Mapper implements Data_Mapper_Interface
{
    public function __construct(private Media_Data_Mapper_Interface $media_data_mapper)
    {
    }
    public function to_data(Product_Media $product_media): array
    {
        return ['id' => (string) $product_media->get_id(), 'product_id' => (string) $product_media->get_product_id(), 'media_id' => (string) $product_media->get_media()->get_id(), 'position' => $product_media->get_position(), 'roles' => $this->get_roles_as_values($product_media), 'active' => $product_media->is_active()];
    }
    public function from_data(array $data): Product_Media
    {
        $roles = [];
        if (!empty($data['roles'])) {
            foreach (explode(',', (string) $data['roles']) as $role) {
                $roles[] = Product_Media_Role::from($role);
            }
        }
        $product_media = new Product_Media(Id::from_string($data['id']), Id::from_string($data['product_id']), $this->media_data_mapper->from_data(['id' => $data['media_id'], 'path' => $data['media_path'], 'type' => $data['media_mime_type']]), new Product_Media_Role_Set(...$roles));
        $product_media->set_position($data['position']);
        if (!$data['active']) {
            $product_media->deactivate();
        }
        return $product_media;
    }
    private function get_roles_as_values(Product_Media $product_media): array
    {
        return $product_media->get_role_set()->get_roles()->map(static fn(Product_Media_Role $role): string => $role->value())->get_values();
    }
}