<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Factory;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Type;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
interface Product_Media_Factory_Interface
{
    public function create(Id $product_id, Media_Path $path, Media_Type $mime_type): Product_Media;
}