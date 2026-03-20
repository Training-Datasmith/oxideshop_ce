<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object;

use Doctrine\Common\Collections\Array_Collection;
use Doctrine\Common\Collections\Collection;
class Product_Media_Role_Set
{
    private readonly Collection $roles;
    public function __construct(Product_Media_Role ...$roles)
    {
        $this->roles = new Array_Collection();
        foreach ($roles as $r) {
            $this->roles->set($r->value(), $r);
        }
    }
    public function get_roles(): Collection
    {
        return $this->roles;
    }
    public function add_role(Product_Media_Role $role): void
    {
        $this->roles->set($role->value(), $role);
    }
    public function remove_role(Product_Media_Role $role): void
    {
        $this->roles->remove($role->value());
    }
    public function has(Product_Media_Role $role): bool
    {
        return $this->roles->contains_key($role->value());
    }
}