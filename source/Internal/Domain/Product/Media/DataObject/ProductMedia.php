<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
class Product_Media
{
    private ?int $position = null;
    private bool $active = true;
    public function __construct(private readonly Id $id, private readonly Id $product_id, private readonly Media $media, private readonly Product_Media_Role_Set $role_set)
    {
    }
    public function get_id(): Id
    {
        return $this->id;
    }
    public function get_product_id(): Id
    {
        return $this->product_id;
    }
    public function get_media(): Media
    {
        return $this->media;
    }
    public function has_position(): bool
    {
        return $this->position !== null;
    }
    public function get_position(): int
    {
        return $this->position;
    }
    public function set_position(int $position): void
    {
        $this->position = $position;
    }
    public function get_role_set(): Product_Media_Role_Set
    {
        return $this->role_set;
    }
    public function is_active(): bool
    {
        return $this->active;
    }
    public function activate(): void
    {
        $this->active = true;
    }
    public function deactivate(): void
    {
        $this->active = false;
    }
}