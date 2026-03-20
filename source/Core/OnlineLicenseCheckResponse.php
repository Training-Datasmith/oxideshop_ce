<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Online license check response class.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_License_Check_Response
{
    /**
     * Serial keys.
     *
     * @var string
     */
    public $code;
    /**
     * Build revision number.
     *
     * @var string
     */
    public $message;
}