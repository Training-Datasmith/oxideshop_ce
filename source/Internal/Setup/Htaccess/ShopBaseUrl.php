<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

class Shop_Base_Url
{
    public function __construct(private readonly string $url)
    {
        $this->validate_url();
    }
    public function get_url(): string
    {
        return $this->url;
    }
    private function validate_url(): void
    {
        if (!parse_url($this->url, PHP_URL_SCHEME) || !parse_url($this->url, PHP_URL_HOST)) {
            throw new Invalid_Shop_Url_Exception("'{$this->url}' is not a valid URL!");
        }
    }
}