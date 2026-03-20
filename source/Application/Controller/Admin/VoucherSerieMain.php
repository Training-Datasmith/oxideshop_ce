<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article main voucherserie manager.
 * There is possibility to change voucherserie name, description, valid terms
 * and etc.
 * Admin Menu: Shop Settings -> Vouchers -> Main.
 */
class Voucher_Serie_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Dynamic_Export_Base_Controller
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = 'voucherSerie_generate';
    /**
     * Voucher serie object
     *
     * @var \OxidEsales\Eshop\Application\Model\VoucherSerie
     */
    protected $_o_voucher_serie;
    /**
     * Current class template name
     *
     * @var string
     */
    protected $_s_this_template = 'voucherserie_main';
    /**
     * View id, use old class name for compatibility reasons.
     *
     * @var string
     */
    protected $view_id = 'voucherserie_main';
    /**
     * Executes parent method parent::render(), creates VoucherSerie object
     * and returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_voucher_serie = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_Serie::class);
            $o_voucher_serie->load($sox_id);
            $this->_a_view_data['edit'] = $o_voucher_serie;
            //Disable editing for derived items
            if ($o_voucher_serie->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        return $this->_s_this_template;
    }
    /**
     * Saves main Voucherserie parameters changes.
     */
    public function save(): void
    {
        parent::save();
        // Parameter Processing
        $sox_id = $this->get_edit_object_id();
        $a_serie_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // Voucher Serie Processing
        $o_voucher_serie = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_Serie::class);
        // if serie already exist use it
        if ($sox_id != '-1') {
            $o_voucher_serie->load($sox_id);
        } else {
            $a_serie_params['oxvoucherseries__oxid'] = null;
        }
        //Disable editing for derived items
        if ($o_voucher_serie->is_derived()) {
            return;
        }
        $a_serie_params['oxvoucherseries__oxdiscount'] = abs((float) $a_serie_params['oxvoucherseries__oxdiscount']);
        $o_voucher_serie->assign($a_serie_params);
        $o_voucher_serie->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_voucher_serie->get_id());
    }
    /**
     * Returns voucher status information array
     *
     * @return array
     */
    public function get_status()
    {
        if ($o_serie = $this->get_voucher_serie()) {
            return $o_serie->count_vouchers();
        }
    }
    /**
     * Overriding parent function, doing nothing..
     */
    public function prepare_export()
    {
    }
    /**
     * Returns voucher serie object
     *
     * @return \OxidEsales\Eshop\Application\Model\VoucherSerie
     */
    protected function get_voucher_serie()
    {
        if ($this->_o_voucher_serie == null) {
            $o_voucher_serie = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_Serie::class);
            $s_id = Registry::get_request()->get_request_escaped_parameter('voucherid');
            if ($o_voucher_serie->load($s_id ?: \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('voucherid'))) {
                $this->_o_voucher_serie = $o_voucher_serie;
            }
        }
        return $this->_o_voucher_serie;
    }
    /**
     * Prepares Export
     */
    public function start(): void
    {
        $s_voucher_nr = trim((string) Registry::get_request()->get_request_escaped_parameter('voucherNr'));
        $b_random_nr = Registry::get_request()->get_request_escaped_parameter('randomVoucherNr');
        $controller_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_request_controller_id();
        if ($controller_id == 'voucherserie_generate' && !$b_random_nr && empty($s_voucher_nr)) {
            return;
        }
        $this->_a_view_data['refresh'] = 0;
        $this->_a_view_data['iStart'] = 0;
        $i_end = $this->prepare_export();
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iEnd', $i_end);
        $this->_a_view_data['iEnd'] = $i_end;
        // saving export info
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('voucherid', Registry::get_request()->get_request_escaped_parameter('voucherid'));
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('voucherAmount', abs((int) Registry::get_request()->get_request_escaped_parameter('voucherAmount')));
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('randomVoucherNr', $b_random_nr);
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('voucherNr', $s_voucher_nr);
    }
    /**
     * Current view ID getter helps to identify navigation position
     * fix for 0003701, passing dynexportbase::getViewId
     *
     * @return string
     */
    public function get_view_id()
    {
        return \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller::get_view_id();
    }
}