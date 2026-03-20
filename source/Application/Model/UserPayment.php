<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * User payment manager.
 * Performs assigning, loading, inserting and updating functions for
 * user payment.
 */
class User_Payment extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Name of current class
     *
     * @var string
     */
    protected $_s_class_name = 'oxuserpayment';
    /**
     * Payment info object
     *
     * @var \OxidEsales\Eshop\Application\Model\Payment
     */
    protected $_o_payment;
    /**
     * current dyn values
     *
     * @var array
     */
    protected $_a_dyn_values;
    /**
     * Special getter for oxpayments__oxdesc field
     *
     * @param string $sName name of field
     *
     * @return string
     */
    public function __get($s_name)
    {
        //due to compatibility with templates
        if ($s_name == 'oxpayments__oxdesc') {
            if ($this->_o_payment === null) {
                $this->_o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
                $this->_o_payment->load($this->oxuserpayments__oxpaymentsid->value);
            }
            return $this->_o_payment->oxpayments__oxdesc;
        }
        if ($s_name == 'aDynValues') {
            if ($this->_a_dyn_values === null) {
                $this->_a_dyn_values = $this->get_dyn_values();
            }
            return $this->_a_dyn_values;
        }
        return parent::__get($s_name);
    }
    /**
     * Class constructor. Sets payment key for encoding sensitive data and
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxuserpayments');
    }
    /**
     * Get user payment by payment id
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser        user object
     * @param string                                   $sPaymentType payment type
     *
     * @return bool
     */
    public function get_payment_by_payment_type($o_user = null, $s_payment_type = null)
    {
        $bl_get = false;
        if ($o_user && $s_payment_type != null) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'select oxpaymentid from oxorder where oxpaymenttype = :oxpaymenttype and
                    oxuserid = :oxuserid order by oxorderdate desc';
            $params = ['oxpaymenttype' => $s_payment_type, 'oxuserid' => $o_user->get_id()];
            if ($s_ox_id = $o_db->get_one($s_q, $params)) {
                $bl_get = $this->load($s_ox_id);
            }
        }
        return $bl_get;
    }
    /**
     * Returns an array of dyn payment values
     *
     * @return array
     */
    public function get_dyn_values()
    {
        if (!$this->_a_dyn_values) {
            $s_raw_dyn_value = null;
            if (is_object($this->oxuserpayments__oxvalue)) {
                $s_raw_dyn_value = $this->oxuserpayments__oxvalue->get_raw_value();
            }
            $this->_a_dyn_values = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($s_raw_dyn_value);
        }
        return $this->_a_dyn_values;
    }
    /**
     * sets the dyn values
     *
     * @param array $aDynValues the array of dy values
     */
    public function set_dyn_values($a_dyn_values): void
    {
        $this->_a_dyn_values = $a_dyn_values;
    }
}