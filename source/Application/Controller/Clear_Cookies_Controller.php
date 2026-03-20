<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\All_Cookies_Removed_Event;
/**
 * CMS - loads pages and displays it
 */
class Clear_Cookies_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current view template
     *
     * @var string
     */
    protected $_s_this_template = 'page/info/clearcookies';
    /**
     * Executes parent::render(), passes template variables to
     * template engine and generates content. Returns the name
     * of template to render content::_sThisTemplate
     *
     * @return  string  $this->_sThisTemplate   current template file name
     */
    public function render()
    {
        parent::render();
        $this->remove_cookies();
        return $this->_s_this_template;
    }
    /**
     * Clears all cookies
     */
    protected function remove_cookies()
    {
        $o_utils_server = Registry::get_utils_server();
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $a_cookies = explode(';', (string) $_SERVER['HTTP_COOKIE']);
            foreach ($a_cookies as $s_cookie) {
                $s_raw_cookie = explode('=', $s_cookie);
                $o_utils_server->set_ox_cookie(trim($s_raw_cookie[0]), '', time() - 10000, '/');
            }
        }
        $o_utils_server->set_ox_cookie('language', '', time() - 10000, '/');
        $o_utils_server->set_ox_cookie('displayedCookiesNotification', '', time() - 10000, '/');
        Container_Facade::dispatch(new All_Cookies_Removed_Event());
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
        $a_path['title'] = Registry::get_lang()->translate_string('INFO_ABOUT_COOKIES', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}