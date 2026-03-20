<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use stdClass;
/**
 * Performs Online License Key check.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_License_Check
{
    /**
     * Variable name to be used in oxConfig table
     */
    public const CONFIG_VAR_NAME = 'iOlcSuccess';
    /**
     * Expected valid response code.
     *
     * @var integer
     */
    protected $valid_response_code = 0;
    /**
     * Expected valid response message.
     *
     * @var string
     */
    protected $valid_response_message = 'ACK';
    /**
     * List of serial keys to validate.
     *
     * @var array
     */
    protected $serial_keys = [];
    /**
     * Error message for the user.
     *
     * @var string
     */
    protected $error_message = '';
    /**
     * Indicates exception event
     *
     * @var bool
     */
    protected $is_exception = false;
    /**
     * @var \OxidEsales\Eshop\Core\OnlineLicenseCheckCaller
     */
    protected $caller;
    /**
     * @var \OxidEsales\Eshop\Core\UserCounter
     */
    protected $user_counter;
    /**
     * @var \OxidEsales\Eshop\Core\Service\ApplicationServerExporterInterface
     */
    protected $app_server_exporter;
    /**
     * Sets servers manager.
     *
     * @param \OxidEsales\Eshop\Core\Service\ApplicationServerExporterInterface $appServerExporter
     */
    public function set_app_server_exporter($app_server_exporter): void
    {
        $this->app_server_exporter = $app_server_exporter;
    }
    /**
     * Gets servers manager.
     *
     * @return \OxidEsales\Eshop\Core\Service\ApplicationServerExporterInterface
     */
    public function get_app_server_exporter()
    {
        return $this->app_server_exporter;
    }
    /**
     * Sets user counter.
     *
     * @param \OxidEsales\Eshop\Core\UserCounter $userCounter
     */
    public function set_user_counter($user_counter): void
    {
        $this->user_counter = $user_counter;
    }
    /**
     * Gets user counter.
     *
     * @return \OxidEsales\Eshop\Core\UserCounter
     */
    protected function get_user_counter()
    {
        return $this->user_counter;
    }
    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Core\OnlineLicenseCheckCaller $caller
     */
    public function __construct($caller)
    {
        $this->caller = $caller;
    }
    /**
     * Get error message.
     *
     * @return string
     */
    public function get_error_message()
    {
        return $this->error_message;
    }
    /**
     * Indicates whether the exception was thrown
     *
     * @return bool
     */
    public function is_exception()
    {
        return $this->is_exception;
    }
    /**
     * Takes active serial key and performs online license check in case it has never been performed before.
     * In case of invalid license key, eShop is declared as unlicensed.
     * In case of validation exception (eg. service can not be reached) the check is postponed until the next call.
     */
    public function validate_shop_serials(): void
    {
        $a_serials = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aSerials');
        if (!$this->validate($a_serials) && !$this->is_exception()) {
            $this->start_grace_period();
        }
    }
    /**
     * The Online shop license check for the new serial is performed. Returns check result.
     *
     * @param string $serial Serial to check.
     *
     * @return bool
     */
    public function validate_new_serial($serial)
    {
        $serials = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aSerials');
        $serials[] = ['attributes' => ['state' => 'new'], 'value' => $serial];
        return $this->validate($serials);
    }
    /**
     * The Online shop license check is performed. Returns check result.
     *
     * @param array $serials Serial keys to be checked.
     *
     * @return bool
     */
    public function validate($serials)
    {
        $serials = (array) $serials;
        $this->set_is_exception(false);
        $result = false;
        try {
            $request = $this->form_request($serials);
            $caller = $this->get_caller();
            $response = $caller->do_request($request);
            $result = $this->validate_response($response);
            if ($result) {
                $this->log_success();
            }
        } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $ex) {
            $this->set_error_message($ex->get_message());
            $this->set_is_exception(true);
        }
        return $result;
    }
    /**
     * Set error message.
     *
     * @param string $errorMessage Error message
     */
    protected function set_error_message($error_message)
    {
        $this->error_message = $error_message;
    }
    /**
     * Gets caller.
     *
     * @return \OxidEsales\Eshop\Core\OnlineLicenseCheckCaller
     */
    protected function get_caller()
    {
        return $this->caller;
    }
    /**
     * Performs a check of the response code and message.
     *
     * @param \OxidEsales\Eshop\Core\OnlineLicenseCheckResponse $response
     *
     * @throws \OxidEsales\Eshop\Core\Exception\StandardException
     *
     * @return bool
     */
    protected function validate_response($response)
    {
        if (isset($response->code) && isset($response->message)) {
            if ($response->code == $this->valid_response_code && $response->message == $this->valid_response_message) {
                // serial keys are valid
                $valid = true;
            } else {
                // serial keys are not valid
                $this->set_error_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('OLC_ERROR_SERIAL_NOT_VALID'));
                $valid = false;
            }
        } else {
            // validation result is unknown
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('OLC_ERROR_RESPONSE_NOT_VALID');
        }
        return $valid;
    }
    /**
     * Builds request object with required parameters.
     *
     * @param array $serials Array of serials to add to request.
     *
     * @return \OxidEsales\Eshop\Core\OnlineLicenseCheckRequest
     */
    protected function form_request($serials)
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheckRequest $request */
        $request = ox_new(\Oxid_Esales\Eshop\Core\Online_License_Check_Request::class);
        $request->keys = ['key' => $serials];
        $request->product_specific_information = new stdClass();
        if (!is_null($this->get_app_server_exporter())) {
            $servers = $this->get_app_server_exporter()->export_app_server_list();
            $request->product_specific_information->servers = ['server' => $servers];
        }
        $counters = $this->form_counters();
        if (!empty($counters)) {
            $request->product_specific_information->counters = ['counter' => $counters];
        }
        return $request;
    }
    /**
     * Forms shop counters array for sending to OXID server.
     */
    protected function form_counters(): array
    {
        $user_counter = $this->get_user_counter();
        $counters = [];
        if (!is_null($this->get_user_counter())) {
            $counters[] = ['name' => 'admin users', 'value' => $user_counter->get_admin_count()];
            $counters[] = ['name' => 'active admin users', 'value' => $user_counter->get_active_admin_count()];
        }
        $counters[] = ['name' => 'subShops', 'value' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_mandate_count()];
        return $counters;
    }
    /**
     * Registers the latest Successful Online License check.
     */
    protected function log_success()
    {
        $time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $base_shop = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_base_shop_id();
        \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('str', \Oxid_Esales\Eshop\Core\Online_License_Check::CONFIG_VAR_NAME, $time, $base_shop);
    }
    /**
     * Sets exception flag.
     *
     * @param bool $isException Exception flag.
     */
    protected function set_is_exception($is_exception)
    {
        $this->is_exception = $is_exception;
    }
    /**
     * Starts grace period.
     * Sets to config options.
     */
    protected function start_grace_period()
    {
    }
}