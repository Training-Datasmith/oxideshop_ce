<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * exception class covering voucher exceptions
 */
class Voucher_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxVoucherException';
    /**
     * Voucher nr. involved in this exception
     */
    private ?string $_s_voucher_nr = null;
    /**
     * Sets the voucher number as a string
     *
     * @param string $sVoucherNr voucher number
     */
    public function set_voucher_nr($s_voucher_nr): void
    {
        $this->_s_voucher_nr = (string) $s_voucher_nr;
    }
    /**
     * get voucher nr. involved
     *
     * @return string
     */
    public function get_voucher_nr()
    {
        return $this->_s_voucher_nr;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Faulty Voucher Nr --> ' . $this->_s_voucher_nr;
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
        $a_res['voucherNr'] = $this->get_voucher_nr();
        return $a_res;
    }
}