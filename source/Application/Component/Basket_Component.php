<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception;
use Oxid_Esales\Eshop\Core\Exception\No_Article_Exception;
use Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\Basket_Changed_Event;
use function Ox_New;
use Psr\Log\Logger_Interface;
use stdClass;
/**
 * Main shopping basket manager. Arranges shopping basket
 * contents, updates amounts, prices, taxes etc.
 *
 * @subpackage oxcmp
 */
class Basket_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Last call function name
     *
     * @var string
     */
    protected $_s_last_call_fnc;
    protected bool $is_basket_calculated = false;
    /**
     * Parameters which are kept when redirecting after user
     * puts something to basket
     *
     * @var array
     */
    public $a_redirect_params = [
        'cnid',
        // category id
        'mnid',
        // manufacturer id
        'anid',
        // active article id
        'tpl',
        // spec. template
        'listtype',
        // list type
        'searchcnid',
        // search category
        'searchvendor',
        // search vendor
        'searchmanufacturer',
        // search manufacturer
        // @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
        'searchrecomm',
        // search recomendation
        'recommid',
    ];
    public function init(): void
    {
        if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
            $basket_reservations = Registry::get_session()->get_basket_reservations();
            if ($basket_reservations) {
                if (!$basket_reservations->get_time_left()) {
                    $basket = Registry::get_session()->get_basket();
                    if ($basket && $basket->get_products_count()) {
                        $this->empty_basket($basket);
                    }
                }
                $basket_reservations->discard_unused_reservations(Container_Facade::get_parameter('oxid_esales.basket_reservation_cleanup_rate'));
            }
        }
        parent::init();
        // Basket exclude
        if (Registry::get_config()->get_config_param('blBasketExcludeEnabled')) {
            $basket = Registry::get_session()->get_basket();
            if ($basket) {
                $this->get_parent()->set_root_cat_changed($this->is_root_cat_changed() && $basket->get_contents());
            }
        }
    }
    /**
     * Loads basket ($oBasket = $mySession->getBasket()), calls oBasket->calculateBasket,
     * executes parent::render() and returns basket object.
     *
     * @return object   $oBasket    basket object
     */
    public function render()
    {
        $session = Registry::get_session();
        if ($basket = $session->get_basket()) {
            if (!$this->is_basket_calculated) {
                $basket->calculate_basket(true);
                $this->is_basket_calculated = true;
            }
        }
        parent::render();
        return $basket;
    }
    /**
     * Basket content update controller.
     * Before adding article - check if client is not a search engine. If
     * yes - exits method by returning false. If no - executes
     * oxcmp_basket::_addItems() and puts article to basket.
     * Returns position where to redirect user browser.
     *
     * @param string $sProductId Product ID (default null)
     * @param double $dAmount    Product amount (default null)
     * @param array  $aSel       (default null)
     * @param array  $aPersParam (default null)
     * @param bool   $blOverride If true amount in basket is replaced by $dAmount otherwise amount is increased by
     *                           $dAmount (default false)
     *
     * @return mixed
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function to_basket($s_product_id = null, $d_amount = null, $a_sel = null, $a_pers_param = null, $bl_override = false)
    {
        if (Registry::get_session()->get_id() && Registry::get_session()->is_actual_sid_in_cookie() && !Registry::get_session()->check_session_challenge()) {
            Container_Facade::get(Logger_Interface::class)->warning('EXCEPTION_NON_MATCHING_CSRF_TOKEN');
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_NON_MATCHING_CSRF_TOKEN');
            return;
        }
        // adding to basket is not allowed ?
        $my_config = Registry::get_config();
        if (Registry::get_utils()->is_search_engine()) {
            return;
        }
        // adding articles
        if ($a_products = $this->get_items($s_product_id, $d_amount, $a_sel, $a_pers_param, $bl_override)) {
            $this->set_last_call_fnc('tobasket');
            $database = Database_Provider::get_db();
            $database->start_transaction();
            try {
                $o_basket_item = $this->add_items($a_products);
                //reserve active basket
                if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
                    $basket = Registry::get_session()->get_basket();
                    Registry::get_session()->get_basket_reservations()->reserve_basket($basket);
                }
            } catch (\Exception $exception) {
                $database->rollback_transaction();
                unset($o_basket_item);
                throw $exception;
            }
            $database->commit_transaction();
            // new basket item marker
            if ($o_basket_item && $my_config->get_config_param('iNewBasketItemMessage') != 0) {
                $o_new_item = new stdClass();
                $o_new_item->s_title = $o_basket_item->get_title();
                $o_new_item->s_id = $o_basket_item->get_product_id();
                $o_new_item->d_amount = $o_basket_item->get_amount();
                $o_new_item->d_bundled_amount = $o_basket_item->getd_bundled_amount();
                // passing article
                Registry::get_session()->set_variable('_newitem', $o_new_item);
            }
            // redirect to basket
            $redirect_url = $this->get_redirect_url();
            Container_Facade::dispatch(new Basket_Changed_Event($this));
            return $redirect_url;
        }
    }
    /**
     * Similar to tobasket, except that as product id "bindex" parameter is (can be) taken
     *
     * @param string $sProductId Product ID (default null)
     * @param double $dAmount    Product amount (default null)
     * @param array  $aSel       (default null)
     * @param array  $aPersParam (default null)
     * @param bool   $blOverride If true means increase amount of chosen article (default false)
     *
     * @return mixed
     */
    public function change_basket($product_id = null, $amount = null, $sel = null, $pers_param = null, $override = true)
    {
        if (!Registry::get_session()->check_session_challenge()) {
            return;
        }
        if (Registry::get_utils()->is_search_engine()) {
            return;
        }
        $session = Registry::get_session();
        if (!$product_id) {
            $basket_item_id = Registry::get_request()->get_request_escaped_parameter('bindex');
            if ($basket_item_id) {
                $basket = $session->get_basket();
                $basket_contents = $basket->get_contents();
                $item = $basket_contents[$basket_item_id];
                $product_id = isset($item) ? $item->get_product_id() : null;
            } else {
                $product_id = Registry::get_request()->get_request_escaped_parameter('aid');
            }
        }
        $amount ??= Registry::get_request()->get_request_escaped_parameter('am');
        $sel ??= Registry::get_request()->get_request_escaped_parameter('sel');
        $pers_param = $pers_param ?: Registry::get_request()->get_request_escaped_parameter('persparam');
        if ($products = $this->get_items($product_id, $amount, $sel, $pers_param, $override)) {
            $basket = $session->get_basket();
            $basket->on_update();
            $this->set_last_call_fnc('changebasket');
            $database = Database_Provider::get_db();
            $database->start_transaction();
            try {
                $basket_item = $this->add_items($products);
                if (Registry::get_config()->get_config_param('blPsBasketReservationEnabled')) {
                    Registry::get_session()->get_basket_reservations()->reserve_basket($basket);
                }
            } catch (No_Article_Exception) {
                return $this->get_redirect_url();
            } catch (\Exception $exception) {
                $database->rollback_transaction();
                unset($basket_item);
                throw $exception;
            }
            $database->commit_transaction();
        }
    }
    /**
     * Formats and returns redirect URL where shop must be redirected after
     * storing something to basket
     *
     * @return string   $sClass.$sPosition  redirection URL
     */
    protected function get_redirect_url()
    {
        // active controller id
        $controller_id = Registry::get_config()->get_request_controller_id();
        $controller_id = $controller_id ? $controller_id . '?' : 'start?';
        $s_position = '';
        // setting redirect parameters
        foreach ($this->a_redirect_params as $s_param_name) {
            $s_param_val = Registry::get_request()->get_request_escaped_parameter($s_param_name);
            $s_position .= $s_param_val ? $s_param_name . '=' . $s_param_val . '&' : '';
        }
        // special treatment
        // search param
        if ($s_param = Registry::get_request()->get_request_parameter('searchparam')) {
            $s_position .= 'searchparam=' . rawurlencode((string) $s_param) . '&';
        }
        // current page number
        $i_page_nr = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
        $s_position .= $i_page_nr > 0 ? 'pgNr=' . $i_page_nr . '&' : '';
        // reload and backbutton blocker
        if (Registry::get_config()->get_config_param('iNewBasketItemMessage') == 3) {
            // saving return to shop link to session
            Registry::get_session()->set_variable('_backtoshop', $controller_id . $s_position);
            // redirecting to basket
            $controller_id = 'basket?';
        }
        return $controller_id . $s_position;
    }
    /**
     * Cleans and returns persisted parameters.
     *
     * @param array $persistedParameters key-value parameters (optional). If not passed - takes parameters from request.
     *
     * @return array|null cleaned up parameters or null, if there are no non-empty parameters
     */
    protected function get_persisted_parameters($persisted_parameters = null)
    {
        $persisted_parameters = $persisted_parameters ?: Registry::get_request()->get_request_escaped_parameter('persparam');
        if (!is_array($persisted_parameters)) {
            return null;
        }
        return array_filter($persisted_parameters) ?: null;
    }
    /**
     * Collects and returns array of items to add to basket. Product info is taken not only from
     * given parameters, but additionally from request 'aproducts' parameter
     *
     * @param string $sProductId product ID
     * @param double $dAmount    product amount
     * @param array  $aSel       product select lists
     * @param array  $aPersParam product persistent parameters
     * @param bool   $blOverride amount override status
     *
     * @return mixed
     */
    protected function get_items($s_product_id = null, $d_amount = null, $a_sel = null, $a_pers_param = null, $bl_override = false)
    {
        // collecting items to add
        $a_products = Registry::get_request()->get_request_escaped_parameter('aproducts');
        // collecting specified item
        $s_product_id = $s_product_id ?: Registry::get_request()->get_request_escaped_parameter('aid');
        if ($s_product_id) {
            // additionally fetching current product info
            $d_amount ??= Registry::get_request()->get_request_escaped_parameter('am');
            // select lists
            $a_sel ??= Registry::get_request()->get_request_escaped_parameter('sel');
            // persistent parameters
            if (empty($a_pers_param)) {
                $a_pers_param = $this->get_persisted_parameters();
            }
            $s_basket_item_id = Registry::get_request()->get_request_escaped_parameter('bindex');
            $a_products[$s_product_id] = ['am' => $d_amount, 'sel' => $a_sel, 'persparam' => $a_pers_param, 'override' => $bl_override, 'basketitemid' => $s_basket_item_id];
        }
        if (is_array($a_products) && count($a_products)) {
            if (Registry::get_request()->get_request_escaped_parameter('removeBtn') !== null) {
                //setting amount to 0 if removing article from basket
                foreach ($a_products as $s_product_id => $a_product) {
                    if (isset($a_product['remove']) && $a_product['remove']) {
                        $a_products[$s_product_id]['am'] = 0;
                    } else {
                        unset($a_products[$s_product_id]);
                    }
                }
            }
            return $a_products;
        }
        return false;
    }
    /**
     * Adds all articles user wants to add to basket. Returns
     * last added to basket item.
     *
     * @param array $products products to add array
     *
     * @return  object  $oBasketItem    last added basket item
     */
    protected function add_items($products)
    {
        $active_view = Registry::get_config()->get_active_view();
        $error_destination = $active_view->get_error_destination();
        $session = Registry::get_session();
        $basket = $session->get_basket();
        $basket_info = $basket->get_basket_summary();
        $basket_item_amounts = [];
        foreach ($products as $add_product_id => $product_info) {
            $data = $this->prepare_product_information($add_product_id, $product_info);
            $product_amount = 0;
            if (isset($basket_info->a_articles[$data['id']])) {
                $product_amount = $basket_info->a_articles[$data['id']];
            }
            $products[$add_product_id]['oldam'] = $product_amount;
            //If we already changed articles so they now exactly match existing ones,
            //we need to make sure we get the amounts correct
            if ($data['oldBasketItemId'] !== null && isset($basket_item_amounts[$data['oldBasketItemId']])) {
                $data['amount'] = $data['amount'] + $basket_item_amounts[$data['oldBasketItemId']];
            }
            $basket_item = $this->add_item_to_basket($basket, $data, $error_destination);
            if ($basket_item instanceof \Oxid_Esales\Eshop\Application\Model\Basket_Item) {
                $basket_item_key = $basket_item->get_basket_item_key();
                if ($basket_item_key) {
                    if (!isset($basket_item_amounts[$basket_item_key])) {
                        $basket_item_amounts[$basket_item_key] = 0;
                    }
                    $basket_item_amounts[$basket_item_key] += $data['amount'];
                }
            }
            if (!$basket_item) {
                $info = $basket->get_basket_summary();
                $products[$add_product_id]['am'] = $info->a_articles[$data['id']] ?? 0;
            }
        }
        //if basket empty remove possible gift card
        if ($basket->get_products_count() == 0) {
            $basket->set_card_id(null);
        }
        // information that last call was tobasket
        $this->set_last_call($this->get_last_call_fnc(), $products, $basket_info);
        return $basket_item;
    }
    /**
     * Setting last call data to session (data used by econda)
     *
     * @param string $sCallName    name of action ('tobasket', 'changebasket')
     * @param array  $aProductInfo data which comes from request when you press button "to basket"
     * @param array  $aBasketInfo  array returned by \OxidEsales\Eshop\Application\Model\Basket::getBasketSummary()
     */
    protected function set_last_call($s_call_name, $a_product_info, $a_basket_info)
    {
        Registry::get_session()->set_variable('aLastcall', [$s_call_name => $a_product_info]);
    }
    /**
     * Setting last call function name (data used by econda)
     *
     * @param string $sCallName name of action ('tobasket', 'changebasket')
     */
    protected function set_last_call_fnc($s_call_name)
    {
        $this->_s_last_call_fnc = $s_call_name;
    }
    /**
     * Getting last call function name (data used by econda)
     *
     * @return string
     */
    protected function get_last_call_fnc()
    {
        return $this->_s_last_call_fnc;
    }
    /**
     * Returns true if active root category was changed
     *
     * @return bool
     */
    public function is_root_cat_changed()
    {
        // in Basket
        $session = Registry::get_session();
        $o_basket = $session->get_basket();
        if ($o_basket->show_cat_change_warning()) {
            $o_basket->set_cat_change_warning_state(false);
            return true;
        }
        // in Category, only then category is empty ant not equal to default category
        $s_def_cat = Registry::get_config()->get_active_shop()->oxshops__oxdefcat->value;
        $s_act_cat = Registry::get_request()->get_request_escaped_parameter('cnid');
        $o_act_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        if ($s_act_cat && $s_act_cat != $s_def_cat && $o_act_cat->load($s_act_cat)) {
            $s_act_root = $o_act_cat->oxcategories__oxrootid->value;
            if ($o_basket->get_basket_root_cat_id() && $s_act_root != $o_basket->get_basket_root_cat_id()) {
                return true;
            }
        }
        return false;
    }
    /**
     * Executes user choice:
     *
     * - if user clicked on "Proceed to checkout" - redirects to basket,
     * - if clicked "Continue shopping" - clear basket
     *
     * @return mixed
     */
    public function execute_user_choice()
    {
        Container_Facade::dispatch(new Basket_Changed_Event($this));
        // redirect to basket
        if (Registry::get_request()->get_request_escaped_parameter('tobasket')) {
            return 'basket';
        }
        // clear basket
        $session = Registry::get_session();
        $session->get_basket()->delete_basket();
        $this->get_parent()->set_root_cat_changed(false);
    }
    /**
     * Deletes user basket object from session and saved one from DB if needed.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket
     */
    protected function empty_basket($o_basket)
    {
        $o_basket->delete_basket();
    }
    /**
     * Prepare information for adding product to basket.
     *
     * @param string $addProductId
     * @param array  $productInfo
     *
     * @return array
     */
    protected function prepare_product_information($add_product_id, $product_info)
    {
        $return = [];
        $return['id'] = $product_info['aid'] ?? $add_product_id;
        $return['amount'] = $product_info['am'] ?? 0;
        $return['selectList'] = $product_info['sel'] ?? null;
        $return['persistentParameters'] = $this->get_persisted_parameters($product_info['persparam'] ?? null);
        $return['override'] = $product_info['override'] ?? null;
        $return['bundle'] = isset($product_info['bundle']) ? true : false;
        $return['oldBasketItemId'] = $product_info['basketitemid'] ?? null;
        return $return;
    }
    /**
     * Add one item to basket. Handle eventual errors.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $basket
     * @param array                                      $itemData
     * @param string                                     $errorDestination
     *
     * @return null|\OxidEsales\Eshop\Application\Model\BasketItem
     */
    protected function add_item_to_basket($basket, $item_data, $error_destination)
    {
        $basket_item = null;
        try {
            $basket_item = $basket->add_to_basket($item_data['id'], $item_data['amount'], $item_data['selectList'], $item_data['persistentParameters'], $item_data['override'], $item_data['bundle'], $item_data['oldBasketItemId']);
        } catch (Out_Of_Stock_Exception $exception) {
            $exception->set_destination($error_destination);
            // #950 Change error destination to basket popup
            if (!$error_destination && Registry::get_config()->get_config_param('iNewBasketItemMessage') == 2) {
                $error_destination = 'popup';
            }
            Registry::get_utils_view()->add_error_to_display($exception, false, (bool) $error_destination, $error_destination);
        } catch (Article_Input_Exception $exception) {
            //add to display at specific position
            $exception->set_destination($error_destination);
            Registry::get_utils_view()->add_error_to_display($exception, false, (bool) $error_destination, $error_destination);
        } catch (No_Article_Exception) {
            //ignored, best solution F ?
        }
        return $basket_item;
    }
}