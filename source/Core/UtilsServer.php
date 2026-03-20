<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use function array_key_exists;
use function in_array;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Application\Model\User;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge\Password_Service_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
/**
 * Server data manipulation class
 */
class Utils_Server extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * user cookies
     *
     * @var array
     */
    protected $_a_user_cookie = [];
    /**
     * Session cookie parameter name
     *
     * @var string
     */
    protected $_s_session_cookies_name = 'aSessionCookies';
    /**
     * Session stored cookies
     *
     * @var array
     */
    protected $_s_session_cookies = [];
    /**
     * sets cookie
     *
     * @param string $sName cookie name
     * @param string $sValue value
     * @param int $iExpire expire time
     * @param string $sPath The path on the server in which the cookie will be available on
     * @param string $sDomain The domain that the cookie is available.
     * @param bool $blToSession is true, records cookie information to session
     * @param bool $blSecure if true, transfer cookie only via SSL
     * @param bool $blHttpOnly if true, only accessible via HTTP
     *
     * @return bool
     */
    public function set_ox_cookie($s_name, $s_value = '', $i_expire = 0, $s_path = '/', $s_domain = null, $bl_to_session = true, $bl_secure = false, $bl_http_only = true)
    {
        if ($bl_to_session && !$this->is_admin()) {
            $this->save_session_cookie($s_name, $s_value, $i_expire, $s_path, $s_domain);
        }
        if (php_sapi_name() === 'cli') {
            // do NOT set cookies in cli because it would issue warnings
            return;
        }
        //if shop runs in https only mode we can set secure flag to all cookies
        $bl_secure = $bl_secure || Registry::get_config()->is_ssl();
        return setcookie($s_name, $s_value, ['expires' => $i_expire, 'path' => $this->get_cookie_path($s_path), 'domain' => $this->get_cookie_domain($s_domain), 'secure' => $bl_secure, 'httponly' => $bl_http_only, 'samesite' => 'Lax']);
    }
    protected $_bl_save_to_session;
    /**
     * Checks if cookie must be saved to session in order to transfer it to different domain
     *
     * @return bool
     */
    protected function must_save_to_session()
    {
        if ($this->_bl_save_to_session === null) {
            $this->_bl_save_to_session = false;
            $my_config = Registry::get_config();
            if ($my_config->get_shop_url()) {
                return true;
            }
        }
        return $this->_bl_save_to_session;
    }
    /**
     * Returns session cookie key
     *
     * @param bool $blGet mode - true - get, false - set cookie
     *
     * @return string
     */
    protected function get_session_cookie_key($bl_get)
    {
        $bl_ssl = Registry::get_config()->is_ssl();
        if ($bl_get) {
            return $bl_ssl ? 'ssl' : 'nossl';
        }
        return $bl_ssl ? 'nossl' : 'ssl';
    }
    /**
     * Copies cookie info to session
     *
     * @param string $sName cookie name
     * @param string $sValue cookie value
     * @param int $iExpire expiration time
     * @param string $sPath cookie path
     * @param string $sDomain cookie domain
     */
    protected function save_session_cookie($s_name, $s_value, $i_expire, $s_path, $s_domain)
    {
        if ($this->must_save_to_session()) {
            $a_cookie_data = ['value' => $s_value, 'expire' => $i_expire, 'path' => $s_path, 'domain' => $s_domain];
            $a_session_cookies = (array) Registry::get_session()->get_variable($this->_s_session_cookies_name);
            $a_session_cookies[$this->get_session_cookie_key(false)][$s_name] = $a_cookie_data;
            Registry::get_session()->set_variable($this->_s_session_cookies_name, $a_session_cookies);
        }
    }
    /**
     * Stored all session cookie info to cookies
     */
    public function load_session_cookies(): void
    {
        $session_cookies = Registry::get_session()->get_variable($this->_s_session_cookies_name);
        if ($session_cookies) {
            $s_key = $this->get_session_cookie_key(true);
            if (isset($session_cookies[$s_key])) {
                // writing session data to cookies
                foreach ($session_cookies[$s_key] as $s_name => $a_cookie_data) {
                    $this->set_ox_cookie($s_name, $a_cookie_data['value'], $a_cookie_data['expire'], $a_cookie_data['path'], $a_cookie_data['domain'], false);
                    $this->_s_session_cookies[$s_name] = $a_cookie_data['value'];
                }
                // cleanup
                unset($session_cookies[$s_key]);
                Registry::get_session()->set_variable($this->_s_session_cookies_name, $session_cookies);
            }
        }
    }
    /**
     * @param string $path
     *
     * @return string
     */
    protected function get_cookie_path($path)
    {
        return Container_Facade::get_parameter('oxid_esales.cookie_paths')[Container_Facade::get(Context_Interface::class)->get_current_shop_id()] ?? $path ?: '';
    }
    /**
     * @param string $domain
     *
     * @return string
     */
    protected function get_cookie_domain($domain)
    {
        return $domain ?: Container_Facade::get_parameter('oxid_esales.cookie_domains')[Container_Facade::get(Context_Interface::class)->get_current_shop_id()] ?? '';
    }
    /**
     * Returns cookie $sName value.
     * If optional parameter $sName is not set then getCookie() returns whole cookie array
     *
     * @param string $sName cookie param name
     *
     * @return mixed
     */
    public function get_ox_cookie($s_name = null)
    {
        $s_value = null;
        if ($s_name && isset($_COOKIE[$s_name])) {
            $s_value = Registry::get_config()->check_param_special_chars($_COOKIE[$s_name]);
        } elseif ($s_name && !isset($_COOKIE[$s_name])) {
            $s_value = $this->_s_session_cookies[$s_name] ?? null;
        } elseif (!$s_name && isset($_COOKIE)) {
            $s_value = $_COOKIE;
        }
        return $s_value;
    }
    /**
     * Returns remote IP address
     *
     * @return string
     */
    public function get_remote_address()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $s_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $s_ip = preg_replace('/,.*$/', '', (string) $s_ip);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $s_ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $s_ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }
        return $s_ip;
    }
    /**
     * returns a server constant
     *
     * @param string $sServVar optional - which server var should be returned, if null returns whole $_SERVER
     *
     * @return mixed
     */
    public function get_server_var($s_serv_var = null)
    {
        $s_value = null;
        if ($s_serv_var && isset($_SERVER[$s_serv_var])) {
            $s_value = $_SERVER[$s_serv_var];
        } elseif (!$s_serv_var) {
            $s_value = $_SERVER;
        }
        return $s_value;
    }
    public function set_user_cookie($user_name, $password_hash, $shop_id = null, $timeout = 31536000, $salt = User::USER_COOKIE_SALT): void
    {
        $my_config = Registry::get_config();
        $shop_id ??= $my_config->get_shop_id();
        $ssl_url = $my_config->get_shop_url();
        $password_service_bridge = Container_Facade::get(Password_Service_Bridge_Interface::class);
        $this->_a_user_cookie[$shop_id] = $user_name . '@@@' . $password_service_bridge->hash($password_hash . $salt);
        $this->set_ox_cookie('oxid_' . $shop_id, $this->_a_user_cookie[$shop_id], Registry::get_utils_date()->get_time() + $timeout, '/', null, true, strncasecmp((string) $ssl_url, 'https', 5) === 0);
        $this->set_ox_cookie('oxid_' . $shop_id . '_autologin', '1', Registry::get_utils_date()->get_time() + $timeout);
    }
    public function delete_user_cookie($shop_id = null): void
    {
        $my_config = Registry::get_config();
        $shop_id = !$shop_id ? Registry::get_config()->get_shop_id() : $shop_id;
        $ssl_url = $my_config->get_shop_url();
        $this->_a_user_cookie[$shop_id] = '';
        $this->set_ox_cookie('oxid_' . $shop_id, '', Registry::get_utils_date()->get_time() - 3600, '/', null, true, strncasecmp((string) $ssl_url, 'https', 5) === 0);
        $this->set_ox_cookie('oxid_' . $shop_id . '_autologin', '0', Registry::get_utils_date()->get_time() - 3600);
    }
    /**
     * Returns cookie stored used login data
     *
     * @param string $sShopId shop ID (default null)
     *
     * @return string
     */
    public function get_user_cookie($s_shop_id = null)
    {
        $my_config = Registry::get_config();
        $s_shop_id = !$s_shop_id ? $my_config->get_shop_id() : $s_shop_id;
        // check for SSL connection
        if (!$my_config->is_ssl() && $this->get_ox_cookie('oxid_' . $s_shop_id . '_autologin') == '1') {
            $ssl_url = rtrim((string) $my_config->get_shop_url(), '/') . $_SERVER['REQUEST_URI'];
            if (strncasecmp($ssl_url, 'https', 5) === 0) {
                Registry::get_utils()->redirect($ssl_url, true, 302);
            }
        }
        if (array_key_exists($s_shop_id, $this->_a_user_cookie) && $this->_a_user_cookie[$s_shop_id] !== null) {
            return $this->_a_user_cookie[$s_shop_id] ?: null;
        }
        return $this->_a_user_cookie[$s_shop_id] = $this->get_ox_cookie('oxid_' . $s_shop_id);
    }
    /**
     * @return bool
     */
    public function is_trusted_client_ip()
    {
        return in_array($this->get_remote_address(), Container_Facade::get_parameter('oxid_esales.trusted_ips'), true);
    }
    /**
     * Removes MSIE(\s)?(\S)*(\s) from browser agent information
     *
     * @param string $sAgent browser user agent idenfitier
     *
     * @return string
     */
    public function process_user_agent_info($s_agent)
    {
        if ($s_agent) {
            return Str::get_str()->preg_replace("/MSIE(\\s)?(\\S)*(\\s)/", '', (string) $s_agent);
        }
        return $s_agent;
    }
    /**
     * Compares current URL to supplied string
     *
     * @param string $sURL URL
     *
     * @return bool true if $sURL is equal to current page URL
     */
    public function is_current_url($s_url)
    {
        // Missing protocol, cannot proceed, assuming true.
        if (!$s_url || !str_starts_with($s_url, 'http')) {
            return true;
        }
        $s_server_host = $this->get_server_var('HTTP_HOST');
        $bl_is_current_url = $this->is_url_host_server_host($s_url, $s_server_host);
        if (!$bl_is_current_url) {
            $s_server_host = $this->get_server_var('HTTP_X_FORWARDED_HOST');
            if ($s_server_host) {
                $bl_is_current_url = $this->is_url_host_server_host($s_url, $s_server_host);
            }
        }
        return $bl_is_current_url;
    }
    /**
     * Check if the given URL is same as used for request.
     * The URL in this context is the base address for the shop e.g. https://www.domain.com/shop/
     * the protocol is optional (www.domain.com/shop/)
     * but the protocol relative syntax (//www.domain.com/shop/) is not yet supported.
     *
     * @param string $sURL URL to check if is same as request.
     * @param string $sServerHost request host.
     *
     * @return bool true if $sURL is equal to current page URL
     */
    public function is_url_host_server_host($s_url, $s_server_host): bool
    {
        // #4010: force_sid added in https to every link
        preg_match("/^(https?:\\/\\/)?(www\\.)?([^\\/]+)/i", $s_url, $matches);
        $s_url_host = $matches[3] ?? null;
        preg_match("/^(https?:\\/\\/)?(www\\.)?([^\\/]+)/i", (string) $s_server_host, $matches);
        $s_real_host = $matches[3] ?? null;
        //fetch the path from SCRIPT_NAME and ad it to the $sServerHost
        $s_script_name = $this->get_server_var('SCRIPT_NAME');
        $s_current_host = preg_replace('/\/(modules\/[\w\/]*)?\w*\.php.*/', '', $s_server_host . $s_script_name);
        //remove double slashes all the way
        $s_current_host = str_replace('/', '', $s_current_host);
        $s_url = str_replace('/', '', $s_url);
        if (!($s_url && $s_current_host)) {
            return false;
        }
        if (!str_contains($s_url, $s_current_host)) {
            return false;
        }
        //bug fix #0002991
        if ($s_url_host == $s_real_host) {
            return true;
        }
        return false;
    }
    /**
     * Return server id by server system information.
     *
     * @return string
     */
    public function get_server_node_id()
    {
        return md5($this->get_server_name() . $this->get_server_ip());
    }
    /**
     * Return local machine ip.
     *
     * @return string
     */
    public function get_server_ip()
    {
        return $this->get_server_var('SERVER_ADDR');
    }
    /**
     * Return server system parameter similar as unix uname.
     */
    private function get_server_name(): string
    {
        return php_uname();
    }
}