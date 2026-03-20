<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * exception for invalid or non existin external files, e.g.:
 * - file does not exist
 * - file is not valid xml
 */
class File_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxFileException';
    /**
     * File connected to this exception.
     *
     * @var string
     */
    protected $_s_err_file_name;
    /**
     * Error occured with the file, if provided
     *
     * @var string
     */
    protected $_s_file_error;
    /**
     *  Sets the file name of the file related to the exception
     *
     * @param string $sFileName file name
     */
    public function set_file_name($s_file_name): void
    {
        $this->_s_err_file_name = $s_file_name;
    }
    /**
     * Gives file name related to the exception
     *
     * @return string
     */
    public function get_file_name()
    {
        return $this->_s_err_file_name;
    }
    /**
     * sets the error returned by the file operation
     *
     * @param string $sFileError Error
     */
    public function set_file_error($s_file_error): void
    {
        $this->_s_file_error = $s_file_error;
    }
    /**
     * return the file error
     *
     * @return string
     */
    public function get_file_error()
    {
        return $this->_s_file_error;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Faulty File --> ' . $this->_s_err_file_name . "\n" . 'Error Code --> ' . $this->_s_file_error;
    }
    /**
     * Override of oxException::getValues()
     *
     * @return array
     */
    public function get_values()
    {
        $a_res = parent::get_values();
        $a_res['fileName'] = $this->get_file_name();
        return $a_res;
    }
}