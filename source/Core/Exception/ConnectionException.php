<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * exception class for all kind of connection problems to external servers, e.g.:
 * - no connection, proxy problem, wrong configuration, etc.
 * - ipayment server
 * - online vat id check
 * - db server
 */
class Connection_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxConnectionException';
    /**
     * Address value
     *
     * @var string
     */
    private $_s_address;
    /**
     * connection error as given by connect method
     *
     * @var string
     */
    private $_s_connection_error;
    /**
     * Enter address of the external server which caused the exception
     *
     * @param string $sAdress Externalserver address
     */
    public function set_adress($s_adress): void
    {
        $this->_s_address = $s_adress;
    }
    /**
     * Gives address of the external server which caused the exception
     *
     * @return string
     */
    public function get_adress()
    {
        return $this->_s_address;
    }
    /**
     * Sets the connection error returned by the connect function
     *
     * @param string $sConnError connection error
     */
    public function set_connection_error($s_conn_error): void
    {
        $this->_s_connection_error = $s_conn_error;
    }
    /**
     * Gives the connection error returned by the connect function
     *
     * @return string
     */
    public function get_connection_error()
    {
        return $this->_s_connection_error;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Connection Adress --> ' . $this->_s_address . "\n" . 'Connection Error --> ' . $this->_s_connection_error;
    }
    /**
     * Override of oxException::getValues()
     *
     * @return array
     */
    public function get_values()
    {
        $a_res = parent::get_values();
        $a_res['adress'] = $this->get_adress();
        $a_res['connectionError'] = $this->get_connection_error();
        return $a_res;
    }
}