<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Online module notifier request class and used as entity.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_Modules_Notifier_Request extends \Oxid_Esales\Eshop\Core\Online_Request
{
    /**
     * Web service protocol version.
     *
     * @var string
     */
    public $p_version = '1.1';
    /**
     * Modules array.
     *
     * @var array
     */
    public $modules;
}