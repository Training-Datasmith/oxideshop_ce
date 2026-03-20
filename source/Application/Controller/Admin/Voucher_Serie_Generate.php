<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Voucher Serie generator class
 */
class Voucher_Serie_Generate extends \Oxid_Esales\Eshop\Application\Controller\Admin\Voucher_Serie_Main
{
    /**
     * Voucher generator class name
     *
     * @var string
     */
    public $s_class_do = 'voucherserie_generate';
    /**
     * Number of vouchers to generate per tick
     *
     * @var int
     */
    public $i_generate_per_tick = 100;
    /**
     * Current class template name
     *
     * @var string
     */
    protected $_s_this_template = 'voucherserie_generate';
    /**
     * Voucher serie object
     *
     * @var \OxidEsales\Eshop\Application\Model\VoucherSerie
     */
    protected $_o_voucher_serie;
    /**
     * Generated vouchers count
     *
     * @var int
     */
    protected $_i_generated = false;
    /**
     * Generates vouchers by offset iCnt
     *
     * @param integer $cnt voucher offset
     *
     * @return bool
     */
    public function next_tick($cnt)
    {
        if ($i_generated_items = $this->generate_voucher($cnt)) {
            return $i_generated_items;
        }
        return false;
    }
    /**
     * Generates and saves vouchers. Returns number of saved records
     *
     * @param int $iCnt voucher counter offset
     *
     * @return int saved record count
     */
    public function generate_voucher($i_cnt)
    {
        $i_amount = abs((int) \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('voucherAmount'));
        // creating new vouchers
        if ($i_cnt < $i_amount && $o_voucher_serie = $this->get_voucher_serie()) {
            if (!$this->_i_generated) {
                $this->_i_generated = $i_cnt;
            }
            $bl_random_nr = (bool) \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('randomVoucherNr');
            $s_voucher_nr = $bl_random_nr ? \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_uid() : \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('voucherNr');
            $o_new_voucher = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher::class);
            $o_new_voucher->oxvouchers__oxvoucherserieid = new \Oxid_Esales\Eshop\Core\Field($o_voucher_serie->get_id());
            $o_new_voucher->oxvouchers__oxvouchernr = new \Oxid_Esales\Eshop\Core\Field($s_voucher_nr);
            $o_new_voucher->save();
            $this->_i_generated++;
        }
        return $this->_i_generated;
    }
    /**
     * Runs voucher generation
     */
    public function run(): void
    {
        $bl_continue = true;
        $i_exported_items = 0;
        // file is open
        $i_start = Registry::get_request()->get_request_escaped_parameter('iStart');
        for ($i = $i_start; $i < $i_start + $this->i_generate_per_tick; $i++) {
            if (($i_exported_items = $this->next_tick($i)) === false) {
                // end reached
                $this->stop(ERR_SUCCESS);
                $bl_continue = false;
                break;
            }
        }
        if ($bl_continue) {
            // make ticker continue
            $this->_a_view_data['refresh'] = 0;
            $this->_a_view_data['iStart'] = $i;
            $this->_a_view_data['iExpItems'] = $i_exported_items;
        }
    }
}