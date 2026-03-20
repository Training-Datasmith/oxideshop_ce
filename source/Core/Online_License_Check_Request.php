<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Online license check request class used as entity.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_License_Check_Request extends \Oxid_Esales\Eshop\Core\Online_Request
{
    /**
     * Web service protocol version.
     *
     * @var string
     */
    public $p_version = '1.1';
    /**
     * Serial keys.
     *
     * @var string
     */
    public $keys;
    /**
     * Product related specific information
     * like amount of sub shops and amount of admin users.
     *
     * @var object
     */
    public $product_specific_information;
}