<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
readonly class Media
{
    public function __construct(private Id $id, private Media_Path $media_path, private Media_Type $media_type)
    {
    }
    public function get_id(): Id
    {
        return $this->id;
    }
    public function get_media_path(): Media_Path
    {
        return $this->media_path;
    }
    public function get_media_type(): Media_Type
    {
        return $this->media_type;
    }
}