<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * e.g.:
 * - shop is not active
 */
class Shop_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxShopException';
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string();
    }
}