<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Transparent shop utilities class.
 * Some specific utilities, such as fetching article info, etc. (Class may be used
 * for overriding).
 *
 * @subpackage oxcmp
 */
class Utils_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Adds/removes chosen article to/from article comparison list
     *
     * @param object $sProductId product id
     * @param double $dAmount    amount
     * @param array  $aSel       (default null)
     * @param bool   $blOverride allow override
     * @param bool   $blBundle   bundled
     */
    public function to_compare_list($s_product_id = null, $d_amount = null, $a_sel = null, $bl_override = false, $bl_bundle = false): void
    {
        // only if enabled and not search engine..
        if ($this->get_view_config()->get_show_compare_list() && !Registry::get_utils()->is_search_engine()) {
            // #657 special treatment if we want to put on comparelist
            $bl_add_compare = Registry::get_request()->get_request_escaped_parameter('addcompare');
            $bl_remove_compare = Registry::get_request()->get_request_escaped_parameter('removecompare');
            $s_product_id = $s_product_id ?: Registry::get_request()->get_request_escaped_parameter('aid');
            if (($bl_add_compare || $bl_remove_compare) && $s_product_id) {
                // toggle state in session array
                $a_items = Registry::get_session()->get_variable('aFiltcompproducts');
                if ($bl_add_compare && !isset($a_items[$s_product_id])) {
                    $a_items[$s_product_id] = true;
                }
                if ($bl_remove_compare) {
                    unset($a_items[$s_product_id]);
                }
                Registry::get_session()->set_variable('aFiltcompproducts', $a_items);
                $o_parent_view = $this->get_parent();
                // #843C there was problem then field "blIsOnComparisonList" was not set to article object
                if ($o_product = $o_parent_view->get_view_product()) {
                    if (isset($a_items[$o_product->get_id()])) {
                        $o_product->set_on_comparison_list(true);
                    } else {
                        $o_product->set_on_comparison_list(false);
                    }
                }
                $a_view_prods = $o_parent_view->get_view_product_list();
                if (is_array($a_view_prods) && count($a_view_prods)) {
                    foreach ($a_view_prods as $o_product) {
                        if (isset($a_items[$o_product->get_id()])) {
                            $o_product->set_on_comparison_list(true);
                        } else {
                            $o_product->set_on_comparison_list(false);
                        }
                    }
                }
            }
        }
    }
    /**
     * If session user is set loads user noticelist (\OxidEsales\Eshop\Application\Model\User::GetBasket())
     * and adds article to it.
     *
     * @param string $sProductId Product/article ID (default null)
     * @param double $dAmount    amount of good (default null)
     * @param array  $aSel       product selection list (default null)
     */
    public function to_notice_list($s_product_id = null, $d_amount = null, $a_sel = null): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        $this->to_list('noticelist', $s_product_id, $d_amount, $a_sel);
    }
    /**
     * If session user is set loads user wishlist (\OxidEsales\Eshop\Application\Model\User::GetBasket()) and
     * adds article to it.
     *
     * @param string $sProductId Product/article ID (default null)
     * @param double $dAmount    amount of good (default null)
     * @param array  $aSel       product selection list (default null)
     */
    public function to_wish_list($s_product_id = null, $d_amount = null, $a_sel = null): void
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        // only if enabled
        if ($this->get_view_config()->get_show_wishlist()) {
            $this->to_list('wishlist', $s_product_id, $d_amount, $a_sel);
        }
    }
    /**
     * Adds chosen product to defined user list. if amount is 0, item is removed from the list
     *
     * @param string $sListType  user product list type
     * @param string $sProductId product id
     * @param double $dAmount    product amount
     * @param array  $aSel       product selection list
     */
    protected function to_list($s_list_type, $s_product_id, $d_amount, $a_sel)
    {
        // only if user is logged in
        if ($o_user = $this->get_user()) {
            $s_product_id = $s_product_id ?: Registry::get_request()->get_request_escaped_parameter('itmid');
            $s_product_id = $s_product_id ?: Registry::get_request()->get_request_escaped_parameter('aid');
            $d_amount ??= Registry::get_request()->get_request_escaped_parameter('am');
            $a_sel = $a_sel ?: Registry::get_request()->get_request_escaped_parameter('sel');
            // processing amounts
            $d_amount = str_replace(',', '.', $d_amount);
            if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blAllowUnevenAmounts')) {
                $d_amount = round((string) $d_amount);
            }
            $o_basket = $o_user->get_basket($s_list_type);
            $o_basket->add_item_to_basket($s_product_id, abs($d_amount), $a_sel, $d_amount == 0);
            // recalculate basket count
            $o_basket->get_item_count(true);
        }
    }
    /**
     *  Set view data, call parent::render
     */
    public function render(): void
    {
        parent::render();
        $o_parent_view = $this->get_parent();
        // add content for main menu
        $o_content_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Content_List::class);
        $o_content_list->load_main_menulist();
        $o_parent_view->set_menue_list($o_content_list);
    }
}