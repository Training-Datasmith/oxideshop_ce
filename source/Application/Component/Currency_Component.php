<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Currency manager class.
 *
 * @subpackage oxcmp
 */
class Currency_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Array of available currencies.
     *
     * @var array
     */
    public $a_currencies;
    /**
     * Active currency object.
     *
     * @var object
     */
    protected $_o_act_cur;
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Checks for currency parameter set in URL, session or post
     * variables. If such were found - loads all currencies possible
     * in shop, searches if passed is available (if no - default
     * currency is set the first defined in admin). Then sets currency
     * parameter so session ($myConfig->setActShopCurrency($iCur)),
     * loads basket and forces ir to recalculate (oBasket->blCalcNeeded
     * = true). Finally executes parent::init().
     */
    public function init(): void
    {
        // Performance
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if (!$my_config->get_config_param('bl_perfLoadCurrency')) {
            //#861C -  show first currency
            $a_currencies = $my_config->get_currency_array();
            $this->_o_act_cur = current($a_currencies);
            return;
        }
        $i_cur = Registry::get_request()->get_request_escaped_parameter('cur');
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        if (isset($i_cur)) {
            $a_currencies = $my_config->get_currency_array();
            if (!isset($a_currencies[$i_cur])) {
                $i_cur = 0;
            }
            // set new currency
            $my_config->set_act_shop_currency($i_cur);
            // recalc basket
            $o_basket = $session->get_basket();
            $o_basket->on_update();
        }
        $i_act_cur = $my_config->get_shop_currency();
        $this->a_currencies = $my_config->get_currency_array($i_act_cur);
        $this->_o_act_cur = $this->a_currencies[$i_act_cur];
        //setting basket currency (M:825)
        if (!isset($o_basket)) {
            $o_basket = $session->get_basket();
        }
        $o_basket->set_basket_currency($this->_o_act_cur);
        parent::init();
    }
    /**
     * Executes parent::render(), passes currency object to template
     * engine and returns currencies array.
     *
     * Template variables:
     * <b>currency</b>
     *
     * @return array
     */
    public function render()
    {
        parent::render();
        $o_parent_view = $this->get_parent();
        $o_parent_view->set_act_currency($this->_o_act_cur);
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadCurrency')) {
            $o_url_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils_url();
            $s_url = $o_url_utils->clean_url(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view()->get_link(), ['cur']);
            reset($this->a_currencies);
            foreach ($this->a_currencies as $o_item) {
                $o_item->link = $o_url_utils->process_url($s_url, true, ['cur' => $o_item->id]);
            }
        }
        return $this->a_currencies;
    }
}