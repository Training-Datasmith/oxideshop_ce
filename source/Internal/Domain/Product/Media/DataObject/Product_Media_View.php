<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object;

readonly class Product_Media_View
{
    public function __construct(private string $detail_url, private string $icon_url, private string $zoom_url, private string $thumbnail_url, private bool $is_fallback = false)
    {
    }
    public function get_detail_url(): string
    {
        return $this->detail_url;
    }
    public function get_icon_url(): string
    {
        return $this->icon_url;
    }
    public function get_zoom_url(): string
    {
        return $this->zoom_url;
    }
    public function get_thumbnail_url(): string
    {
        return $this->thumbnail_url;
    }
    public function is_fallback(): bool
    {
        return $this->is_fallback;
    }
}