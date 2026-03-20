<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object;

use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Exception\Empty_Product_Media_Role_Exception;
class Product_Media_Role
{
    public const ICON = 'icon';
    public const THUMBNAIL = 'thumbnail';
    public const DETAIL = 'detail';
    public static function from(string $role): Product_Media_Role
    {
        if ($role === '') {
            throw new Empty_Product_Media_Role_Exception();
        }
        return new self($role);
    }
    public function value(): string
    {
        return $this->role;
    }
    private function __construct(private readonly string $role)
    {
    }
}