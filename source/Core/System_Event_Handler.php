<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Exception;
use Oxid_Esales\Eshop\Core\Dao\Application_Server_Dao;
use Oxid_Esales\Eshop\Core\Module\Module_List;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Service\Application_Server_Exporter;
use Oxid_Esales\Eshop\Core\Service\Application_Server_Service;
/**
 * Contains system event handler methods
 *
 * @internal Do not make a module extension for this class.
 */
class System_Event_Handler
{
    /**
     * @var \OxidEsales\Eshop\Core\OnlineModuleVersionNotifier
     */
    private $online_module_version_notifier;
    /**
     * @var \OxidEsales\Eshop\Core\OnlineLicenseCheck
     */
    private $online_license_check;
    /**
     * OLC dependency setter
     */
    public function set_online_license_check(\Oxid_Esales\Eshop\Core\Online_License_Check $online_license_check): void
    {
        $this->online_license_check = $online_license_check;
    }
    /**
     * OLC dependency getter
     *
     * @return \OxidEsales\Eshop\Core\OnlineLicenseCheck
     */
    public function get_online_license_check()
    {
        if (!$this->online_license_check) {
            /** @var \OxidEsales\Eshop\Core\Curl $curl */
            $curl = ox_new(\Oxid_Esales\Eshop\Core\Curl::class);
            /** @var \OxidEsales\Eshop\Core\OnlineServerEmailBuilder $emailBuilder */
            $email_builder = ox_new(\Oxid_Esales\Eshop\Core\Online_Server_Email_Builder::class);
            /** @var \OxidEsales\Eshop\Core\SimpleXml $simpleXml */
            $simple_xml = ox_new(\Oxid_Esales\Eshop\Core\Simple_Xml::class);
            /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheckCaller $licenseCaller */
            $license_caller = ox_new(\Oxid_Esales\Eshop\Core\Online_License_Check_Caller::class, $curl, $email_builder, $simple_xml);
            /** @var \OxidEsales\Eshop\Core\UserCounter $userCounter */
            $user_counter = ox_new(\Oxid_Esales\Eshop\Core\User_Counter::class);
            /** @var ApplicationServerExporter $appServerExporter */
            $app_server_exporter = $this->get_application_server_exporter();
            /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheck $OLC */
            $OLC = ox_new(\Oxid_Esales\Eshop\Core\Online_License_Check::class, $license_caller);
            $OLC->set_app_server_exporter($app_server_exporter);
            $OLC->set_user_counter($user_counter);
            $this->set_online_license_check($OLC);
        }
        return $this->online_license_check;
    }
    /**
     * ApplicationServerExporter dependency setter
     *
     * @return \OxidEsales\Eshop\Core\Service\ApplicationServerExporterInterface
     */
    protected function get_application_server_exporter()
    {
        $app_server_service = $this->get_app_server_service();
        return ox_new(Application_Server_Exporter::class, $app_server_service);
    }
    /**
     * OnlineModuleVersionNotifier dependency setter
     */
    public function set_online_module_version_notifier(\Oxid_Esales\Eshop\Core\Online_Module_Version_Notifier $online_module_version_notifier): void
    {
        $this->online_module_version_notifier = $online_module_version_notifier;
    }
    /**
     * OnlineModuleVersionNotifier dependency getter
     *
     * @return \OxidEsales\Eshop\Core\OnlineModuleVersionNotifier
     */
    public function get_online_module_version_notifier()
    {
        if (!$this->online_module_version_notifier) {
            /** @var \OxidEsales\Eshop\Core\Curl $curl */
            $curl = ox_new(\Oxid_Esales\Eshop\Core\Curl::class);
            /** @var \OxidEsales\Eshop\Core\OnlineServerEmailBuilder $mailBuilder */
            $mail_builder = ox_new(\Oxid_Esales\Eshop\Core\Online_Server_Email_Builder::class);
            /** @var \OxidEsales\Eshop\Core\SimpleXml $simpleXml */
            $simple_xml = ox_new(\Oxid_Esales\Eshop\Core\Simple_Xml::class);
            /** @var \OxidEsales\Eshop\Core\OnlineModuleVersionNotifierCaller $onlineModuleVersionNotifierCaller */
            $online_module_version_notifier_caller = ox_new(\Oxid_Esales\Eshop\Core\Online_Module_Version_Notifier_Caller::class, $curl, $mail_builder, $simple_xml);
            /** @var \OxidEsales\Eshop\Core\OnlineModuleVersionNotifier $onlineModuleVersionNotifier */
            $online_module_version_notifier = ox_new(\Oxid_Esales\Eshop\Core\Online_Module_Version_Notifier::class, $online_module_version_notifier_caller, ox_new(Module_List::class));
            $this->set_online_module_version_notifier($online_module_version_notifier);
        }
        return $this->online_module_version_notifier;
    }
    /**
     * onAdminLogin() is called on every successful login to the backend
     */
    public function on_admin_login(): void
    {
        try {
            $this->get_online_module_version_notifier()->version_notify();
        } catch (Exception) {
        }
    }
    /**
     * Perform shop startup related actions, like license check.
     */
    public function on_shop_start(): void
    {
        $this->validate_offline();
    }
    /**
     * Perform shop finishing up related actions, like updating app server data.
     */
    public function on_shop_end(): void
    {
        $this->validate_online();
    }
    /**
     * Check if shop is valid online.
     */
    protected function validate_online()
    {
        try {
            $app_server_service = $this->get_app_server_service();
            if (Registry::get_config()->is_admin()) {
                $app_server_service->update_app_server_information_in_admin();
            } else {
                $app_server_service->update_app_server_information_in_frontend();
            }
            if (!Registry::get_utils()->is_search_engine()) {
                $this->send_shop_information();
            }
        } catch (Exception $exception) {
            Registry::get_logger()->error($exception->get_message(), [$exception]);
        }
    }
    /**
     * Sends shop information to oxid servers.
     */
    protected function send_shop_information()
    {
        if ($this->need_to_send_shop_information()) {
            $this->update_next_check_time();
            $online_license_check = $this->get_online_license_check();
            $online_license_check->validate_shop_serials();
        }
    }
    /**
     * Check if need to send information.
     * We will not send information on each request due to possible performance drop.
     */
    private function need_to_send_shop_information(): bool
    {
        return $this->get_next_check_time() < $this->get_current_time();
    }
    /**
     * Return time stamp when shop was checked last with white noise from config.
     */
    private function get_next_check_time(): int
    {
        return (int) Registry::get_config()->get_system_config_parameter('sOnlineLicenseNextCheckTime');
    }
    /**
     * Update when shop was checked last time with white noise.
     * White noise is used to separate call time for different shop.
     */
    private function update_next_check_time(): void
    {
        $hour_to_check = $this->get_check_time();
        /** @var \OxidEsales\Eshop\Core\UtilsDate $utilsDate */
        $utils_date = Registry::get_utils_date();
        $next_check_time = $utils_date->form_time('tomorrow', $hour_to_check);
        Registry::get_config()->save_system_config_parameter('str', 'sOnlineLicenseNextCheckTime', $next_check_time);
    }
    /**
     * Returns time (hour minutes seconds) when to perform license check.
     * Create if does not exist.
     *
     * @return string time formed as H:i:s
     */
    private function get_check_time()
    {
        $check_time = Registry::get_config()->get_system_config_parameter('sOnlineLicenseCheckTime');
        if (!$check_time) {
            $hour_to_check = random_int(8, 23);
            $minute_to_check = random_int(0, 59);
            $second_to_check = random_int(0, 59);
            $check_time = $hour_to_check . ':' . $minute_to_check . ':' . $second_to_check;
            Registry::get_config()->save_system_config_parameter('str', 'sOnlineLicenseCheckTime', $check_time);
        }
        return $check_time;
    }
    /**
     * Return current time - time stamp.
     *
     * @return int
     */
    private function get_current_time()
    {
        /** @var \OxidEsales\Eshop\Core\UtilsDate $utilsDate */
        $utils_date = Registry::get_utils_date();
        return $utils_date->get_time();
    }
    /**
     * Check if shop valid and do related actions.
     */
    protected function validate_offline()
    {
    }
    /**
     * Gets application server service.
     *
     * @return \OxidEsales\Eshop\Core\Service\ApplicationServerServiceInterface
     */
    protected function get_app_server_service()
    {
        return ox_new(Application_Server_Service::class, ox_new(Application_Server_Dao::class, \Oxid_Esales\Eshop\Core\Database_Provider::get_db(), Registry::get_config()), ox_new(\Oxid_Esales\Eshop\Core\Utils_Server::class), Registry::get('oxUtilsDate')->get_time());
    }
}