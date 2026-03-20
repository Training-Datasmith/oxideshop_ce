<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Payment;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article RDFa payment manager.
 * Performs collection and updating (on user submit) main item information.
 * Admin Menu: Shop Settings -> Payment Methods -> RDFa.
 */
class Payment_Rdfa extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'payment_rdfa';
    /**
     * Predefined RDFa payment methods
     * 0 value have general payments, 1 have credit card payments
     *
     * @var array
     */
    protected $_a_rd_fa_payments = ['ByBankTransferInAdvance' => 0, 'ByInvoice' => 0, 'Cash' => 0, 'CheckInAdvance' => 0, 'COD' => 0, 'DirectDebit' => 0, 'GoogleCheckout' => 0, 'PayPal' => 0, 'PaySwarm' => 0, 'AmericanExpress' => 1, 'DinersClub' => 1, 'Discover' => 1, 'JCB' => 1, 'MasterCard' => 1, 'VISA' => 1];
    public function render()
    {
        $payment_id = $this->get_edit_object_id();
        if (isset($payment_id)) {
            $this->_a_view_data['edit'] = $this->get_payment($payment_id);
        }
        return parent::render();
    }
    /**
     * Saves changed mapping configurations
     */
    public function save(): void
    {
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $a_rd_fa_payments = (array) Registry::get_request()->get_request_escaped_parameter('ardfapayments');
        // Delete old mappings
        $o_db = Database_Provider::get_db();
        $o_db->execute("DELETE FROM oxobject2payment WHERE oxpaymentid = :oxpaymentid AND OXTYPE = 'rdfapayment'", ['oxpaymentid' => Registry::get_request()->get_request_escaped_parameter('oxid')]);
        // Save new mappings
        foreach ($a_rd_fa_payments as $s_payment) {
            $o_mapping = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
            $o_mapping->init('oxobject2payment');
            $o_mapping->assign($a_params);
            $o_mapping->oxobject2payment__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_payment);
            $o_mapping->save();
        }
    }
    /**
     * Returns an array including all available RDFa payments.
     *
     * @return array
     */
    public function get_all_rd_fa_payments()
    {
        $a_rd_fa_payments = [];
        $a_assigned_rd_fa_payments = $this->get_assigned_rd_fa_payments();
        foreach ($this->_a_rd_fa_payments as $s_name => $i_type) {
            $o_payment = new stdClass();
            $o_payment->name = $s_name;
            $o_payment->type = $i_type;
            $o_payment->checked = in_array($s_name, $a_assigned_rd_fa_payments);
            $a_rd_fa_payments[] = $o_payment;
        }
        return $a_rd_fa_payments;
    }
    /**
     * Returns array of RDFa payments which are assigned to current payment
     *
     * @return array
     */
    public function get_assigned_rd_fa_payments()
    {
        return Database_Provider::get_db()->get_col('select oxobjectid from oxobject2payment where oxpaymentid = :oxpaymentid and oxtype = "rdfapayment"', ['oxpaymentid' => Registry::get_request()->get_request_escaped_parameter('oxid')]);
    }
    private function get_payment(string $payment_id): Payment
    {
        $payment = ox_new(Payment::class);
        $payment->load_in_lang($this->_i_edit_lang, $payment_id);
        return $payment;
    }
}