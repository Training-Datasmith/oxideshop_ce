<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Contract\I_Display_Error;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
class Utils_View extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Templating instance getter
     *
     * @return TemplateRendererInterface
     */
    private function get_renderer()
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    /**
     * Returns rendered template output. According to debug configuration outputs
     * debug information.
     *
     * @param string $templateName template file name
     * @param object $oObject      object, witch template we wish to output
     *
     * @return string
     */
    public function get_template_output($template_name, $o_object)
    {
        $view_data = $o_object->get_view_data();
        if (!is_array($view_data)) {
            $view_data = [];
        }
        return $this->get_renderer()->render_template($template_name, $view_data);
    }
    /**
     * adds the given errors to the view array
     *
     * @param array $aView  view data array
     * @param array $errors array of errors to pass to view
     */
    public function pass_all_errors_to_view(&$a_view, $errors): void
    {
        if (count($errors) > 0) {
            foreach ($errors as $s_location => $a_ex2) {
                foreach ($a_ex2 as $s_key => $o_er) {
                    $a_view['Errors'][$s_location][$s_key] = unserialize($o_er);
                }
            }
        }
    }
    /**
     * Adds an exception to the array of displayed exceptions for the view
     * by default is displayed in the inc_header, but with the custom destination set to true
     * the exception won't be displayed by default but can be displayed where ever wanted in the tpl
     *
     * @param StandardException|IDisplayError|string $exception            an exception object or just a language local (string),
     *                                                                     which will be converted into a oxExceptionToDisplay object
     * @param bool                                   $blFull               if true the whole object is add to display (default false)
     * @param bool                                   $useCustomDestination true if the exception shouldn't be displayed
     *                                                                     at the default position (default false)
     * @param string                                 $customDestination    defines a name of the view variable containing
     *                                                                     the messages, overrides Parameter 'CustomError' ("default")
     * @param string                                 $activeController     defines a name of the controller, which should
     *                                                                     handle the error.
     */
    public function add_error_to_display($exception, $bl_full = false, $use_custom_destination = false, $custom_destination = '', $active_controller = ''): void
    {
        //default
        $destination = 'default';
        $custom_destination = $custom_destination ?: Registry::get_request()->get_request_escaped_parameter('CustomError');
        if ($use_custom_destination && $custom_destination) {
            $destination = $custom_destination;
        }
        //starting session if not yet started as all exception
        //messages are stored in session
        $session = Registry::get_session();
        if (!$session->get_id() && !$session->is_header_sent()) {
            $session->set_force_new_session();
            $session->start();
        }
        $session_errors = Registry::get_session()->get_variable('Errors');
        if ($exception instanceof \Oxid_Esales\Eshop\Core\Exception\Standard_Exception) {
            $exception_to_display = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $exception_to_display->set_message($exception->get_message());
            $exception_to_display->set_exception_type($exception->get_type());
            if ($exception instanceof \Oxid_Esales\Eshop\Core\Exception\System_Component_Exception) {
                $exception_to_display->set_message_args($exception->get_component());
            }
            $exception_to_display->set_values($exception->get_values());
            $exception_to_display->set_stack_trace($exception->get_trace_as_string());
            $exception_to_display->set_debug($bl_full);
            $exception = $exception_to_display;
        } elseif ($exception instanceof \Throwable) {
            $temp_exception = $exception;
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Display_Error::class);
            $exception->set_message($temp_exception->get_message());
        } elseif ($exception && !$exception instanceof \Oxid_Esales\Eshop\Core\Contract\I_Display_Error) {
            $temp_exception = $exception;
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Display_Error::class);
            $exception->set_message($temp_exception);
        } elseif ($exception instanceof \Oxid_Esales\Eshop\Core\Contract\I_Display_Error) {
            // take the object
        } else {
            $exception = null;
        }
        if ($exception) {
            $session_errors[$destination][] = serialize($exception);
            Registry::get_session()->set_variable('Errors', $session_errors);
            if ($active_controller == '') {
                $active_controller = Registry::get_request()->get_request_escaped_parameter('actcontrol');
            }
            if ($active_controller) {
                $a_controller_errors[$destination] = $active_controller;
                Registry::get_session()->set_variable('ErrorController', $a_controller_errors);
            }
        }
    }
}