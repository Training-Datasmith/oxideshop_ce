<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

use Exception;
/**
 * Basic exception class
 */
class Standard_Exception extends Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxException';
    /**
     * Not caught means the exception was not caught and occured in the rendering process,
     * which is not allowed!
     *
     * @var bool
     */
    protected $_bl_renderer = false;
    /**
     * Indicates that the Exception was caught in oxshopcontrol, which should be avoided!
     *
     * @var bool
     */
    protected $_bl_not_caught = false;
    /**
     * Default constructor
     *
     * @param string          $sMessage exception message
     * @param integer         $iCode    exception code
     * @param Exception|null $previous previous exception
     */
    public function __construct($s_message = 'not set', $i_code = 0, ?Exception $previous = null)
    {
        parent::__construct($s_message, $i_code, $previous);
    }
    /**
     * Sets the exception message
     *
     *  @deprecated since v6.0 (2017-02-27); This method will be removed. Set message in the constructor.
     *
     * @param string $sMessage exception message
     */
    public function set_message($s_message): void
    {
        $this->message = $s_message;
    }
    /**
     * To define that the exception was caught in renderer
     */
    public function set_renderer(): void
    {
        $this->_bl_renderer = true;
    }
    /**
     * Is the exception caught in a renderer
     *
     * @return bool
     */
    public function is_renderer()
    {
        return $this->_bl_renderer;
    }
    /**
     * To define that the exception was not caught (only in oxexceptionhandler)
     */
    public function set_not_caught(): void
    {
        $this->_bl_not_caught = true;
    }
    /**
     * Is the exception "not" caught.
     *
     * @return bool
     */
    public function is_not_caught()
    {
        return $this->_bl_not_caught;
    }
    /**
     * Get complete string dump, should be overwritten by excptions extending this exceptions
     * if they introduce new fields
     */
    public function get_string(): string
    {
        $s_warning = '';
        if ($this->_bl_not_caught) {
            $s_warning .= '--!--NOT CAUGHT--!--';
        }
        if ($this->_bl_renderer) {
            $s_warning .= '--!--RENDERER--!--';
        }
        $current_time = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        return $s_warning . self::class . ' (time: ' . $current_time . "): [{$this->code}]: {$this->message} \n Stack Trace: {$this->get_trace_as_string()}\n\n";
    }
    /**
     * Creates an array of field name => field value of the object.
     * To make a easy conversion of exceptions to error messages possible.
     * Should be extended when additional fields are used!
     */
    public function get_values(): array
    {
        return [];
    }
    /**
     * Defines a name of the view variable containing the messages
     *
     * @param string $sDestination name of the view variable
     */
    public function set_destination($s_destination)
    {
    }
    /**
     * Get exception type.
     * Currently old class name is used here for compatibility.
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }
}