<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Factory;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Type;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role_Set;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
readonly class Product_Media_Factory implements Product_Media_Factory_Interface
{
    public function create(Id $product_id, Media_Path $path, Media_Type $mime_type): Product_Media
    {
        return new Product_Media(Id::generate(), $product_id, new Media(Id::generate(), $path, $mime_type), new Product_Media_Role_Set());
    }
}