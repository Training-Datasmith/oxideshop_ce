<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * simplified Exception classes for simply displaying errors
 * saves resources when exception functionality is not needed
 */
class Exception_To_Display implements \Oxid_Esales\Eshop\Core\Contract\I_Display_Error, \Stringable
{
    /**
     * Language const of a Message
     *
     * @var string
     */
    private $_s_message;
    /**
     * Shop debug
     *
     * @var integer
     */
    protected $_bl_debug = false;
    /**
     * Stack trace as a string
     *
     * @var string
     */
    private $_s_stack_trace;
    /**
     * Additional values
     *
     * @var string
     */
    private $_a_values;
    /**
     * Typeof the exception (old class name)
     *
     * @var string
     */
    private $_s_type;
    /**
     * Stack trace setter
     *
     * @param string $sStackTrace stack trace
     */
    public function set_stack_trace($s_stack_trace): void
    {
        $this->_s_stack_trace = $s_stack_trace;
    }
    /**
     * Returns stack trace
     *
     * @return string
     */
    public function get_stack_trace()
    {
        return $this->_s_stack_trace;
    }
    /**
     * Sets \OxidEsales\Eshop\Core\Exception\ExceptionToDisplay::_aValues value
     *
     * @param array $aValues exception values to store
     */
    public function set_values($a_values): void
    {
        $this->_a_values = $a_values;
    }
    /**
     * Stores into exception storage message or other value
     *
     * @param string $sName  storage name
     * @param mixed  $sValue value to store
     */
    public function add_value($s_name, $s_value): void
    {
        $this->_a_values[$s_name] = $s_value;
    }
    /**
     * Exception type setter
     *
     * @param string $sType exception type
     */
    public function set_exception_type($s_type): void
    {
        $this->_s_type = $s_type;
    }
    /**
     * Returns error class type
     *
     * @return string
     */
    public function get_error_class_type()
    {
        return $this->_s_type;
    }
    /**
     * Returns exception stored (by name) value
     *
     * @param string $sName storage name
     *
     * @return  mixed
     */
    public function get_value($s_name)
    {
        return $this->_a_values[$s_name];
    }
    /**
     * Returns all exception stored values
     *
     * @return  array
     */
    public function get_values()
    {
        return $this->_a_values;
    }
    /**
     * Exception debug mode setter
     *
     * @param bool $bl if TRUE debug mode on
     */
    public function set_debug($bl): void
    {
        $this->_bl_debug = $bl;
    }
    /**
     * Exception message setter
     *
     * @param string $sMessage exception message
     */
    public function set_message($s_message): void
    {
        $this->_s_message = $s_message;
    }
    /**
     * Sets the exception message arguments used when
     * outputing message using sprintf().
     */
    public function set_message_args(): void
    {
        $this->_a_message_args = func_get_args();
    }
    /**
     * Returns translated exception message
     *
     * @return string
     */
    public function get_ox_message()
    {
        if ($this->_bl_debug) {
            return $this;
        }
        $s_string = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string($this->_s_message);
        if (!empty($this->_a_message_args)) {
            return vsprintf($s_string, $this->_a_message_args);
        }
        return $s_string;
    }
    /**
     * When exception is converted as string, this magic method return exception message
     */
    public function __toString(): string
    {
        $s_res = $this->get_error_class_type() . ' (time: ' . date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time()) . '): ' . $this->get_ox_message() . " \n Stack Trace: " . $this->get_stack_trace() . "\n";
        foreach ($this->_a_values as $key => $value) {
            $s_res .= $key . ' => ' . $value . "\n";
        }
        return $s_res;
    }
}