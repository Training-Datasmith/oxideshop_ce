<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * Class ModuleValidationException
 *
 * This exception should be thrown, if a module validation fails in any point (activation, deactivation, module list, etc)
 */
class Module_Validation_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type
     *
     * @var string
     */
    protected $type = 'ModuleValidationException';
}