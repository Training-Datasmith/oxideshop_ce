<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Exception\Cookie_Exception;
use Oxid_Esales\Eshop\Core\Exception\User_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Shop_Version;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Administrator login form.
 * Performs administrator login form data collection.
 */
class Login_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /** Login page view id. */
    public const VIEW_ID = 'login';
    /**
     * Sets value for _sThisAction to "login".
     */
    public function __construct()
    {
        Registry::get_config()->set_config_param('blAdmin', true);
        $this->_s_this_action = 'login';
    }
    /**
     * Executes parent method parent::render(), creates shop object, sets template parameters
     * and returns name of template file "login".
     *
     * @return string
     */
    public function render()
    {
        $my_config = Registry::get_config();
        if (!$my_config->is_ssl()) {
            $admin_url = Container_Facade::get_parameter('oxid_esales.shop_admin_url');
            if ($admin_url && str_starts_with((string) $admin_url, 'https://')) {
                Registry::get_utils()->redirect($admin_url, false, 302);
            }
        }
        //resets user once on this screen.
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $o_user->logout();
        \Oxid_Esales\Eshop\Core\Controller\Base_Controller::render();
        $this->set_shop_config_parameters();
        if ($my_config->is_demo_shop()) {
            // demo
            $this->add_tpl_param('user', 'admin');
            $this->add_tpl_param('pwd', 'admin');
        }
        //#533 user profile
        $this->add_tpl_param('profiles', Registry::get_utils()->load_admin_profile($my_config->get_config_param('aInterfaceProfiles')));
        $a_languages = $this->get_available_languages();
        $this->add_tpl_param('aLanguages', $a_languages);
        // setting templates language to selected language id
        foreach ($a_languages as $i_key => $o_lang) {
            if ($a_languages[$i_key]->selected) {
                Registry::get_lang()->set_tpl_language($i_key);
                break;
            }
        }
        return 'login';
    }
    /**
     * Sets configuration parameters related to current shop.
     */
    protected function set_shop_config_parameters()
    {
        $my_config = Registry::get_config();
        $o_base_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        $o_base_shop->load($my_config->get_base_shop_id());
        $this->get_view_config()->set_view_config_param('sShopVersion', ox_new(Shop_Version::class)->get_version());
    }
    /**
     * Checks user login data, on success returns "admin_start".
     *
     * @return mixed
     */
    public function checklogin()
    {
        $my_utils_server = Registry::get_utils_server();
        $my_utils_view = Registry::get_utils_view();
        $s_user = Registry::get_request()->get_request_parameter('user');
        $s_pass = Registry::get_request()->get_request_parameter('pwd');
        $s_profile = Registry::get_request()->get_request_escaped_parameter('profile');
        try {
            // trying to login
            $session = Registry::get_session();
            $admin_profiles = $session->get_variable('aAdminProfiles');
            $session->init_new_session();
            $session->set_variable('aAdminProfiles', $admin_profiles);
            /** @var \OxidEsales\Eshop\Application\Model\User $oUser */
            $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $o_user->login($s_user, $s_pass);
            if ($o_user->oxuser__oxrights->value === 'user') {
                throw ox_new(User_Exception::class, 'ERROR_MESSAGE_USER_NOVALIDLOGIN');
            }
            $i_subshop = (int) $o_user->oxuser__oxrights->value;
            if ($i_subshop) {
                Registry::get_session()->set_variable('shp', $i_subshop);
                Registry::get_session()->set_variable('currentadminshop', $i_subshop);
                Registry::get_config()->set_shop_id($i_subshop);
            }
        } catch (User_Exception|Cookie_Exception $o_ex) {
            $my_utils_view->add_error_to_display($o_ex);
            $o_str = Str::get_str();
            $this->add_tpl_param('user', $o_str->htmlspecialchars($s_user));
            $this->add_tpl_param('pwd', $o_str->htmlspecialchars($s_pass));
            $this->add_tpl_param('profile', $s_profile ? $o_str->htmlspecialchars($s_profile) : '');
            return;
        } catch (\Oxid_Esales\Eshop\Core\Exception\Connection_Exception $o_ex) {
            $my_utils_view->add_error_to_display($o_ex);
        }
        //execute onAdminLogin() event
        $o_even_handler = ox_new(\Oxid_Esales\Eshop\Core\System_Event_Handler::class);
        $o_even_handler->on_admin_login(Registry::get_config()->get_shop_id());
        // #533
        if (isset($s_profile)) {
            $a_profiles = Registry::get_session()->get_variable('aAdminProfiles');
            if ($a_profiles && isset($a_profiles[$s_profile])) {
                // setting cookie to store last locally used profile
                $my_utils_server->set_ox_cookie('oxidadminprofile', $s_profile . '@' . implode('@', $a_profiles[$s_profile]), time() + 31536000, '/');
                Registry::get_session()->set_variable('profile', $a_profiles[$s_profile]);
            }
        } else {
            //deleting cookie info, as setting profile to default
            $my_utils_server->set_ox_cookie('oxidadminprofile', '', time() - 3600, '/');
        }
        // languages
        $i_lang = Registry::get_request()->get_request_escaped_parameter('chlanguage');
        $a_languages = Registry::get_lang()->get_admin_tpl_language_array();
        if ($i_lang === null || !isset($a_languages[$i_lang])) {
            $i_lang = key($a_languages);
        }
        $my_utils_server->set_ox_cookie('oxidadminlanguage', $a_languages[$i_lang]->abbr, time() + 31536000, '/');
        //P
        //\OxidEsales\Eshop\Core\Registry::getSession()->setVariable( "blAdminTemplateLanguage", $iLang );
        Registry::get_lang()->set_tpl_language($i_lang);
        return 'admin_start';
    }
    /**
     * Users are always authorized to use login page.
     * Rewrites authorization method.
     *
     * @return boolean
     */
    protected function authorize()
    {
        return true;
    }
    /**
     * Current view ID getter
     *
     * @return string
     */
    public function get_view_id()
    {
        return self::VIEW_ID;
    }
    /**
     * Get available admin interface languages
     *
     * @return array
     */
    protected function get_available_languages()
    {
        $s_def_lang = Registry::get_utils_server()->get_ox_cookie('oxidadminlanguage');
        $s_def_lang = $s_def_lang ?: $this->get_browser_language();
        $a_languages = Registry::get_lang()->get_admin_tpl_language_array();
        foreach ($a_languages as $o_lang) {
            $o_lang->selected = $s_def_lang == $o_lang->abbr ? 1 : 0;
        }
        return $a_languages;
    }
    /**
     * Get detected user browser language abbervation
     *
     * @return string
     */
    protected function get_browser_language()
    {
        return strtolower(substr((string) $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
    }
}