<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Type;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Media_Uploader_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Media_Constraint_Validator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Factory\Product_Media_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Symfony\Component\Http_Foundation\File\Uploaded_File;
readonly class Product_Media_Upload_Processor implements Product_Media_Upload_Processor_Interface
{
    public function __construct(private Media_Constraint_Validator_Interface $media_constraint_validator, private Media_Uploader_Interface $media_uploader, private Product_Media_Factory_Interface $product_media_factory, private Product_Media_Path_Resolver_Interface $product_media_path_resolver)
    {
    }
    public function process(Id $product_id, Uploaded_File $uploaded_file): Product_Media
    {
        $this->media_constraint_validator->validate($uploaded_file);
        $target_path = $this->product_media_path_resolver->resolve((string) $product_id, $uploaded_file->get_client_original_name());
        $this->media_uploader->upload_to($uploaded_file, $target_path);
        return $this->product_media_factory->create($product_id, $target_path, new Media_Type($uploaded_file->get_client_mime_type()));
    }
}