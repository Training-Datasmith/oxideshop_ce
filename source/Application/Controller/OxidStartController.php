<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\System_Event_Handler;
/**
 * Encapsulates methods for application initialization.
 */
class Oxid_Start_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Initializes globals and environment vars
     */
    public function app_init(): void
    {
        $this->page_start();
        if ('oxstart' == \Oxid_Esales\Eshop\Core\Registry::get_config()->get_request_controller_id() || $this->is_admin()) {
            return;
        }
        $o_system_event_handler = $this->get_system_event_handler();
        $o_system_event_handler->on_shop_start();
    }
    /**
     * Renders error screen
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $error_number = Registry::get_request()->get_request_escaped_parameter('execerror');
        $templates = $this->get_error_templates();
        if (array_key_exists($error_number, $templates)) {
            return $templates[$error_number];
        }
        return 'message/err_unknown';
    }
    /**
     * Creates and starts session object, sets default currency.
     */
    public function page_start(): void
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $config->set_config_param('iMaxMandates', $config->get_config_param('IMS'));
        $config->set_config_param('iMaxArticles', $config->get_config_param('IMA'));
    }
    /**
     * Finalizes the script.
     */
    public function page_close(): void
    {
        $system_event_handler = $this->get_system_event_handler();
        $system_event_handler->on_shop_end();
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        if (isset($session)) {
            $session->freeze();
        }
    }
    /**
     * Return error number
     *
     * @return integer
     */
    public function get_error_number()
    {
        return Registry::get_request()->get_request_escaped_parameter('errornr');
    }
    /**
     * Returns which template should be used for specific error.
     *
     * @return array
     */
    protected function get_error_templates()
    {
        return ['unknown' => 'message/err_unknown'];
    }
    /**
     * Gets system event handler.
     *
     * @return SystemEventHandler
     */
    protected function get_system_event_handler()
    {
        return ox_new(\Oxid_Esales\Eshop\Core\System_Event_Handler::class);
    }
}