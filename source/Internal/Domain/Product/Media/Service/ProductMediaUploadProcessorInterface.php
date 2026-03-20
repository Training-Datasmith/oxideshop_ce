<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
interface Product_Media_Upload_Processor_Interface
{
    public function process(Id $product_id, Uploaded_File $uploaded_file): Product_Media;
}