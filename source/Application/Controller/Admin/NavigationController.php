<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Shop;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Shop_Version;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Administrator GUI navigation manager class.
 */
class Navigation_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Allowed host url
     *
     * @var string
     */
    protected $_s_allowed_host = 'http://admin.oxid-esales.com';
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $request = Registry::get_request();
        $session = Registry::get_session();
        $utils_server = Registry::get_utils_server();
        $item_param = $request->get_request_escaped_parameter('item');
        $item = $item_param ? basename((string) $item_param) : false;
        if (!$item) {
            $item = 'nav_frame';
            $favorites_param = $request->get_request_escaped_parameter('favorites');
            if (is_array($favorites_param)) {
                $utils_server->set_ox_cookie('oxidadminfavorites', implode('|', $favorites_param));
            }
        } else {
            $nav_tree = $this->get_navigation();
            $this->_a_view_data['menustructure'] = $nav_tree->get_dom_xml()->document_element->child_nodes;
            $this->_a_view_data['sVersion'] = Shop_Version::get_version();
            if (!$request->get_request_escaped_parameter('navReload')) {
                $template_extension = Container_Facade::get_parameter('oxid_esales.templating.engine_template_extension');
                if ($item === "home.{$template_extension}") {
                    $this->_a_view_data['aMessage'] = $this->do_start_up_checks();
                }
            } else {
                $session->remove('navReload');
            }
            $favorites_cookie = $utils_server->get_ox_cookie('oxidadminfavorites');
            $favorites = is_string($favorites_cookie) ? explode('|', $favorites_cookie) : [];
            if ($favorites) {
                $this->_a_view_data['menufavorites'] = $nav_tree->get_list_nodes($favorites);
                $this->_a_view_data['aFavorites'] = $favorites;
            }
            $history_cookie = $utils_server->get_ox_cookie('oxidadminhistory');
            $history = is_string($history_cookie) ? explode('|', $history_cookie) : [];
            if ($history) {
                $this->_a_view_data['menuhistory'] = $nav_tree->get_list_nodes($history);
            }
            $this->_a_view_data['blOpenHistory'] = $request->get_request_escaped_parameter('openHistory');
        }
        $is_mall_admin = $session->get_variable('malladmin');
        $shop_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop_List::class);
        if ($is_mall_admin) {
            $shop_list->get_id_title_list();
        } else {
            $shop_id = $session->get_variable('actshop');
            $shop = ox_new(Shop::class);
            $shop->load($shop_id);
            $shop_list->add($shop);
        }
        $this->_a_view_data['shoplist'] = $shop_list;
        $this->_a_view_data['shopURL'] = Registry::get_config()->get_shop_url();
        return $item;
    }
    /**
     * Changing active shop
     */
    public function chshp(): void
    {
        parent::chshp();
        // informing about basefrm parameters
        $this->_a_view_data['loadbasefrm'] = true;
        $this->_a_view_data['listview'] = Registry::get_request()->get_request_escaped_parameter('listview');
        $this->_a_view_data['editview'] = Registry::get_request()->get_request_escaped_parameter('editview');
        $this->_a_view_data['actedit'] = Registry::get_request()->get_request_escaped_parameter('actedit');
    }
    /**
     * Destroy session, redirects to admin login and clears cache
     */
    public function logout(): void
    {
        $session = Registry::get_session();
        $my_config = Registry::get_config();
        $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $o_user->logout();
        // kill session
        $session->destroy();
        //resetting content cache if needed
        if ($my_config->get_config_param('blClearCacheOnLogout')) {
            $this->reset_content_cache(true);
        }
        Registry::get_utils()->redirect('index.php', true, 302);
    }
    /**
     * Caches external url file locally, adds <base> tag with original url to load images and other links correcly
     */
    public function exturl(): void
    {
        $my_utils = Registry::get_utils();
        if ($s_url = Registry::get_request()->get_request_escaped_parameter('url')) {
            // Caching not allowed, redirecting
            $my_utils->redirect($s_url, true, 302);
        }
        $my_utils->show_message_and_exit('');
    }
    /**
     * Every Time Admin starts we perform these checks
     * returns some messages if there is something to display
     *
     * @return array
     */
    protected function do_start_up_checks()
    {
        $messages = [];
        $session = Registry::get_session();
        if (Registry::get_config()->get_config_param('blCheckSysReq') !== false) {
            // check if system requirements are ok
            $o_sys_req = ox_new(\Oxid_Esales\Eshop\Core\System_Requirements::class);
            if (!$o_sys_req->get_sys_req_status()) {
                $messages['warning'] = Registry::get_lang()->translate_string('NAVIGATION_SYSREQ_MESSAGE');
                $messages['warning'] .= '<a href="?cl=sysreq&amp;stoken=' . $session->get_session_challenge_token() . '" target="basefrm">';
                $messages['warning'] .= Registry::get_lang()->translate_string('NAVIGATION_SYSREQ_MESSAGE2') . '</a>';
            }
        } else {
            $messages['message'] = Registry::get_lang()->translate_string('NAVIGATION_SYSREQ_MESSAGE_INACTIVE');
            $messages['message'] .= '<a href="?cl=sysreq&amp;stoken=' . $session->get_session_challenge_token() . '" target="basefrm">';
            $messages['message'] .= Registry::get_lang()->translate_string('NAVIGATION_SYSREQ_MESSAGE2') . '</a>';
        }
        // version check
        if (Registry::get_config()->get_config_param('blCheckForUpdates')) {
            if ($s_version_notice = $this->check_version()) {
                $messages['message'] .= $s_version_notice;
            }
        }
        return $messages;
    }
    /**
     * Checks if newer shop version available. If true - returns message
     *
     * @return string
     */
    protected function check_version()
    {
        $query = 'https://admin.oxid-esales.com/' . $this->get_shop_edition() . '/onlinecheck.php?getlatestversion';
        $latest_version = Registry::get_utils_file()->read_remote_file_as_string($query);
        if ($latest_version) {
            $current_version = Shop_Version::get_version();
            if (version_compare($current_version, $latest_version, '<')) {
                return \sprintf(Registry::get_lang()->translate_string('NAVIGATION_NEW_VERSION_AVAILABLE'), $current_version, $latest_version);
            }
        }
    }
}