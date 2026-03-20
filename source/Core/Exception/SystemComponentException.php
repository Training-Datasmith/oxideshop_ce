<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * exceptions for missing components e.g.:
 * - missing class
 * - missing function
 * - missing template
 * - missing field in object
 */
class System_Component_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxSystemComponentException';
    /**
     * Component causing the exception.
     *
     * @var string
     */
    private $_s_component;
    /**
     * Sets the component name which caused the exception as a string.
     *
     * @param string $sComponent name of component
     */
    public function set_component($s_component): void
    {
        $this->_s_component = $s_component;
    }
    /**
     * Name of the component that caused the exception
     *
     * @return string
     */
    public function get_component()
    {
        return $this->_s_component;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Faulty component --> ' . $this->_s_component;
    }
    /**
     * Creates an array of field name => field value of the object.
     * To make a easy conversion of exceptions to error messages possible.
     * Should be extended when additional fields are used!
     * Overrides oxException::getValues().
     *
     * @return array
     */
    public function get_values()
    {
        $a_res = parent::get_values();
        $a_res['component'] = $this->get_component();
        return $a_res;
    }
}