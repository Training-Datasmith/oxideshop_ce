<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * e.g.:
 * - not existing object
 * - wrong type
 * - ID not set
 */
class Object_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
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
    private $_o_object;
    /**
     * Sets the object which caused the exception.
     *
     * @param object $oObject exception object
     */
    public function set_object($o_object): void
    {
        $this->_o_object = $o_object;
    }
    /**
     * Get the object which caused the exception.
     *
     * @return object
     */
    public function get_object()
    {
        return $this->_o_object;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Faulty Object --> ' . $this->_o_object::class . "\n";
    }
}