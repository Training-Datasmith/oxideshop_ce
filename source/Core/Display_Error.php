<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * simple class to add a error message to display
 */
class Display_Error implements \Oxid_Esales\Eshop\Core\Contract\I_Display_Error
{
    /**
     * Error message
     *
     * @var string $_sMessage
     */
    protected $_s_message;
    /** @var array */
    private $_a_format_parameters = [];
    /**
     * Formats message using vsprintf if property _aFormatParameters was set and returns translated message.
     *
     * @return string stored message
     */
    public function get_ox_message()
    {
        $translated_message = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string($this->_s_message);
        if (!empty($this->_a_format_parameters)) {
            return vsprintf($translated_message, $this->_a_format_parameters);
        }
        return $translated_message;
    }
    /**
     * Stored the message.
     *
     * @param string $message message
     */
    public function set_message($message): void
    {
        $this->_s_message = $message;
    }
    /**
     * Stes format parameters for message.
     *
     * @param array $formatParameters
     */
    public function set_format_parameters($format_parameters): void
    {
        $this->_a_format_parameters = $format_parameters;
    }
    /**
     * Returns errorrous class name (currently returns null)
     */
    public function get_error_class_type(): null
    {
        return null;
    }
    /**
     * Returns value (currently returns empty string)
     *
     * @param string $name value ignored
     */
    public function get_value($name): string
    {
        return '';
    }
}