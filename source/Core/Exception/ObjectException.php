<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Exception;

/**
 * e.g.:
 * - not existing object
 * - wrong type
 * - ID not set
 */
class ObjectException extends \OxidEsales\Eshop\Core\Exception\StandardException
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxObjectException';

    /**
     * Object causing exception.
     *
     * @var object
     */
    private $_oObject;

    /**
     * Sets the object which caused the exception.
     *
     * @param object $oObject exception object
     */
    public function setObject($oObject): void
    {
        $this->_oObject = $oObject;
    }

    /**
     * Get the object which caused the exception.
     *
     * @return object
     */
    public function getObject()
    {
        return $this->_oObject;
    }

    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function getString(): string
    {
        return self::class . '-' . parent::getString() . ' Faulty Object --> ' . $this->_oObject::class . "\n";
    }
}
