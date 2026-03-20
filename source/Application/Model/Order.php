<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
use Oxid_Esales\Eshop\Application\Model\Payment as EshopPayment;
use Oxid_Esales\Eshop\Application\Model\Voucher as EshopVoucherModel;
use Oxid_Esales\Eshop\Core\Counter;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Price as ShopPrice;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Order manager.
 * Performs creation assigning, updating, deleting and other order functions.
 */
class Order extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    // defining order state constants
    /**
     * Error while sending order notification mail to customer
     *
     * @var int
     */
    public const ORDER_STATE_MAILINGERROR = 0;
    /**
     * Order finalization was completed without errors
     *
     * @var int
     */
    public const ORDER_STATE_OK = 1;
    /**
     * Error during payment execution
     *
     * @var int
     */
    public const ORDER_STATE_PAYMENTERROR = 2;
    /**
     * Order with such id already exist
     *
     * @var int
     */
    public const ORDER_STATE_ORDEREXISTS = 3;
    /**
     * Delivery parameters used for order are invalid
     *
     * @var int
     */
    public const ORDER_STATE_INVALIDDELIVERY = 4;
    /**
     * Payment parameters used for order are invalid
     *
     * @var int
     */
    public const ORDER_STATE_INVALIDPAYMENT = 5;
    /**
     * Protection parameters used for some data in order are invalid
     *
     * @var int
     */
    public const ORDER_STATE_INVALIDDELADDRESSCHANGED = 7;
    /**
     * Basket price < minimum order price
     *
     * @var int
     */
    public const ORDER_STATE_BELOWMINPRICE = 8;
    /**
     * Voucher cannot be applied
     *
     * @var int
     */
    public const ORDER_STATE_VOUCHERERROR = 9;
    /**
     * Skip update fields
     *
     * @var array
     */
    protected $_a_skip_save_fields = ['oxtimestamp'];
    /**
     * oxList of oxarticle objects
     *
     * @var \oxlist
     */
    protected $_o_articles;
    /**
     * Oxdeliveryset object
     *
     * @var \oxdeliveryset
     */
    protected $_o_del_set;
    /**
     * Gift card
     *
     * @var \oxWrapping
     */
    protected $_o_gift_card;
    /**
     * Payment type
     *
     * @var \oxpayment
     */
    protected $_o_payment_type;
    /**
     * User payment
     *
     * @var \OxidEsales\Eshop\Application\Model\UserPayment
     */
    protected $_o_payment;
    /**
     * Order vouchers marked as used
     *
     * @var array
     */
    protected $_a_voucher_list;
    /**
     * Order delivery costs price object
     *
     * @var ShopPrice
     */
    protected $_o_del_price;
    /**
     * Order user
     *
     * @var \OxidEsales\Eshop\Application\Model\User
     */
    protected $_o_user;
    /**
     * Order basket
     *
     * @var \OxidEsales\Eshop\Application\Model\Basket
     */
    protected $_o_basket;
    /**
     * Order wrapping costs price object
     *
     * @var ShopPrice
     */
    protected $_o_wrapping_price;
    /**
     * Order gift card price object
     *
     * @var ShopPrice
     */
    protected $_o_gift_card_price;
    /**
     * Order payment costs price object
     *
     * @var ShopPrice
     */
    protected $_o_payment_price;
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxorder';
    /**
     * Useage of seperate orders numbering for different shops
     *
     * @var bool
     */
    protected $_bl_separate_numbering;
    /**
     * Order language id
     *
     * @var int
     */
    protected $_i_order_lang;
    /**
     * If true delivery will be recalculated while recalculating order
     *
     * @var bool
     */
    protected $_bl_reload_delivery = true;
    /**
     * If true discount will be recalculated while recalculating order
     *
     * @var bool
     */
    protected $_bl_reload_discount = true;
    /**
     * Current order currency object
     *
     * @var \stdClass
     */
    protected $_o_order_currency;
    /**
     * Current order files object
     *
     * @var object
     */
    protected $_o_order_files;
    /**
     * Shipment tracking url
     *
     * @var string
     */
    protected $_s_ship_track_url;
    /**
     * @var \OxidEsales\Eshop\Application\Model\Basket
     */
    protected $_o_order_basket;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxorder');
        // set usage of separate orders numbering for different shops
        $this->set_separate_numbering(Registry::get_config()->get_config_param('blSeparateNumbering'));
    }
    /**
     * Getter made for order delivery set object access
     *
     * @param string $sName parameter name
     *
     * @return mixed
     */
    public function __get($s_name)
    {
        if ($s_name == 'oDelSet') {
            return $this->get_del_set();
        }
        if ($s_name == 'oxorder__oxbillcountry') {
            return $this->get_bill_country();
        }
        if ($s_name == 'oxorder__oxdelcountry') {
            return $this->get_del_country();
        }
    }
    /**
     * Assigns data, stored in DB to oxorder object
     *
     * @param mixed $dbRecord DB record
     */
    public function assign($db_record): void
    {
        parent::assign($db_record);
        $o_utils_date = Registry::get_utils_date();
        // convert date's to international format
        $this->oxorder__oxorderdate = new Field($o_utils_date->format_db_date($this->oxorder__oxorderdate->value));
        $this->oxorder__oxsenddate = new Field($o_utils_date->format_db_date($this->oxorder__oxsenddate->value));
    }
    /**
     * Gets country title by country id.
     *
     * @param string $sCountryId country ID
     *
     * @return string
     */
    protected function get_country_title($s_country_id)
    {
        $s_title = null;
        if ($s_country_id && $s_country_id != '-1') {
            $o_country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
            $o_country->load_in_lang($this->get_order_language(), $s_country_id);
            $s_title = $o_country->oxcountry__oxtitle->value;
        }
        return $s_title;
    }
    /**
     * returned assigned orderarticles from order
     *
     * @param bool $blExcludeCanceled excludes canceled items from list
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected function get_articles($bl_exclude_canceled = false)
    {
        $s_select = 'SELECT `oxorderarticles`.* FROM `oxorderarticles`
             WHERE `oxorderarticles`.`oxorderid` = :oxorderid' . ($bl_exclude_canceled ? ' AND `oxorderarticles`.`oxstorno` != 1 ' : ' ') . ' ORDER BY `oxorderarticles`.`oxartid`, `oxorderarticles`.`oxselvariant`,' . ' `oxorderarticles`.`oxpersparam` ';
        // order articles
        $o_articles = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_articles->init('oxorderarticle');
        $o_articles->select_string($s_select, ['oxorderid' => (string) $this->get_id()]);
        return $o_articles;
    }
    /**
     * Assigns data, stored in oxorderarticles to oxorder object .
     *
     * @param bool $blExcludeCanceled excludes canceled items from list
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_order_articles($bl_exclude_canceled = false)
    {
        // checking set value
        if ($bl_exclude_canceled) {
            return $this->get_articles(true);
        }
        // checking set value
        if ($this->_o_articles === null) {
            $this->_o_articles = $this->get_articles();
        }
        return $this->_o_articles;
    }
    /**
     * Order article list setter
     *
     * @param \OxidEsales\Eshop\Application\Model\OrderArticleList $oOrderArticleList
     */
    public function set_order_article_list($o_order_article_list): void
    {
        $this->_o_articles = $o_order_article_list;
    }
    /**
     * Returns order delivery expenses price object
     *
     * @return ShopPrice
     */
    public function get_order_delivery_price()
    {
        if ($this->_o_del_price != null) {
            return $this->_o_del_price;
        }
        $this->_o_del_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $this->_o_del_price->set_brutto_price_mode();
        $this->_o_del_price->set_price($this->oxorder__oxdelcost->value, $this->oxorder__oxdelvat->value);
        return $this->_o_del_price;
    }
    /**
     * Returns order wrapping expenses price object
     *
     * @return ShopPrice
     */
    public function get_order_wrapping_price()
    {
        if ($this->_o_wrapping_price != null) {
            return $this->_o_wrapping_price;
        }
        $this->_o_wrapping_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $this->_o_wrapping_price->set_brutto_price_mode();
        $this->_o_wrapping_price->set_price($this->oxorder__oxwrapcost->value, $this->oxorder__oxwrapvat->value);
        return $this->_o_wrapping_price;
    }
    /**
     * Returns order wrapping expenses price object
     *
     * @return ShopPrice
     */
    public function get_order_gift_card_price()
    {
        if ($this->_o_gidt_card_price != null) {
            return $this->_o_gidt_card_price;
        }
        $this->_o_gidt_card_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $this->_o_gidt_card_price->set_brutto_price_mode();
        $this->_o_gidt_card_price->set_price($this->oxorder__oxgiftcardcost->value, $this->oxorder__oxgiftcardvat->value);
        return $this->_o_gidt_card_price;
    }
    /**
     * Returns order payment expenses price object
     *
     * @return ShopPrice
     */
    public function get_order_payment_price()
    {
        if ($this->_o_payment_price != null) {
            return $this->_o_payment_price;
        }
        $this->_o_payment_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $this->_o_payment_price->set_brutto_price_mode();
        $this->_o_payment_price->set_price($this->oxorder__oxpaycost->value, $this->oxorder__oxpayvat->value);
        return $this->_o_payment_price;
    }
    /**
     * Returns order netto sum (total order price - VAT)
     *
     * @return double
     */
    public function get_order_net_sum()
    {
        $d_total_net_sum = 0;
        $d_total_net_sum += $this->oxorder__oxtotalnetsum->value;
        $d_total_net_sum += $this->get_order_delivery_price()->get_netto_price();
        $d_total_net_sum += $this->get_order_wrapping_price()->get_netto_price();
        return $d_total_net_sum + $this->get_order_payment_price()->get_netto_price();
    }
    /**
     * Order checking, processing and saving method.
     * Before saving performed checking if order is still not executed (checks in
     * database oxorder table for order with know ID), if yes - returns error code 3,
     * if not - loads payment data, assigns all info from basket to new Order object
     * and saves full order with error status. Then executes payment.
     * On failure - deletes order and returns error code 2.
     * On success - saves order (\OxidEsales\Eshop\Application\Model\Order::save()),
     * removes article from wishlist (\OxidEsales\Eshop\Application\Model\Order::_updateWishlist()),
     * updates voucher data (\OxidEsales\Eshop\Application\Model\Order::_markVouchers()).
     * Finally sends order confirmation email to customer (\OxidEsales\Eshop\Core\Email::SendOrderEMailToUser())
     * and shop owner (\OxidEsales\Eshop\Core\Email::SendOrderEMailToOwner()).
     * If this is order recalculation, skipping payment execution, marking vouchers as used
     * and sending order by email to shop owner and user
     * Mailing status (1 if OK, 0 on error) is returned.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket              Basket object
     * @param object                                     $oUser                Current User object
     * @param bool                                       $blRecalculatingOrder Order recalculation
     *
     * @return integer
     */
    public function finalize_order(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket, $o_user, $bl_recalculating_order = false)
    {
        // check if this order is already stored
        $order_id = Registry::get_session()->get_variable('sess_challenge');
        if ($this->check_order_exist($order_id)) {
            Registry::get_logger()->debug('finalizeOrder: Order already exists: ' . $order_id, [$o_basket, $o_user]);
            // we might use this later, this means that somebody clicked like mad on order button
            return self::ORDER_STATE_ORDEREXISTS;
        }
        // if not recalculating order, use sess_challenge id, else leave old order id
        if (!$bl_recalculating_order) {
            // use this ID
            $this->set_id($order_id);
            // validating various order/basket parameters before finalizing
            if ($i_order_state = $this->validate_order($o_basket, $o_user)) {
                return $i_order_state;
            }
        }
        // copies user info
        $this->assign_user_information($o_user);
        // copies basket info
        $this->load_from_basket($o_basket);
        // payment information
        $o_user_payment = $this->set_payment($o_basket->get_payment_id());
        // set folder information, if order is new
        // #M575 in recalculating order case folder must be the same as it was
        if (!$bl_recalculating_order) {
            $this->set_folder();
        }
        // marking as not finished
        $this->set_order_status('NOT_FINISHED');
        //saving all order data to DB
        $this->save();
        // executing payment (on failure deletes order and returns error code)
        // in case when recalculating order, payment execution is skipped
        if (!$bl_recalculating_order) {
            $bl_ret = $this->execute_payment($o_basket, $o_user_payment);
            if ($bl_ret !== true) {
                return $bl_ret;
            }
        }
        if (!$this->get_field_data('oxordernr')) {
            $this->set_number();
        } else {
            ox_new(Counter::class)->update($this->get_counter_ident(), $this->oxorder__oxordernr->value);
        }
        // deleting remark info only when order is finished
        Registry::get_session()->delete_variable('ordrem');
        //#4005: Order creation time is not updated when order processing is complete
        if (!$bl_recalculating_order) {
            $this->update_order_date();
        }
        // updating order trans status (success status)
        $this->set_order_status('OK');
        // store orderid
        $o_basket->set_order_id($this->get_id());
        // updating wish lists
        $this->update_wishlist($o_basket->get_contents(), $o_user);
        // updating users notice list
        $this->update_notice_list($o_basket->get_contents(), $o_user);
        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        // skipping this action in case of order recalculation
        // send order by email to shop owner and current user
        // skipping this action in case of order recalculation
        if (!$bl_recalculating_order) {
            $this->mark_vouchers($o_basket, $o_user);
            return $this->send_order_by_email($o_user, $o_basket, $o_user_payment);
        }
        return self::ORDER_STATE_OK;
    }
    /**
     * Return true if order store in netto mode
     *
     * @return bool
     */
    public function is_netto_mode()
    {
        return (bool) $this->oxorder__oxisnettomode->value;
    }
    /**
     * Updates order transaction status. Faster than saving whole object
     *
     * @param string $sStatus order transaction status
     */
    protected function set_order_status($s_status)
    {
        $o_db = Database_Provider::get_db();
        $s_q = 'update oxorder set oxtransstatus = :oxtransstatus where oxid = :oxid';
        $o_db->execute($s_q, ['oxtransstatus' => $s_status, 'oxid' => $this->get_id()]);
        //updating order object
        $this->oxorder__oxtransstatus = new Field($s_status, Field::T_RAW);
    }
    /**
     * Converts string VAT representation into float e.g. 7,6 to 7.6
     *
     * @param string $sVat vat value
     *
     * @return float
     */
    protected function convert_vat($s_vat)
    {
        if (strpos($s_vat, '.') < strpos($s_vat, ',')) {
            $s_vat = str_replace(['.', ','], ['', '.'], $s_vat);
        } else {
            $s_vat = str_replace(',', '', $s_vat);
        }
        return (float) $s_vat;
    }
    /**
     * Reset Vat info
     */
    protected function reset_vats()
    {
        $this->oxorder__oxartvat1 = new Field(null);
        $this->oxorder__oxartvatprice1 = new Field(null);
        $this->oxorder__oxartvat2 = new Field(null);
        $this->oxorder__oxartvatprice2 = new Field(null);
    }
    /**
     * Gathers and assigns to new oxOrder object customer data, payment, delivery
     * and shipping info, customer order remark, currency, voucher, language data.
     * Additionally stores general discount and wrapping. Sets order status to "error"
     * and creates oxOrderArticle objects and assigns to them basket articles.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket Shopping basket object
     */
    protected function load_from_basket(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket)
    {
        $my_config = Registry::get_config();
        // store IP Address - default must be FALSE as it is illegal to store
        if ($my_config->get_config_param('blStoreIPs') && $this->oxorder__oxip->value === null) {
            $this->oxorder__oxip = new Field(Registry::get_utils_server()->get_remote_address(), Field::T_RAW);
        }
        //setting view mode
        $this->oxorder__oxisnettomode = new Field($o_basket->is_calculation_mode_netto());
        // copying main price info
        $this->oxorder__oxtotalnetsum = new Field($o_basket->get_netto_sum());
        $this->oxorder__oxtotalbrutsum = new Field($o_basket->get_brutto_sum());
        $this->oxorder__oxtotalordersum = new Field($o_basket->get_price()->get_brutto_price(), Field::T_RAW);
        // copying discounted VAT info
        $this->reset_vats();
        $i_vat_index = 1;
        foreach ($o_basket->get_product_vats(false) as $i_vat => $d_price) {
            $this->{"oxorder__oxartvat{$i_vat_index}"} = new Field($this->convert_vat($i_vat), Field::T_RAW);
            $this->{"oxorder__oxartvatprice{$i_vat_index}"} = new Field($d_price, Field::T_RAW);
            $i_vat_index++;
        }
        // payment costs if available
        if ($o_payment_cost = $o_basket->get_costs('oxpayment')) {
            $this->oxorder__oxpaycost = new Field($o_payment_cost->get_brutto_price(), Field::T_RAW);
            $this->oxorder__oxpayvat = new Field($o_payment_cost->get_vat(), Field::T_RAW);
        }
        // delivery info
        if ($o_delivery_cost = $o_basket->get_costs('oxdelivery')) {
            $this->oxorder__oxdelcost = new Field($o_delivery_cost->get_brutto_price(), Field::T_RAW);
            //V #M382: Save VAT, not VAT value for delivery costs
            $this->oxorder__oxdelvat = new Field($o_delivery_cost->get_vat(), Field::T_RAW);
            //V #M382
            $this->oxorder__oxdeltype = new Field($o_basket->get_shipping_id(), Field::T_RAW);
        }
        // user remark
        if (!isset($this->oxorder__oxremark) || !isset($this->oxorder__oxremark->value)) {
            $this->oxorder__oxremark = new Field(Registry::get_session()->get_variable('ordrem'), Field::T_RAW);
        }
        // currency
        $o_cur = $my_config->get_act_shop_currency_object();
        $this->oxorder__oxcurrency = new Field($o_cur->name);
        $this->oxorder__oxcurrate = new Field($o_cur->rate, Field::T_RAW);
        // store voucher discount
        if ($o_voucher_discount = $o_basket->get_voucher_discount()) {
            $this->oxorder__oxvoucherdiscount = new Field($o_voucher_discount->get_brutto_price(), Field::T_RAW);
        }
        // general discount
        if ($this->_bl_reload_discount) {
            $d_discount = 0;
            $a_discounts = $o_basket->get_discounts();
            if (is_array($a_discounts) && count($a_discounts) > 0) {
                foreach ($a_discounts as $o_discount) {
                    $d_discount += $o_discount->d_discount;
                }
            }
            $this->oxorder__oxdiscount = new Field($d_discount, Field::T_RAW);
        }
        //order language
        $this->oxorder__oxlang = new Field($this->get_order_language());
        // initial status - 'ERROR'
        $this->oxorder__oxtransstatus = new Field('ERROR', Field::T_RAW);
        // copies basket product info ...
        $this->set_order_articles($o_basket->get_contents());
        // copies wrapping info
        $this->set_wrapping($o_basket);
    }
    /**
     * Returns language id of current order object. If order already has
     * language defined - checks if this language is defined in shops config
     *
     * @return int
     */
    public function get_order_language()
    {
        if ($this->_i_order_lang === null) {
            if (isset($this->oxorder__oxlang->value)) {
                $this->_i_order_lang = Registry::get_lang()->validate_language($this->oxorder__oxlang->value);
            } else {
                $this->_i_order_lang = Registry::get_lang()->get_base_language();
            }
        }
        return $this->_i_order_lang;
    }
    /**
     * Assigns to new oxorder object customer delivery and shipping info
     *
     * @param object $oUser user object
     */
    protected function assign_user_information($o_user)
    {
        $this->oxorder__oxuserid = new Field($o_user->get_id());
        // bill address
        $this->oxorder__oxbillcompany = clone $o_user->oxuser__oxcompany;
        $this->oxorder__oxbillemail = clone $o_user->oxuser__oxusername;
        $this->oxorder__oxbillfname = clone $o_user->oxuser__oxfname;
        $this->oxorder__oxbilllname = clone $o_user->oxuser__oxlname;
        $this->oxorder__oxbillstreet = clone $o_user->oxuser__oxstreet;
        $this->oxorder__oxbillstreetnr = clone $o_user->oxuser__oxstreetnr;
        $this->oxorder__oxbilladdinfo = clone $o_user->oxuser__oxaddinfo;
        $this->oxorder__oxbillustid = clone $o_user->oxuser__oxustid;
        $this->oxorder__oxbillcity = clone $o_user->oxuser__oxcity;
        $this->oxorder__oxbillcountryid = clone $o_user->oxuser__oxcountryid;
        $this->oxorder__oxbillstateid = clone $o_user->oxuser__oxstateid;
        $this->oxorder__oxbillzip = clone $o_user->oxuser__oxzip;
        $this->oxorder__oxbillfon = clone $o_user->oxuser__oxfon;
        $this->oxorder__oxbillfax = clone $o_user->oxuser__oxfax;
        $this->oxorder__oxbillsal = clone $o_user->oxuser__oxsal;
        // delivery address
        if ($o_del_adress = $this->get_del_address_info()) {
            // set delivery address
            $this->oxorder__oxdelcompany = clone $o_del_adress->oxaddress__oxcompany;
            $this->oxorder__oxdelfname = clone $o_del_adress->oxaddress__oxfname;
            $this->oxorder__oxdellname = clone $o_del_adress->oxaddress__oxlname;
            $this->oxorder__oxdelstreet = clone $o_del_adress->oxaddress__oxstreet;
            $this->oxorder__oxdelstreetnr = clone $o_del_adress->oxaddress__oxstreetnr;
            $this->oxorder__oxdeladdinfo = clone $o_del_adress->oxaddress__oxaddinfo;
            $this->oxorder__oxdelcity = clone $o_del_adress->oxaddress__oxcity;
            $this->oxorder__oxdelcountryid = clone $o_del_adress->oxaddress__oxcountryid;
            $this->oxorder__oxdelstateid = clone $o_del_adress->oxaddress__oxstateid;
            $this->oxorder__oxdelzip = clone $o_del_adress->oxaddress__oxzip;
            $this->oxorder__oxdelfon = clone $o_del_adress->oxaddress__oxfon;
            $this->oxorder__oxdelfax = clone $o_del_adress->oxaddress__oxfax;
            $this->oxorder__oxdelsal = clone $o_del_adress->oxaddress__oxsal;
        }
    }
    /**
     * Assigns wrapping VAT and card price + card message info
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     */
    protected function set_wrapping(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket)
    {
        // wrapping price
        if ($o_wrapping_cost = $o_basket->get_costs('oxwrapping')) {
            $this->oxorder__oxwrapcost = new Field($o_wrapping_cost->get_brutto_price(), Field::T_RAW);
            // wrapping VAT will be always calculated (#3757)
            $this->oxorder__oxwrapvat = new Field($o_wrapping_cost->get_vat(), Field::T_RAW);
        }
        if ($o_gift_card_cost = $o_basket->get_costs('oxgiftcard')) {
            $this->oxorder__oxgiftcardcost = new Field($o_gift_card_cost->get_brutto_price(), Field::T_RAW);
            $this->oxorder__oxgiftcardvat = new Field($o_gift_card_cost->get_vat(), Field::T_RAW);
        }
        // greetings card
        $this->oxorder__oxcardid = new Field($o_basket->get_card_id(), Field::T_RAW);
        // card text will be stored in database
        $this->oxorder__oxcardtext = new Field($o_basket->get_card_message(), Field::T_RAW);
    }
    /**
     * Creates OrderArticle objects and assigns to them basket articles.
     * Updates quantity of sold articles (\OxidEsales\Eshop\Application\Model\Article::updateSoldAmount()).
     *
     * @param array $aArticleList article list
     */
    protected function set_order_articles($a_article_list)
    {
        // reset articles list
        $this->_o_articles = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $i_curr_lang = $this->get_order_language();
        // add all the products we have on basket to the order
        foreach ($a_article_list as $o_content) {
            //$oContent->oProduct = $oContent->getArticle();
            // #M773 Do not use article lazy loading on order save
            $o_product = $o_content->get_article(true, null, true);
            // copy only if object is oxarticle type
            if ($o_product->is_order_article()) {
                $o_order_article = $o_product;
            } else {
                // if order language does not match product language article must be reloaded in order language
                if ($i_curr_lang != $o_product->get_language()) {
                    $o_product->load_in_lang($i_curr_lang, $o_product->get_product_id());
                }
                // set chosen select list
                $s_sel_list = '';
                if (count($a_chosen_sel_list = $o_content->get_chosen_sel_list())) {
                    foreach ($a_chosen_sel_list as $o_item) {
                        if ($s_sel_list) {
                            $s_sel_list .= ', ';
                        }
                        $s_sel_list .= "{$o_item->name} : {$o_item->value}";
                    }
                    if ($s_sel_list !== '' && $o_content->get_var_select() !== '') {
                        $s_sel_list .= ' ||';
                    }
                }
                $o_order_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_Article::class);
                $o_order_article->set_is_new_order_item(true);
                $o_order_article->copy_this($o_product);
                $o_order_article->set_id();
                $o_order_article->oxorderarticles__oxartnum = clone $o_product->oxarticles__oxartnum;
                $o_order_article->oxorderarticles__oxselvariant = new Field(trim($s_sel_list . ' ' . $o_content->get_var_select()), Field::T_RAW);
                $o_order_article->oxorderarticles__oxshortdesc = new Field($o_product->oxarticles__oxshortdesc->get_raw_value(), Field::T_RAW);
                // #M974: duplicated entries for the name of variants in orders
                $o_order_article->oxorderarticles__oxtitle = new Field(trim((string) $o_product->oxarticles__oxtitle->get_raw_value()), Field::T_RAW);
                // copying persistent parameters ...
                $a_pers_params = $o_content->get_pers_params();
                if (is_array($a_pers_params) && count($a_pers_params)) {
                    $o_order_article->oxorderarticles__oxpersparam = new Field(serialize($a_pers_params), Field::T_RAW);
                }
            }
            // ids, titles, numbers ...
            $o_order_article->oxorderarticles__oxorderid = new Field($this->get_id());
            $o_order_article->oxorderarticles__oxartid = new Field($o_content->get_product_id());
            $o_order_article->oxorderarticles__oxamount = new Field($o_content->get_amount());
            // prices
            $o_price = $o_content->get_price();
            $o_order_article->oxorderarticles__oxnetprice = new Field($o_price->get_netto_price(), Field::T_RAW);
            $o_order_article->oxorderarticles__oxvatprice = new Field($o_price->get_vat_value(), Field::T_RAW);
            $o_order_article->oxorderarticles__oxbrutprice = new Field($o_price->get_brutto_price(), Field::T_RAW);
            $o_order_article->oxorderarticles__oxvat = new Field($o_price->get_vat(), Field::T_RAW);
            $o_unit_price = $o_content->get_unit_price();
            $o_order_article->oxorderarticles__oxnprice = new Field($o_unit_price->get_netto_price(), Field::T_RAW);
            $o_order_article->oxorderarticles__oxbprice = new Field($o_unit_price->get_brutto_price(), Field::T_RAW);
            // wrap id
            $o_order_article->oxorderarticles__oxwrapid = new Field($o_content->get_wrapping_id(), Field::T_RAW);
            // items shop id
            $o_order_article->oxorderarticles__oxordershopid = new Field($o_content->get_shop_id(), Field::T_RAW);
            // bundle?
            $o_order_article->oxorderarticles__oxisbundle = new Field($o_content->is_bundle());
            // add information for eMail
            //P
            //TODO: check if this assign is needed at all
            $o_order_article->o_product = $o_product;
            $o_order_article->set_article($o_product);
            // simulation order article list
            $this->_o_articles->offsetSet($o_order_article->get_id(), $o_order_article);
        }
    }
    /**
     * Executes payment. Additionally loads oxPaymentGateway object, initiates
     * it by adding payment parameters (oxPaymentGateway::setPaymentParams())
     * and finally executes it (oxPaymentGateway::executePayment()). On failure -
     * deletes order and returns * error code 2.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket      basket object
     * @param object                                     $oUserpayment user payment object
     *
     * @return  integer 2 or an error code
     */
    protected function execute_payment(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket, $o_userpayment)
    {
        $o_pay_transaction = $this->get_gateway();
        $o_pay_transaction->set_payment_params($o_userpayment);
        if (!$o_pay_transaction->execute_payment($o_basket->get_price()->get_brutto_price(), $this)) {
            $this->delete();
            // checking for error messages
            if (method_exists($o_pay_transaction, 'getLastError')) {
                if ($s_last_error = $o_pay_transaction->get_last_error()) {
                    return $s_last_error;
                }
            }
            // checking for error codes
            if (method_exists($o_pay_transaction, 'getLastErrorNo')) {
                if ($i_last_error_no = $o_pay_transaction->get_last_error_no()) {
                    return $i_last_error_no;
                }
            }
            return self::ORDER_STATE_PAYMENTERROR;
            // means no authentication
        }
        return true;
        // everything fine
    }
    /**
     * Returns the correct gateway. At the moment only switch between default
     * and IPayment, can be extended later.
     *
     * @return object $oPayTransaction payment gateway object
     */
    protected function get_gateway()
    {
        return ox_new(\Oxid_Esales\Eshop\Application\Model\Payment_Gateway::class);
    }
    /**
     * Creates and returns user payment.
     *
     * @param string $sPaymentid used payment id
     *
     * @return \OxidEsales\Eshop\Application\Model\UserPayment
     */
    protected function set_payment($s_paymentid)
    {
        $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
        if (!$o_payment->load($s_paymentid)) {
            return null;
        }
        $a_dynvalue = $this->get_dynamic_values();
        $o_payment->set_dyn_values(Registry::get_utils()->assign_values_from_text($o_payment->oxpayments__oxvaldesc->value));
        // collecting dynamic values
        $a_dyn_val = [];
        if (is_array($a_payment_dyn_values = $o_payment->get_dyn_values())) {
            foreach ($a_payment_dyn_values as $key => $o_val) {
                if (isset($a_dynvalue[$o_val->name])) {
                    $o_val->value = $a_dynvalue[$o_val->name];
                }
                //$oPayment->setDynValue($key, $oVal);
                $a_payment_dyn_values[$key] = $o_val;
                $a_dyn_val[$o_val->name] = $o_val->value;
            }
        }
        // Store this payment information, we might allow users later to
        // reactivate already give payment information
        $o_userpayment = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
        $o_userpayment->oxuserpayments__oxuserid = clone $this->oxorder__oxuserid;
        $o_userpayment->oxuserpayments__oxpaymentsid = new Field($s_paymentid, Field::T_RAW);
        $o_userpayment->oxuserpayments__oxvalue = new Field(Registry::get_utils()->assign_values_to_text($a_dyn_val), Field::T_RAW);
        $o_userpayment->oxpayments__oxdesc = clone $o_payment->oxpayments__oxdesc;
        $o_userpayment->oxpayments__oxlongdesc = clone $o_payment->oxpayments__oxlongdesc;
        $o_userpayment->set_dyn_values($a_payment_dyn_values);
        $o_userpayment->save();
        // storing payment information to order
        $this->oxorder__oxpaymentid = new Field($o_userpayment->get_id(), Field::T_RAW);
        $this->oxorder__oxpaymenttype = clone $o_userpayment->oxuserpayments__oxpaymentsid;
        // returning user payment object which will be used later in code ...
        return $o_userpayment;
    }
    /**
     * Assigns oxfolder as new
     */
    protected function set_folder()
    {
        $my_config = Registry::get_config();
        $this->oxorder__oxfolder = new Field(key($my_config->get_shop_conf_var('aOrderfolder', $my_config->get_shop_id())), Field::T_RAW);
    }
    /**
     * aAdds/removes user chosen article to/from his noticelist
     * or wishlist (oxuserbasket::addItemToBasket()).
     *
     * @param array  $aArticleList basket products
     * @param object $oUser        user object
     */
    protected function update_wishlist($a_article_list, $o_user)
    {
        foreach ($a_article_list as $o_content) {
            if ($s_wish_id = $o_content->get_wish_id()) {
                // checking which wishlist user uses ..
                if ($s_wish_id == $o_user->get_id()) {
                    $o_user_basket = $o_user->get_basket('wishlist');
                } else {
                    $a_where = ['oxuserbaskets.oxuserid' => $s_wish_id, 'oxuserbaskets.oxtitle' => 'wishlist'];
                    $o_user_basket = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Basket::class);
                    $query = $o_user_basket->build_select_string($a_where);
                    $record = Database_Provider::get_db()->select($query);
                    if ($record && $record->count() > 0) {
                        $o_user_basket->assign($record->fields);
                    }
                }
                // updating users wish list
                if ($o_user_basket) {
                    if (!$s_prod_id = $o_content->get_wish_article_id()) {
                        $s_prod_id = $o_content->get_product_id();
                    }
                    $o_user_basket_item = $o_user_basket->get_item($s_prod_id, $o_content->get_sel_list());
                    $d_new_amount = $o_user_basket_item->oxuserbasketitems__oxamount->value - $o_content->get_amount();
                    if ($d_new_amount < 0) {
                        $d_new_amount = 0;
                    }
                    $o_user_basket->add_item_to_basket($s_prod_id, $d_new_amount, $o_content->get_sel_list(), true);
                }
            }
        }
    }
    /**
     * After order is finished this method cleans up users notice list, by
     * removing bought items from users notice list
     *
     * @param array                                    $aArticleList array of basket products
     * @param \OxidEsales\Eshop\Application\Model\User $oUser        basket user object
     */
    protected function update_notice_list($a_article_list, $o_user)
    {
        /*
         * #6141
         * If there is no noticelist, don't create an empty one.
         * Because loading the list via $user->getBasket('noticelist') will create it if there isn't one, but it will
         * only exists in the session for now. So it is possible to check if it has an oxid. If yes then we had a list.
         * If not, it's newly created and adds a row in oxuserbaskets without content in oxuserbasketitems.
         * Also it will prevent creating a row for guests.
         */
        if (!isset($o_user->get_basket('noticelist')->oxuserbaskets__oxid->value)) {
            return;
        }
        // loading users notice list ..
        if ($o_user_basket = $o_user->get_basket('noticelist')) {
            // only if wishlist is enabled
            foreach ($a_article_list as $o_content) {
                $s_prod_id = $o_content->get_product_id();
                // updating users notice list
                /** @var \OxidEsales\EshopCommunity\Application\Model\BasketItem $oUserBasketItem */
                $o_user_basket_item = $o_user_basket->get_item($s_prod_id, $o_content->get_sel_list(), $o_content->get_pers_params());
                if (is_object($o_user_basket_item->oxuserbasketitems__oxamount) && $o_user_basket_item->oxuserbasketitems__oxamount->value) {
                    $d_new_amount = $o_user_basket_item->oxuserbasketitems__oxamount->value - $o_content->get_amount();
                } else {
                    $d_new_amount = -1 * $o_content->get_amount();
                }
                if ($d_new_amount < 0) {
                    $d_new_amount = 0;
                }
                $o_user_basket->add_item_to_basket($s_prod_id, $d_new_amount, $o_content->get_sel_list(), true, $o_content->get_pers_params());
            }
        }
    }
    /**
     * Updates order date to current date
     */
    protected function update_order_date()
    {
        $o_db = Database_Provider::get_db();
        $s_date = date('Y-m-d H:i:s', Registry::get_utils_date()->get_time());
        $s_q = 'update oxorder set oxorderdate = :oxorderdate where oxid = :oxid';
        $this->oxorder__oxorderdate = new Field($s_date, Field::T_RAW);
        $o_db->execute($s_q, ['oxorderdate' => $s_date, 'oxid' => $this->get_id()]);
    }
    /**
     * Marks voucher as used (oxvoucher::markAsUsed())
     * and sets them to $this->_aVoucherList.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser   user object
     */
    protected function mark_vouchers($o_basket, $o_user)
    {
        $this->_a_voucher_list = $o_basket->get_vouchers();
        if (is_array($this->_a_voucher_list)) {
            foreach ($this->_a_voucher_list as $s_voucher_id => $o_simple_voucher) {
                $o_voucher = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher::class);
                $o_voucher->load($s_voucher_id);
                $o_voucher->mark_as_used($this->oxorder__oxid->value, $o_user->oxuser__oxid->value, $o_simple_voucher->d_voucherdiscount);
                $this->_a_voucher_list[$s_voucher_id] = $o_voucher;
            }
        }
    }
    /**
     * Updates/inserts order object and related info to DB
     */
    public function save()
    {
        if ($bl_save = parent::save()) {
            // saving order articles
            $o_order_articles = $this->get_order_articles();
            if ($o_order_articles && count($o_order_articles) > 0) {
                foreach ($o_order_articles as $o_order_article) {
                    $o_order_article->save();
                }
            }
        }
        return $bl_save;
    }
    /**
     * Loads and returns delivery address object or null
     * if deladrid is not configured, or object was not loaded
     *
     * @return \OxidEsales\Eshop\Application\Model\Address|null
     */
    public function get_del_address_info()
    {
        $o_del_adress = null;
        if (!$sox_address_id = Registry::get_request()->get_request_escaped_parameter('deladrid')) {
            $sox_address_id = Registry::get_session()->get_variable('deladrid');
        }
        if ($sox_address_id) {
            $o_del_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
            $o_del_adress->load($sox_address_id);
            //get delivery country name from delivery country id
            if ($o_del_adress->oxaddress__oxcountryid->value && $o_del_adress->oxaddress__oxcountryid->value != -1) {
                $o_country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
                $o_country->load($o_del_adress->oxaddress__oxcountryid->value);
                $o_del_adress->oxaddress__oxcountry = clone $o_country->oxcountry__oxtitle;
            }
        }
        return $o_del_adress;
    }
    /**
     * Function which checks if article stock is valid.
     * If not displays error and returns false.
     *
     * @param object $oBasket basket object
     *
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     */
    public function validate_stock($o_basket): void
    {
        foreach ($o_basket->get_contents() as $key => $o_content) {
            try {
                $o_prod = $o_content->get_article(true, null, true);
            } catch (\Oxid_Esales\Eshop\Core\Exception\No_Article_Exception|\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception $o_ex) {
                $o_basket->remove_item($key);
                throw $o_ex;
            }
            // check if its still available
            $d_art_stock_amount = $o_basket->get_art_stock_in_basket($o_prod->get_id(), $key);
            $i_on_stock = $o_prod->check_for_stock($o_content->get_amount(), $d_art_stock_amount);
            if ($i_on_stock !== true) {
                /** @var \OxidEsales\Eshop\Core\Exception\OutOfStockException $oEx */
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Out_Of_Stock_Exception::class);
                $o_ex->set_message('ERROR_MESSAGE_OUTOFSTOCK_OUTOFSTOCK');
                $o_ex->set_article_nr($o_prod->oxarticles__oxartnum->value);
                $o_ex->set_product_id($o_prod->get_id());
                $o_ex->set_basket_index($key);
                if (!is_numeric($i_on_stock)) {
                    $i_on_stock = 0;
                }
                $o_ex->set_remaining_amount($i_on_stock);
                throw $o_ex;
            }
        }
    }
    /**
     * Inserts order object information in DB. Returns true on success.
     *
     * @return bool
     */
    protected function insert()
    {
        $my_config = Registry::get_config();
        $o_utils_date = Registry::get_utils_date();
        //V #M525 orderdate must be the same as it was
        if (!$this->oxorder__oxorderdate || !$this->oxorder__oxorderdate->value) {
            $this->oxorder__oxorderdate = new Field(date('Y-m-d H:i:s', $o_utils_date->get_time()), Field::T_RAW);
        } else {
            $this->oxorder__oxorderdate = new Field($o_utils_date->format_db_date($this->oxorder__oxorderdate ? $this->oxorder__oxorderdate->value : null, true));
        }
        $this->oxorder__oxshopid = new Field($my_config->get_shop_id(), Field::T_RAW);
        $this->oxorder__oxsenddate = new Field($o_utils_date->format_db_date($this->oxorder__oxsenddate ? $this->oxorder__oxsenddate->value : null, true));
        return parent::insert();
    }
    /**
     * creates counter ident
     *
     * @return String
     */
    protected function get_counter_ident()
    {
        return $this->_bl_separate_numbering ? 'oxOrder_' . Registry::get_config()->get_shop_id() : 'oxOrder';
    }
    /**
     * Tries to fetch and set next record number in DB. Returns true on success
     *
     * @return bool
     */
    protected function set_number()
    {
        $o_db = Database_Provider::get_db();
        $i_cnt = ox_new(Counter::class)->get_next($this->get_counter_ident());
        $s_q = 'update oxorder set oxordernr = :oxordernr where oxid = :oxid';
        $bl_update = (bool) $o_db->execute($s_q, ['oxordernr' => $i_cnt, 'oxid' => $this->get_id()]);
        if ($bl_update) {
            $this->oxorder__oxordernr = new Field($i_cnt);
        }
        return $bl_update;
    }
    /**
     * Updates object parameters to DB.
     */
    protected function update()
    {
        $this->_a_skip_save_fields = ['oxtimestamp', 'oxorderdate'];
        $this->oxorder__oxsenddate = new Field(Registry::get_utils_date()->format_db_date($this->oxorder__oxsenddate->value, true));
        return parent::update();
    }
    /**
     * Updates stock information, deletes current ordering details from DB,
     * returns true on success.
     *
     * @param string $sOxId Ordering ID (default null)
     *
     * @return bool
     */
    public function delete($s_ox_id = null)
    {
        if ($s_ox_id) {
            if (!$this->load($s_ox_id)) {
                // such order does not exist
                return false;
            }
        } elseif (!$s_ox_id) {
            $s_ox_id = $this->get_id();
        }
        // no order id is passed
        if (!$s_ox_id) {
            return false;
        }
        // delete order articles
        $o_order_articles = $this->get_order_articles(false);
        foreach ($o_order_articles as $o_order_article) {
            $o_order_article->delete();
        }
        // #440 - deleting user payment info
        if ($o_payment_type = $this->get_payment_type()) {
            $o_payment_type->delete();
        }
        return parent::delete($s_ox_id);
    }
    /**
     * Recalculates order. Starts transactions, deletes current order and order articles from DB,
     * adds current order articles to virtual basket and finally recalculates order by calling Order::finalizeOrder()
     * If no errors, finishing transaction.
     *
     * @param array $aNewArticles article list of new order
     *
     * @throws Exception
     */
    public function recalculate_order($a_new_articles = []): void
    {
        Database_Provider::get_db()->start_transaction();
        try {
            $o_basket = $this->get_order_basket();
            // add this order articles to virtual basket and recalculates basket
            $this->add_order_articles_to_basket($o_basket, $this->get_order_articles(true));
            // adding new articles to existing order
            $this->add_articles_to_basket($o_basket, $a_new_articles);
            // recalculating basket
            $o_basket->calculate_basket(true);
            //finalizing order (skipping payment execution, vouchers marking and mail sending)
            $i_ret = $this->finalize_order($o_basket, $this->get_order_user(), true);
            //if finalizing order failed, rollback transaction
            if ($i_ret !== 1) {
                Database_Provider::get_db()->rollback_transaction();
            } else {
                Database_Provider::get_db()->commit_transaction();
            }
        } catch (Exception $exception) {
            Database_Provider::get_db()->rollback_transaction();
            throw $exception;
        }
    }
    /**
     * Returns basket object filled up with discount, delivery, wrapping and all other info
     *
     * @param bool $blStockCheck perform stock check or not (default true)
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    protected function get_order_basket($bl_stock_check = true)
    {
        $this->_o_order_basket = ox_new(\Oxid_Esales\Eshop\Application\Model\Basket::class);
        $this->_o_order_basket->enable_save_to_data_base(false);
        //setting recalculation mode
        $this->_o_order_basket->set_calculation_mode_netto($this->is_netto_mode());
        // setting stock check mode
        $this->_o_order_basket->set_stock_check_mode($bl_stock_check);
        // setting virtual basket user
        $this->_o_order_basket->set_basket_user($this->get_order_user());
        // transferring order id
        $this->_o_order_basket->set_order_id($this->get_id());
        // setting basket currency order uses
        $a_currencies = Registry::get_config()->get_currency_array();
        foreach ($a_currencies as $o_cur) {
            if ($o_cur->name == $this->oxorder__oxcurrency->value) {
                $o_basket_cur = $o_cur;
                break;
            }
        }
        // setting currency
        $this->_o_order_basket->set_basket_currency($o_basket_cur);
        // set basket card id and message
        $this->_o_order_basket->set_card_id($this->oxorder__oxcardid->value);
        $this->_o_order_basket->set_card_message($this->oxorder__oxcardtext->value);
        if ($this->_bl_reload_discount) {
            $o_db = Database_Provider::get_db();
            // disabling availability check
            $this->_o_order_basket->set_skip_vouchers_checking(true);
            // add previously used vouchers
            $s_q = 'select oxid from oxvouchers where oxorderid = :oxorderid';
            $a_vouchers = $o_db->get_all($s_q, ['oxorderid' => $this->get_id()]);
            foreach ($a_vouchers as $a_voucher) {
                $this->_o_order_basket->add_voucher($a_voucher['oxid']);
            }
        } else {
            $this->_o_order_basket->set_discount_calc_mode(false);
            $this->_o_order_basket->set_voucher_discount($this->oxorder__oxvoucherdiscount->value);
            $this->_o_order_basket->set_total_discount($this->oxorder__oxdiscount->value);
        }
        // must be kept old delivery?
        if (!$this->_bl_reload_delivery) {
            $this->_o_order_basket->set_delivery_price($this->get_order_delivery_price());
        } else {
            //  set shipping
            $this->_o_order_basket->set_shipping($this->oxorder__oxdeltype->value);
            $this->_o_order_basket->set_delivery_price(null);
        }
        //set basket payment
        $this->_o_order_basket->set_payment($this->oxorder__oxpaymenttype->value);
        return $this->_o_order_basket;
    }
    /**
     * Sets new delivery id for order and forces order to recalculate using new delivery type.
     * Order is not recalculated automatically, to do this Order::recalculateOrder() must be called ;
     *
     * @param string $sDeliveryId new delivery id
     */
    public function set_delivery($s_delivery_id): void
    {
        $this->reload_delivery(true);
        $this->oxorder__oxdeltype = new Field($s_delivery_id);
    }
    /**
     * Returns current order user object
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    public function get_order_user()
    {
        if ($this->_o_user === null) {
            $this->_o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $this->_o_user->load($this->oxorder__oxuserid->value);
            // if object is loaded then reusing its order info
            if ($this->_is_loaded) {
                // bill address
                $this->_o_user->oxuser__oxcompany = clone $this->oxorder__oxbillcompany;
                $this->_o_user->oxuser__oxusername = clone $this->oxorder__oxbillemail;
                $this->_o_user->oxuser__oxfname = clone $this->oxorder__oxbillfname;
                $this->_o_user->oxuser__oxlname = clone $this->oxorder__oxbilllname;
                $this->_o_user->oxuser__oxstreet = clone $this->oxorder__oxbillstreet;
                $this->_o_user->oxuser__oxstreetnr = clone $this->oxorder__oxbillstreetnr;
                $this->_o_user->oxuser__oxaddinfo = clone $this->oxorder__oxbilladdinfo;
                $this->_o_user->oxuser__oxustid = clone $this->oxorder__oxbillustid;
                $this->_o_user->oxuser__oxcity = clone $this->oxorder__oxbillcity;
                $this->_o_user->oxuser__oxcountryid = clone $this->oxorder__oxbillcountryid;
                $this->_o_user->oxuser__oxstateid = clone $this->oxorder__oxbillstateid;
                $this->_o_user->oxuser__oxzip = clone $this->oxorder__oxbillzip;
                $this->_o_user->oxuser__oxfon = clone $this->oxorder__oxbillfon;
                $this->_o_user->oxuser__oxfax = clone $this->oxorder__oxbillfax;
                $this->_o_user->oxuser__oxsal = clone $this->oxorder__oxbillsal;
            }
        }
        return $this->_o_user;
    }
    /**
     * Fake entries, pdf is generated in modules.. myorder.
     *
     * @param mixed $oPdf pdf object
     */
    public function pdf_footer($o_pdf)
    {
    }
    /**
     * Fake entries, pdf is generated in modules.. myorder.
     *
     * @param mixed $oPdf pdf object
     */
    public function pdf_headerplus($o_pdf)
    {
    }
    /**
     * Fake entries, pdf is generated in modules.. myorder.
     *
     * @param mixed $oPdf pdf object
     */
    public function pdf_header($o_pdf)
    {
    }
    /**
     * Fake entries, pdf is generated in modules.. myorder.
     *
     * @param string $sFilename file name
     * @param int    $iSelLang  selected language
     */
    public function gen_pdf($s_filename, $i_sel_lang = 0)
    {
    }
    /**
     * Returns order invoice number.
     *
     * @return integer
     */
    public function get_invoice_num()
    {
        $s_q = 'select max(oxorder.oxinvoicenr) from oxorder 
            where oxorder.oxshopid = :oxshopid ';
        $params = ['oxshopid' => Registry::get_config()->get_shop_id()];
        return (int) Database_Provider::get_db()->get_one($s_q, $params) + 1;
    }
    /**
     * Returns next possible (free) order bill number.
     *
     * @return integer
     */
    public function get_next_bill_num()
    {
        $s_q = 'select max(cast(oxorder.oxbillnr as unsigned)) from oxorder 
            where oxorder.oxshopid = :oxshopid ';
        $params = ['oxshopid' => Registry::get_config()->get_shop_id()];
        return (int) Database_Provider::get_db()->get_one($s_q, $params) + 1;
    }
    /**
     * Loads possible shipping sets for this order
     *
     * @return \OxidEsales\Eshop\Application\Model\DeliveryList
     */
    public function get_shipping_set_list()
    {
        // in which country we deliver
        if (!$s_ship_id = $this->oxorder__oxdelcountryid->value) {
            $s_ship_id = $this->oxorder__oxbillcountryid->value;
        }
        $o_basket = $this->get_order_basket(false);
        // unsetting bundles
        $o_order_articles = $this->get_order_articles();
        foreach ($o_order_articles as $s_item_id => $o_item) {
            if ($o_item->is_bundle()) {
                $o_order_articles->offsetUnset($s_item_id);
            }
        }
        // add this order articles to basket and recalculate basket
        $this->add_order_articles_to_basket($o_basket, $o_order_articles);
        // recalculating basket
        $o_basket->calculate_basket(true);
        // load fitting deliveries list
        $o_delivery_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_List::class, 'core');
        $o_delivery_list->set_collect_fitting_deliveries_sets(true);
        return $o_delivery_list->get_delivery_list($o_basket, $this->get_order_user(), $s_ship_id);
    }
    /**
     * Get vouchers numbers list which were used with this order
     *
     * @return array
     */
    public function get_voucher_nr_list()
    {
        return Database_Provider::get_db()->get_col('select oxvouchernr from oxvouchers where oxorderid = :oxorderid', ['oxorderid' => $this->oxorder__oxid->value]);
    }
    /**
     * Returns orders total price
     *
     * @param bool $blToday if true calculates only current day orders
     *
     * @return double
     */
    public function get_order_sum($bl_today = false)
    {
        $s_select = 'select sum(oxtotalordersum / oxcurrate) from oxorder where ';
        $s_select .= 'oxshopid = :oxshopid and oxorder.oxstorno != "1" ';
        if ($bl_today) {
            $s_select .= 'and oxorderdate like "' . date('Y-m-d') . '%" ';
        }
        $params = ['oxshopid' => Registry::get_config()->get_shop_id()];
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (float) Database_Provider::get_master()->get_one($s_select, $params);
    }
    /**
     * Returns orders count
     *
     * @param bool $blToday if true calculates only current day orders
     *
     * @return int
     */
    public function get_order_cnt($bl_today = false)
    {
        $s_select = 'select count(*) from oxorder where ';
        $s_select .= 'oxshopid = :oxshopid  and oxorder.oxstorno != "1" ';
        if ($bl_today) {
            $s_select .= 'and oxorderdate like "' . date('Y-m-d') . '%" ';
        }
        $params = ['oxshopid' => Registry::get_config()->get_shop_id()];
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (int) Database_Provider::get_master()->get_one($s_select, $params);
    }
    /**
     * Checking if this order is already stored.
     *
     * @param string $sOxId order ID
     *
     * @return bool
     */
    protected function check_order_exist($s_ox_id = null)
    {
        if (!$s_ox_id) {
            return false;
        }
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = Database_Provider::get_master();
        $params = ['oxid' => $s_ox_id];
        if ($master_db->get_one('select oxid from oxorder where oxid = :oxid', $params)) {
            return true;
        }
        return false;
    }
    /**
     * Send order to shop owner and user
     *
     * @param \OxidEsales\Eshop\Application\Model\User        $oUser    order user
     * @param \OxidEsales\Eshop\Application\Model\Basket      $oBasket  current order basket
     * @param \OxidEsales\Eshop\Application\Model\UserPayment $oPayment order payment
     *
     * @return bool
     */
    protected function send_order_by_email($o_user = null, $o_basket = null, $o_payment = null)
    {
        $i_ret = self::ORDER_STATE_MAILINGERROR;
        // add user, basket and payment to order
        $this->_o_user = $o_user;
        $this->_o_basket = $o_basket;
        $this->_o_payment = $o_payment;
        $ox_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
        // send order email to user
        if ($ox_email->send_order_e_mail_to_user($this)) {
            // mail to user was successfully sent
            $i_ret = self::ORDER_STATE_OK;
        }
        // send order email to shop owner
        $ox_email->send_order_e_mail_to_owner($this);
        return $i_ret;
    }
    /**
     * Returns order basket
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    public function get_basket()
    {
        return $this->_o_basket;
    }
    /**
     * Returns order payment
     *
     * @return \OxidEsales\Eshop\Application\Model\UserPayment
     */
    public function get_payment()
    {
        return $this->_o_payment;
    }
    /**
     * Returns order vouchers marked as used
     *
     * @return array
     */
    public function get_voucher_list()
    {
        return $this->_a_voucher_list;
    }
    /**
     * Returns order deliveryset object
     *
     * @return \OxidEsales\Eshop\Application\Model\DeliverySet
     */
    public function get_del_set()
    {
        if ($this->_o_del_set == null) {
            // load deliveryset info
            $this->_o_del_set = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
            $this->_o_del_set->load($this->oxorder__oxdeltype->value);
        }
        return $this->_o_del_set;
    }
    /**
     * Get payment type
     *
     * @return \OxidEsales\Eshop\Application\Model\UserPayment|false
     */
    public function get_payment_type()
    {
        if ($this->get_field_data('oxpaymentid') && $this->_o_payment_type === null) {
            $this->_o_payment_type = false;
            $o_payment_type = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
            if ($o_payment_type->load($this->get_field_data('oxpaymentid'))) {
                $this->_o_payment_type = $o_payment_type;
            }
        }
        return $this->_o_payment_type;
    }
    /**
     * Get gift card
     *
     * @return \OxidEsales\Eshop\Application\Model\Wrapping|null
     */
    public function get_gift_card()
    {
        if ($this->oxorder__oxcardid->value && $this->_o_gift_card == null) {
            $this->_o_gift_card = ox_new(\Oxid_Esales\Eshop\Application\Model\Wrapping::class);
            $this->_o_gift_card->load($this->oxorder__oxcardid->value);
        }
        return $this->_o_gift_card;
    }
    /**
     * Set usage of separate orders numbering for different shops
     *
     * @param bool $blSeparateNumbering use or not separate orders numbering
     */
    public function set_separate_numbering($bl_separate_numbering = null): void
    {
        $this->_bl_separate_numbering = $bl_separate_numbering;
    }
    /**
     * Get users payment type from last order
     *
     * @param string $sUserId order user id
     *
     * @return string $sLastPaymentId payment id
     */
    public function get_last_user_payment_type($s_user_id)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = Database_Provider::get_master();
        $s_q = 'select oxorder.oxpaymenttype from oxorder 
            where oxorder.oxshopid = :oxshopid 
                and oxorder.oxuserid = :oxuserid 
            order by oxorder.oxorderdate desc ';
        return $master_db->get_one($s_q, ['oxshopid' => Registry::get_config()->get_shop_id(), 'oxuserid' => $s_user_id]);
    }
    /**
     * Adds order articles back to virtual basket. Needed for recalculating order.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket        basket object
     * @param array                                      $aOrderArticles order articles
     */
    protected function add_order_articles_to_basket($o_basket, $a_order_articles)
    {
        // if no order articles, return empty basket
        if (count($a_order_articles) > 0) {
            //adding order articles to basket
            foreach ($a_order_articles as $o_order_article) {
                $o_basket->add_order_article_to_basket($o_order_article);
            }
        }
    }
    /**
     * Adds new products to basket/order
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket   basket to add articles
     * @param array                                      $aArticles article array
     */
    protected function add_articles_to_basket($o_basket, $a_articles)
    {
        // if no order articles
        if (count($a_articles) > 0) {
            //adding order articles to basket
            foreach ($a_articles as $o_article) {
                $a_sel = isset($o_article->oxorderarticles__oxselvariant) ? $o_article->oxorderarticles__oxselvariant->value : null;
                $a_pers_param = isset($o_article->oxorderarticles__oxpersparam) ? $o_article->get_pers_params() : null;
                $o_basket->add_to_basket($o_article->oxorderarticles__oxartid->value, $o_article->oxorderarticles__oxamount->value, $a_sel, $a_pers_param);
            }
        }
    }
    /**
     * Get total sum from last order
     *
     * @return string
     */
    public function get_total_order_sum()
    {
        $o_cur = Registry::get_config()->get_act_shop_currency_object();
        return number_format((float) $this->oxorder__oxtotalordersum->value, $o_cur->decimal, '.', '');
    }
    /**
     * Returns array of plain formatted VATs stored in order
     *
     * @param bool $blFormatCurrency enables currency formatting
     *
     * @return array
     */
    public function get_product_vats($bl_format_currency = true)
    {
        $a_vats = [];
        if ($this->oxorder__oxartvat1->value) {
            $a_vats[(int) $this->oxorder__oxartvat1->value] = $this->oxorder__oxartvatprice1->value;
        }
        if ($this->oxorder__oxartvat2->value) {
            $a_vats[(int) $this->oxorder__oxartvat2->value] = $this->oxorder__oxartvatprice2->value;
        }
        if ($bl_format_currency) {
            $o_lang = Registry::get_lang();
            $o_cur = Registry::get_config()->get_act_shop_currency_object();
            foreach ($a_vats as $s_key => $d_vat) {
                $a_vats[$s_key] = $o_lang->format_currency($d_vat, $o_cur);
            }
        }
        return $a_vats;
    }
    /**
     * Get billing country name from billing country id
     *
     * @return \OxidEsales\Eshop\Core\Field
     */
    public function get_bill_country()
    {
        if (!property_exists($this, 'oxorder__oxbillcountry')) {
            $this->oxorder__oxbillcountry = new Field($this->get_country_title($this->oxorder__oxbillcountryid->value));
        }
        return $this->oxorder__oxbillcountry;
    }
    /**
     * Get delivery country name from delivery country id
     *
     * @return \OxidEsales\Eshop\Core\Field
     */
    public function get_del_country()
    {
        if (!property_exists($this, 'oxorder__oxdelcountry')) {
            $this->oxorder__oxdelcountry = new Field($this->get_country_title($this->oxorder__oxdelcountryid->value));
        }
        return $this->oxorder__oxdelcountry;
    }
    /**
     * Tells to keep old or reload delivery costs while recalculating order
     *
     * @param bool $blReload reload state marker
     */
    public function reload_delivery($bl_reload): void
    {
        $this->_bl_reload_delivery = $bl_reload;
    }
    /**
     * Tells to keep old or reload discount while recalculating order
     *
     * @param bool $blReload reload state marker
     */
    public function reload_discount($bl_reload): void
    {
        $this->_bl_reload_discount = $bl_reload;
    }
    /**
     * Performs order cancel process
     */
    public function cancel_order(): void
    {
        $this->oxorder__oxstorno = new Field(1);
        if ($this->save()) {
            // canceling ordered products
            foreach ($this->get_order_articles() as $o_order_article) {
                $o_order_article->cancel_order_article();
            }
        }
    }
    /**
     * Returns actual order currency object. In case currency was not recognized
     * due to changed name returns first shop currency object
     *
     * @return \stdClass
     */
    public function get_order_currency()
    {
        if ($this->_o_order_currency === null) {
            // setting default in case unrecognized currency was set during order
            $a_currencies = Registry::get_config()->get_currency_array();
            $this->_o_order_currency = current($a_currencies);
            foreach ($a_currencies as $o_curr) {
                if ($o_curr->name == $this->oxorder__oxcurrency->value) {
                    $this->_o_order_currency = $o_curr;
                    break;
                }
            }
        }
        return $this->_o_order_currency;
    }
    /**
     * Validates order parameters like stock, delivery and payment
     * parameters
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser   order user
     */
    public function validate_order($o_basket, $o_user)
    {
        // validating stock
        $i_valid_state = $this->validate_stock($o_basket);
        if (!$i_valid_state) {
            // validating delivery
            $i_valid_state = $this->validate_delivery($o_basket);
        }
        if (!$i_valid_state) {
            // validating payment
            $i_valid_state = $this->validate_payment($o_basket, $o_user);
        }
        if (!$i_valid_state) {
            //0003110 validating delivery address, it is not be changed during checkout process
            $i_valid_state = $this->validate_delivery_address($o_user);
        }
        if (!$i_valid_state) {
            // validating minimum price
            $i_valid_state = $this->validate_basket($o_basket);
        }
        if (!$i_valid_state) {
            // validating vouchers
            return $this->validate_vouchers($o_basket);
        }
        return $i_valid_state;
    }
    public function validate_vouchers($basket)
    {
        $voucher_ids = array_keys($basket->get_vouchers());
        foreach ($voucher_ids as $voucher_id) {
            $voucher = ox_new(Eshop_Voucher_Model::class);
            $voucher->load($voucher_id);
            if ($voucher->get_field_data('oxorderid')) {
                return self::ORDER_STATE_VOUCHERERROR;
            }
        }
    }
    /**
     * Validates basket. Currently checks if minimum order price > basket price
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     *
     * @return bool
     */
    public function validate_basket($o_basket)
    {
        return $o_basket->is_below_min_order_price() ? self::ORDER_STATE_BELOWMINPRICE : null;
    }
    /**
     * Checks if delivery address (billing or shipping) was not changed during checkout
     * Throws exception if not available
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser user object
     *
     * @return int
     */
    public function validate_delivery_address($o_user)
    {
        $s_del_address_md5 = Registry::get_request()->get_request_escaped_parameter('sDeliveryAddressMD5');
        $s_delivery_address = $o_user->get_encoded_delivery_address();
        /** @var \OxidEsales\Eshop\Application\Model\RequiredAddressFields $oRequiredAddressFields */
        $o_required_address_fields = ox_new(\Oxid_Esales\Eshop\Application\Model\Required_Address_Fields::class);
        /** @var \OxidEsales\Eshop\Application\Model\RequiredFieldsValidator $oFieldsValidator */
        $o_fields_validator = ox_new(\Oxid_Esales\Eshop\Application\Model\Required_Fields_Validator::class);
        $o_fields_validator->set_required_fields($o_required_address_fields->get_billing_fields());
        $bl_fields_valid = $o_fields_validator->validate_fields($o_user);
        /** @var \OxidEsales\Eshop\Application\Model\Address $oDeliveryAddress */
        $o_delivery_address = $this->get_del_address_info();
        if ($bl_fields_valid && $o_delivery_address) {
            $s_delivery_address .= $o_delivery_address->get_encoded_delivery_address();
            $o_fields_validator->set_required_fields($o_required_address_fields->get_delivery_fields());
            $bl_fields_valid = $o_fields_validator->validate_fields($o_delivery_address);
        }
        if ($s_del_address_md5 != $s_delivery_address || !$bl_fields_valid) {
            return self::ORDER_STATE_INVALIDDELADDRESSCHANGED;
        }
        return 0;
    }
    /**
     * Checks if delivery set used for current order is available and active.
     * Throws exception if not available
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     */
    public function validate_delivery($o_basket)
    {
        // proceed with no delivery
        // used for other countries
        if ($o_basket->get_payment_id() == 'oxempty') {
            return;
        }
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = Database_Provider::get_master();
        $o_del_set = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery_Set::class);
        $s_table = $o_del_set->get_view_name();
        $s_q = "select 1 from {$s_table} where {$s_table}.oxid = :oxid and " . $o_del_set->get_sql_active_snippet();
        $params = ['oxid' => $o_basket->get_shipping_id()];
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        if (!$master_db->get_one($s_q, $params)) {
            // throwing exception
            return self::ORDER_STATE_INVALIDDELIVERY;
        }
    }
    /**
     * Checks if payment used for current order is available and active.
     * Throws exception if not available
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket    $oBasket basket object
     * @param \OxidEsales\Eshop\Application\Model\User|null $oUser   user object
     */
    public function validate_payment($o_basket, $o_user = null)
    {
        $payment_id = $o_basket->get_payment_id();
        if (!$this->is_valid_payment_id($payment_id) || !$this->is_valid_payment($o_basket, $o_user)) {
            return self::ORDER_STATE_INVALIDPAYMENT;
        }
    }
    /**
     * Get total net sum formatted
     *
     * @return string
     */
    public function get_formatted_total_net_sum()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxtotalnetsum->value, $this->get_order_currency());
    }
    /**
     * Get total brut sum formatted
     *
     * @return string
     */
    public function get_formatted_total_brut_sum()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxtotalbrutsum->value, $this->get_order_currency());
    }
    /**
     * Get Delivery cost sum formatted
     *
     * @return string
     */
    public function get_formatted_delivery_cost()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxdelcost->value, $this->get_order_currency());
    }
    /**
     * Get pay cost sum formatted
     *
     * @return string
     */
    public function get_formatted_pay_cost()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxpaycost->value, $this->get_order_currency());
    }
    /**
     * Get wrap cost sum formatted
     *
     * @return string
     */
    public function get_formatted_wrap_cost()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxwrapcost->value, $this->get_order_currency());
    }
    /**
     * Get wrap cost sum formatted
     *
     * @return string
     */
    public function get_formatted_gift_card_cost()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxgiftcardcost->value, $this->get_order_currency());
    }
    /**
     * Get total vouchers formatted
     *
     * @return string
     */
    public function get_formatted_total_vouchers()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxvoucherdiscount->value, $this->get_order_currency());
    }
    /**
     * Get Discount formatted
     *
     * @return string
     */
    public function get_formatted_discount()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxdiscount->value, $this->get_order_currency());
    }
    /**
     * Get formatted total sum from last order
     *
     * @return string
     */
    public function get_formatted_total_order_sum()
    {
        return Registry::get_lang()->format_currency($this->oxorder__oxtotalordersum->value, $this->get_order_currency());
    }
    /**
     * Returns shipment tracking code
     *
     * @return string
     */
    public function get_track_code()
    {
        return $this->oxorder__oxtrackcode->value;
    }
    /**
     * Returns shipment tracking url if oxtrackcode and shipment tracking url are supplied
     *
     * @return string
     */
    public function get_shipment_tracking_url()
    {
        if ($this->_s_ship_track_url === null) {
            $tracking_url = $this->get_tracking_url();
            $tracking_code = $this->get_track_code();
            if ($tracking_url && $tracking_code) {
                $this->_s_ship_track_url = str_replace('##ID##', $tracking_code, $tracking_url);
            }
        }
        return $this->_s_ship_track_url;
    }
    private function get_tracking_url(): string
    {
        $delivery_set_tracking_url = $this->get_del_set()->get_field_data('oxtrackingurl');
        return (string) ($delivery_set_tracking_url ?: Registry::get_config()->get_config_param('sParcelService'));
    }
    /**
     * Returns true if paymentId is valid.
     *
     * @param int $paymentId
     */
    private function is_valid_payment_id($payment_id): bool
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = Database_Provider::get_master();
        $payment_model = ox_new(Eshop_Payment::class);
        $table_name = $payment_model->get_view_name();
        $sql = "\n            select\n                1 \n            from \n                {$table_name}\n            where \n                {$table_name}.oxid = :oxid\n                and {$payment_model->get_sql_active_snippet()}\n        ";
        return (bool) $master_db->get_one($sql, ['oxid' => $payment_id]);
    }
    /**
     * Returns true if payment is valid.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket    $basket
     * @param \OxidEsales\Eshop\Application\Model\User|null $oUser  user object
     *
     * @return bool
     */
    private function is_valid_payment($basket, $o_user = null)
    {
        $payment_id = $basket->get_payment_id();
        $payment_model = ox_new(Eshop_Payment::class);
        $payment_model->load($payment_id);
        $dynamic_values = $this->get_dynamic_values();
        $shop_id = Registry::get_config()->get_shop_id();
        if (!$o_user) {
            $o_user = $this->get_user();
        }
        return $payment_model->is_valid_payment($dynamic_values, $shop_id, $o_user, $basket->get_price_for_payment(), $basket->get_shipping_id());
    }
    /**
     * @return mixed
     */
    private function get_dynamic_values()
    {
        $session = Registry::get_session();
        $dynamic_values = $session->get_variable('dynvalue');
        if (!$dynamic_values) {
            $dynamic_values = Registry::get_request()->get_request_parameter('dynvalue');
        }
        if (!$dynamic_values && $this->get_payment_type()) {
            return $this->get_dynamic_values_from_payment_type();
        }
        return $dynamic_values;
    }
    /**
     * @return mixed
     */
    private function get_dynamic_values_from_payment_type()
    {
        $dynamic_values = null;
        $dynamic_values_list = $this->get_payment_type()->get_dyn_values();
        if (is_array($dynamic_values_list)) {
            foreach ($dynamic_values_list as $value) {
                $dynamic_values[$value->name] = $value->value;
            }
        }
        return $dynamic_values;
    }
}