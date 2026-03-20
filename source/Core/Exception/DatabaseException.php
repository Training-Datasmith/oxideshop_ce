<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * Exception to be thrown on database errors
 */
class Database_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * DatabaseException constructor.
     *
     * Use this exception to catch and rethrow exceptions of the underlying DBAL.
     * Provide the caught exception as the third parameter of the constructor to enable exception chaining.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous Previous exception thrown by the underlying DBAL
     */
    public function __construct($message, $code, \Exception $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}