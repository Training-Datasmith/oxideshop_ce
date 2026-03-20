<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Payment gateway manager.
 * Checks and sets payment method data, executes payment.
 */
class Payment_Gateway extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Payment status (active - true/not active - false) (default false).
     *
     * @var bool
     */
    protected $_bl_active = false;
    /**
     * oUserpayment object (default null).
     *
     * @var object
     */
    protected $_o_payment_info;
    /**
     * Last error nr. For backward compatibility must be >3
     *
     * @abstract
     * @var string
     */
    protected $_i_last_error_no = 4;
    /**
     * Last error text.
     *
     * @abstract
     * @var string
     */
    protected $_s_last_error;
    /**
     * Sets payment parameters.
     *
     * @param object $oUserpayment User payment object
     */
    public function set_payment_params($o_userpayment): void
    {
        // store data
        $this->_o_payment_info =& $o_userpayment;
    }
    /**
     * Executes payment, returns true on success.
     *
     * @param double $dAmount Goods amount
     * @param object $oOrder  User ordering object
     *
     * @return bool
     */
    public function execute_payment($d_amount, &$o_order)
    {
        $this->_i_last_error_no = null;
        $this->_s_last_error = null;
        if (!$this->is_active()) {
            return true;
            // fake yes
        }
        // proceed with no payment
        // used for other countries
        if (@$this->_o_payment_info->oxuserpayments__oxpaymentsid->value == 'oxempty') {
            return true;
        }
        return false;
    }
    /**
     * Returns last payment processing error nr.
     *
     * @return int
     */
    public function get_last_error_no()
    {
        return $this->_i_last_error_no;
    }
    /**
     * Returns last payment processing error.
     *
     * @return int
     */
    public function get_last_error()
    {
        return $this->_s_last_error;
    }
    /**
     * Returns true is payment active.
     *
     * @return bool
     */
    protected function is_active()
    {
        return $this->_bl_active;
    }
}