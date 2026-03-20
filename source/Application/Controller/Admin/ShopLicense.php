<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Shop;
use Oxid_Esales\Eshop\Core\Curl;
use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop\Core\Online_Caller;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Shop_Version;
use Throwable;
/**
 * Admin shop license setting manager.
 * Collects shop license settings, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> License.
 */
class Shop_License extends \Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Configuration
{
    /** @var string Current class template */
    protected $_s_this_template = 'shop_license';
    /** @var string Current shop version links for edition. */
    private string $version_check_link = 'https://admin.oxid-esales.com/CE/onlinecheck.php';
    /** @inheritdoc */
    public function render()
    {
        if (Registry::get_config()->is_demo_shop()) {
            /** @var SystemComponentException $oSystemComponentException */
            $o_system_component_exception = ox_new(System_Component_Exception::class, 'license');
            throw $o_system_component_exception;
        }
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if ($sox_id != '-1') {
            // load object
            $o_shop = ox_new(Shop::class);
            $o_shop->load($sox_id);
            $this->_a_view_data['edit'] = $o_shop;
        }
        $this->_a_view_data['version'] = Shop_Version::get_version();
        $this->_a_view_data['aCurVersionInfo'] = $this->fetch_cur_version_info($this->version_check_link);
        if (!$this->can_update()) {
            $this->_a_view_data['readonly'] = true;
        }
        return $this->_s_this_template;
    }
    /**
     * Checks if the license key update is allowed.
     */
    protected function can_update(): bool
    {
        $my_config = Registry::get_config();
        $bl_is_mall_admin = Registry::get_session()->get_variable('malladmin');
        if (!$bl_is_mall_admin) {
            return false;
        }
        if ($my_config->is_demo_shop()) {
            return false;
        }
        return true;
    }
    /**
     * Fetch current shop version information from url
     *
     * @param string $sUrl current version info fetching url by edition
     */
    protected function fetch_cur_version_info($s_url): string
    {
        try {
            $response = $this->request_version_info($s_url);
        } catch (Throwable $e) {
            /** Exception is not logged! */
            $this->handle_connection_error($e);
            return '';
        }
        $response = $this->filter_version_checker_response($response);
        $newest_shop_version = $this->parse_response_for_the_newest_shop_version($response);
        return $newest_shop_version && $this->shop_is_outdated($newest_shop_version) ? $this->insert_link_to_shop_update_documentation($response) : $response;
    }
    private function request_version_info(string $url): string
    {
        $curl = ox_new(Curl::class);
        $curl->set_method('POST');
        $curl->set_url(sprintf('%s/%s', $url, $this->get_language_abbreviation()));
        $curl->set_parameters(['myversion' => Shop_Version::get_version()]);
        $curl->set_option(Curl::CONNECT_TIMEOUT_OPTION, Online_Caller::CURL_CONNECT_TIMEOUT);
        $curl->set_option(Curl::EXECUTION_TIMEOUT_OPTION, Online_Caller::CURL_EXECUTION_TIMEOUT);
        return $curl->execute();
    }
    private function get_language_abbreviation(): string
    {
        $language = Registry::get_lang();
        return $language->get_language_abbr($language->get_tpl_language());
    }
    private function handle_connection_error(Throwable $e): void
    {
        $this->display_error_message($e->get_message());
    }
    private function display_error_message(string $message): void
    {
        Registry::get_utils_view()->add_error_to_display(sprintf('%s! %s.', Registry::get_lang()->translate_string('ADMIN_SETTINGS_LICENSE_VERSION_FETCH_INFO_ERROR'), sprintf(Registry::get_lang()->translate_string('CURL_EXECUTE_ERROR'), $message)));
    }
    private function filter_version_checker_response(string $response): string
    {
        $response = strip_tags(trim($response), '<br><b>');
        return str_replace(['<br/>', '<br />'], '<br>', $response);
    }
    private function parse_response_for_the_newest_shop_version(string $response): string
    {
        preg_match_all('/[1-9]{1,3}\.\d{1,3}\.\d{1,3}/', $response, $matches);
        return $matches[0][1] ?? '';
    }
    private function shop_is_outdated(string $newest_shop_version): bool
    {
        return version_compare(Shop_Version::get_version(), $newest_shop_version, '<');
    }
    private function insert_link_to_shop_update_documentation(string $response): string
    {
        $lines = explode('<br>', $response);
        $line_with_update_text = array_key_last($lines) - 1;
        $documentation_url = Registry::get_lang()->translate_string('VERSION_UPDATE_LINK');
        $lines[$line_with_update_text] = "<a id='linkToUpdate' href='{$documentation_url}' target='_blank'>{$lines[$line_with_update_text]}</a>";
        return implode('<br>', $lines);
    }
}