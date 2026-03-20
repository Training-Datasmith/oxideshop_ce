<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Bridge_Interface;
/**
 * Newsletter opt-in/out.
 * Arranges newsletter opt-in form, have some methods to confirm
 * user opt-in or remove user from newsletter list. OXID eShop ->
 * (Newsletter).
 */
class Newsletter_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Home country id
     *
     * @var string
     */
    protected $_s_home_country_id;
    /**
     * Newletter status.
     *
     * @var integer
     */
    protected $_i_newsletter_status;
    /**
     * User newsletter registration data.
     *
     * @var object
     */
    protected $_a_reg_params;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/info/newsletter';
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Only loads newsletter subscriber data.
     *
     * Template variables:
     * <b>aRegParams</b>
     */
    public function fill(): void
    {
        // loads submited values
        $this->_a_reg_params = Registry::get_request()->get_request_escaped_parameter('editval');
    }
    /**
     * Checks for newsletter subscriber data, if OK - creates new user as
     * subscriber or assigns existing user to newsletter group and sends
     * confirmation email.
     *
     * Template variables:
     * <b>success</b>, <b>error</b>, <b>aRegParams</b>
     */
    public function send(): void
    {
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $email_validator = Container_Facade::get(Email_Validator_Service_Bridge_Interface::class);
        // loads submited values
        $this->_a_reg_params = $a_params;
        if (!$a_params['oxuser__oxusername']) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_COMPLETE_FIELDS_CORRECTLY');
            return;
        }
        if (!$email_validator->is_email_valid($a_params['oxuser__oxusername'])) {
            // #1052C - eMail validation added
            Registry::get_utils_view()->add_error_to_display('MESSAGE_INVALID_EMAIL');
            return;
        }
        $bl_subscribe = Registry::get_request()->get_request_escaped_parameter('subscribeStatus');
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $o_user->oxuser__oxusername = new Field($a_params['oxuser__oxusername'], Field::T_RAW);
        // if such user does not exist
        if (!$o_user->exists()) {
            // and subscribe is off - error, on - create
            if (!$bl_subscribe) {
                Registry::get_utils_view()->add_error_to_display('NEWSLETTER_EMAIL_NOT_EXIST');
                return;
            }
            $o_user->oxuser__oxactive = new \Oxid_Esales\Eshop\Core\Field(1, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_user->oxuser__oxrights = new \Oxid_Esales\Eshop\Core\Field('user', \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_user->oxuser__oxshopid = new \Oxid_Esales\Eshop\Core\Field(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id(), \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_user->oxuser__oxfname = new \Oxid_Esales\Eshop\Core\Field($a_params['oxuser__oxfname'], \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_user->oxuser__oxlname = new \Oxid_Esales\Eshop\Core\Field($a_params['oxuser__oxlname'], \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_user->oxuser__oxsal = new \Oxid_Esales\Eshop\Core\Field($a_params['oxuser__oxsal'], \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $o_user->oxuser__oxcountryid = new \Oxid_Esales\Eshop\Core\Field($a_params['oxuser__oxcountryid'], \Oxid_Esales\Eshop\Core\Field::T_RAW);
            $bl_user_loaded = $o_user->save();
        } else {
            $bl_user_loaded = $o_user->load($o_user->get_id());
        }
        // if user was added/loaded successfully and subscribe is on - subscribing to newsletter
        if ($bl_subscribe && $bl_user_loaded) {
            //removing user from subscribe list before adding
            $o_user->set_news_subscription(false, false);
            $bl_order_opt_in_email = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blOrderOptInEmail');
            if ($o_user->set_news_subscription(true, $bl_order_opt_in_email)) {
                // done, confirmation required?
                if ($bl_order_opt_in_email) {
                    $this->_i_newsletter_status = 1;
                } else {
                    $this->_i_newsletter_status = 2;
                }
            } else {
                Registry::get_utils_view()->add_error_to_display('MESSAGE_NOT_ABLE_TO_SEND_EMAIL');
            }
        } elseif (!$bl_subscribe && $bl_user_loaded) {
            // unsubscribing user
            $o_user->set_news_subscription(false, false);
            $this->_i_newsletter_status = 3;
        }
    }
    /**
     * Loads user and Adds him to newsletter group.
     *
     * Template variables:
     * <b>success</b>
     */
    public function addme(): void
    {
        // user exists ?
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        if ($o_user->load(Registry::get_request()->get_request_escaped_parameter('uid'))) {
            $s_confirm_code = md5($o_user->oxuser__oxusername->value . $o_user->oxuser__oxpasssalt->value);
            // is confirm code ok?
            if (Registry::get_request()->get_request_escaped_parameter('confirm') == $s_confirm_code) {
                $o_user->get_news_subscription()->set_opt_in_status(1);
                $o_user->add_to_group('oxidnewsletter');
                $this->_i_newsletter_status = 2;
            }
        }
    }
    /**
     * Loads user and removes him from newsletter group.
     */
    public function removeme(): void
    {
        // existing user ?
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        if ($o_user->load(Registry::get_request()->get_request_escaped_parameter('uid'))) {
            $o_user->get_news_subscription()->set_opt_in_status(0);
            // removing from group ..
            $o_user->remove_from_group('oxidnewsletter');
            $this->_i_newsletter_status = 3;
        }
    }
    /**
     * simlink to function removeme bug fix #0002894
     */
    public function rmvm(): void
    {
        $this->removeme();
    }
    /**
     * Template variable getter. Returns country id
     *
     * @return string
     */
    public function get_home_country_id()
    {
        if ($this->_s_home_country_id === null) {
            $this->_s_home_country_id = false;
            $a_home_country = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aHomeCountry');
            if (is_array($a_home_country)) {
                $this->_s_home_country_id = current($a_home_country);
            }
        }
        return $this->_s_home_country_id;
    }
    /**
     * Template variable getter. Returns newsletter subscription status
     *
     * @return integer
     */
    public function get_newsletter_status()
    {
        return $this->_i_newsletter_status;
    }
    /**
     * Template variable getter. Returns user newsletter registration data
     *
     * @return array
     */
    public function get_reg_params()
    {
        return $this->_a_reg_params;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $a_path = [];
        $i_base_language = Registry::get_lang()->get_base_language();
        $a_path['title'] = Registry::get_lang()->translate_string('STAY_INFORMED', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Page title
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->get_newsletter_status() == 4 || !$this->get_newsletter_status()) {
            $s_constant = 'STAY_INFORMED';
        } elseif ($this->get_newsletter_status() == 1) {
            $s_constant = 'MESSAGE_THANKYOU_FOR_SUBSCRIBING_NEWSLETTERS';
        } elseif ($this->get_newsletter_status() == 2) {
            $s_constant = 'MESSAGE_NEWSLETTER_CONGRATULATIONS';
        } elseif ($this->get_newsletter_status() == 3) {
            $s_constant = 'SUCCESS';
        }
        return Registry::get_lang()->translate_string($s_constant, Registry::get_lang()->get_base_language(), false);
    }
}