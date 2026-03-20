<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Controller\Frontend_Controller;
use Oxid_Esales\Eshop\Core\Exception\Routing_Exception;
use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Controller\View_Controller_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\Before_Headers_Send_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\View_Rendered_Event;
use ReflectionMethod;
use Symfony\Component\Filesystem\Path;
class Shop_Control extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Used to force handling, it allows other place like widget controller to skip it.
     *
     * @var bool
     */
    protected $_bl_main_tasks_executed;
    /**
     * Profiler start time
     *
     * @var double
     */
    protected $_d_time_start;
    /**
     * Profiler end time
     *
     * @var double
     */
    protected $_d_time_end;
    /**
     * errors to be displayed/returned
     *
     * @see _getErrors
     *
     * @var array
     */
    protected $_a_errors;
    /**
     * same as errors in session
     *
     * @see _getErrors
     *
     * @var array
     */
    protected $_a_all_errors;
    /**
     * same as controller errors in session
     *
     * @see _getErrors
     *
     * @var array
     */
    protected $_a_controller_errors;
    /**
     * output handler object
     *
     * @see _getOuput
     *
     * @var \OxidEsales\Eshop\Core\Output
     */
    protected $_o_output;
    /**
     * Cache manager instance
     */
    protected $_o_cache;
    /**
     * Main shop manager, that sets shop status, executes configuration methods.
     * Executes \OxidEsales\Eshop\Core\ShopControl::_runOnce(), if needed sets default class (according
     * to admin or regular activities). Additionally its possible to pass class name,
     * function name and parameters array to view, which will be executed.
     *
     * @param string $controllerKey Key of the controller class to be processed
     * @param string $function      Function name
     * @param array  $parameters    Parameters array
     * @param array  $viewsChain    Array of views names that should be initialized also
     */
    public function start($controller_key = null, $function = null, $parameters = null, $views_chain = null): void
    {
        try {
            $this->run_once();
            $function = !is_null($function) ? $function : Registry::get_request()->get_request_escaped_parameter('fnc');
            $controller_key = !is_null($controller_key) ? $controller_key : $this->get_start_controller_key();
            $controller_class = $this->get_controller_class($controller_key);
            $this->process($controller_class, $function, $parameters, $views_chain);
        } catch (System_Component_Exception $exception) {
            $this->handle_system_exception($exception);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Cookie_Exception $exception) {
            $this->handle_cookie_exception($exception);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Routing_Exception $exception) {
            $this->handle_routing_exception($exception);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
            $this->handle_base_exception($exception);
        }
    }
    /**
     * Returns the difference between stored profiler end time and start time. Works only after stopMonitoring() is
     * called, otherwise returns 0.
     *
     * @return double
     */
    public function get_total_time()
    {
        if ($this->_d_time_end && $this->_d_time_start) {
            return $this->_d_time_end - $this->_d_time_start;
        }
        return 0;
    }
    /**
     * Returns class id of controller which should be loaded.
     * When in doubt returns default start controller class.
     *
     * @return string
     */
    protected function get_start_controller_key()
    {
        $controller_key = Registry::get_config()->get_request_controller_id();
        // Use default route in case no controller id is given
        if (!$controller_key) {
            $session = Registry::get_session();
            if ($this->is_admin()) {
                $controller_key = $session->get_variable('auth') ? 'admin_start' : 'login';
            } else {
                $controller_key = $this->get_frontend_start_controller_key();
            }
            $session->set_variable('cl', $controller_key);
        }
        return $controller_key;
    }
    /**
     * Returns class id of controller which should be loaded.
     * When in doubt returns default start controller class.
     *
     * @param string $controllerKey Controller id
     *
     * @throws RoutingException
     * @return string
     */
    protected function resolve_controller_class($controller_key)
    {
        $resolved_class = Registry::get_controller_class_name_resolver()->get_class_name_by_id($controller_key);
        // If unmatched controller id is requested throw exception
        if (!$resolved_class) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Routing_Exception(sprintf('Controller "%s" cannot be resolved', $controller_key));
        }
        return $resolved_class;
    }
    /**
     * Returns id of controller that should be loaded at shop start.
     * Check whether we have to display mall start screen or not.
     *
     * @return string
     */
    protected function get_frontend_start_controller_key()
    {
        return 'start';
    }
    /**
     * Initiates object (object::init()), executes passed function
     * (\OxidEsales\Eshop\Core\ShopControl::executeFunction(), if method returns some string - will
     * redirect page and will call another function according to returned
     * parameters), renders object (object::render()). Performs output processing
     * \OxidEsales\Eshop\Core\Output::ProcessViewArray(). Passes template variables to template
     * engine witch generates output. Output is additionally processed
     * (\OxidEsales\Eshop\Core\Output::Process()), fixed links according search engines optimization
     * rules (configurable in Admin area). Finally echoes the output.
     *
     * @param string $class      Class name
     * @param string $function   Name of function
     * @param array  $parameters Parameters array
     * @param array  $viewsChain Array of views names that should be initialized also
     */
    protected function process($class, $function, $parameters = null, $views_chain = null)
    {
        start_profile('process');
        $config = Registry::get_config();
        // executing maintenance tasks
        $this->execute_maintenance_tasks();
        // starting resource monitor
        $this->start_monitor();
        // Initialize view object and it's components.
        $view = $this->initialize_view_object($class, $function, $parameters, $views_chain);
        $this->execute_action($view, $view->get_fnc_name());
        $output = $this->form_output($view);
        Container_Facade::dispatch(new View_Rendered_Event($this));
        $output_manager = $this->get_output_manager();
        $output_manager->set_charset($view->get_char_set());
        if (Registry::get_request()->get_request_escaped_parameter('renderPartial')) {
            $output_manager->set_output_format(\Oxid_Esales\Eshop\Core\Output::OUTPUT_FORMAT_JSON);
            $output_manager->output('errors', $this->get_formatted_errors($view->get_class_key()));
        }
        Container_Facade::dispatch(new Before_Headers_Send_Event($this, $view));
        $output_manager->send_headers();
        //Send headers that have been registered
        $header = Registry::get(\Oxid_Esales\Eshop\Core\Header::class);
        $header->send_header();
        $this->send_additional_headers($view);
        $output_manager->output('content', $output);
        $config->page_close();
        stop_profile('process');
        $this->stop_monitoring($view);
        $output_manager->flush_output();
    }
    /**
     * Executes regular maintenance functions..
     */
    protected function execute_maintenance_tasks()
    {
        if (isset($this->_bl_main_tasks_executed)) {
            return;
        }
        start_profile('executeMaintenanceTasks');
        ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class)->update_upcoming_prices();
        stop_profile('executeMaintenanceTasks');
    }
    /**
     * Executes provided function on view object.
     * If this function can not be executed (is protected or so), a RoutingException is thrown
     *
     * @param FrontendController $view
     * @param string             $functionName
     */
    protected function execute_action($view, $function_name)
    {
        if (!$this->can_execute_function($view, $function_name)) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Routing_Exception(sprintf('Non public method cannot be accessed: %s::%s', $view::class, $function_name));
        }
        $view->execute_function($function_name);
    }
    /**
     * Forms output from view object.
     *
     * @param FrontendController $view
     *
     * @return string
     */
    protected function form_output($view)
    {
        return $this->render($view);
    }
    /**
     * Method for sending any additional headers on every page requests.
     *
     * @param FrontendController $view
     */
    protected function send_additional_headers($view)
    {
    }
    /**
     * Initialize and return view object.
     *
     * @param string $class      View class
     * @param string $function   Function name
     * @param array  $parameters Parameters array
     * @param array  $viewsChain Array of views names that should be initialized also
     *
     * @return FrontendController
     */
    protected function initialize_view_object($class, $function, $parameters = null, $views_chain = null)
    {
        $class_key = Registry::get_controller_class_name_resolver()->get_id_by_class_name($class);
        $class_key = !is_null($class_key) ? $class_key : $class;
        //fallback
        /** @var ViewControllerInterface $controller */
        $controller = $this->is_service_controller($class_key, $class) ? Container_Facade::get($class) : ox_new($class);
        $controller->set_class_key($class_key);
        $controller->set_fnc_name($function);
        $controller->set_view_parameters($parameters);
        Registry::get_config()->set_active_view($controller);
        $this->on_view_creation($controller);
        $controller->init();
        return $controller;
    }
    /**
     * Event for any actions during view creation.
     *
     * @param FrontendController $view
     */
    protected function on_view_creation($view)
    {
    }
    /**
     * Check if method can be executed.
     *
     * @param FrontendController $view     View object to check if its method can be executed.
     * @param string             $function Method to check if it can be executed.
     *
     * @return bool
     */
    protected function can_execute_function($view, $function)
    {
        $can_execute = true;
        if ($function && method_exists($view, $function)) {
            $reflection_method = new ReflectionMethod($view, $function);
            if (!$reflection_method->is_public()) {
                $can_execute = false;
            }
        }
        return $can_execute;
    }
    /**
     * Format error messages from _getErrors and return as array.
     *
     * @param string $controllerName a class name
     *
     * @return array
     */
    protected function get_formatted_errors($controller_name)
    {
        $errors = $this->get_errors($controller_name);
        $formatted_errors = [];
        if (is_array($errors) && count($errors)) {
            foreach ($errors as $location => $ex2) {
                foreach ($ex2 as $key => $er) {
                    $error = unserialize($er, ['allowed_classes' => [\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class]]);
                    $formatted_errors[$location][$key] = $error->get_ox_message();
                }
            }
        }
        return $formatted_errors;
    }
    /**
     * Render BaseController object.
     *
     * @param FrontendController $view view object to render
     *
     * @return string
     */
    protected function render($view)
    {
        $template_name = $view->render();
        // Output processing. This is useful for modules. As sometimes you may want to process output manually.
        $output_manager = $this->get_output_manager();
        $view_data = $output_manager->process_view_array($view->get_view_data(), $view->get_class_key());
        $view->set_view_data($view_data);
        $renderer = $this->get_renderer();
        $view_data['oxEngineTemplateId'] = $view->get_view_id();
        $view_data = $this->pass_session_errors_to_view_data($view, $view_data);
        try {
            $output = $renderer->render_template($template_name, $view_data);
        } catch (\Throwable $exception) {
            $this->process_template_render_error($template_name, $exception);
            $view_data = $this->pass_session_errors_to_view_data($view, $view_data);
            $output = $renderer->render_template('message/exception', $view_data);
        }
        //Output processing - useful for modules as sometimes you may want to process output manually.
        $output = $output_manager->process($output, $view->get_class_key());
        return $output_manager->add_version_tags($output);
    }
    /**
     * @internal
     *
     * @return TemplateRendererInterface
     */
    private function get_renderer()
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    /**
     * Return output handler.
     *
     * @return \OxidEsales\Eshop\Core\Output
     */
    protected function get_output_manager()
    {
        if (!$this->_o_output) {
            $this->_o_output = ox_new(\Oxid_Esales\Eshop\Core\Output::class);
        }
        return $this->_o_output;
    }
    /**
     * Return page errors array.
     *
     * @param string $currentControllerName Class name
     *
     * @return array
     */
    protected function get_errors($current_controller_name)
    {
        if (null === $this->_a_errors) {
            $this->_a_errors = Registry::get_session()->get_variable('Errors');
            $this->_a_controller_errors = Registry::get_session()->get_variable('ErrorController');
            if (null === $this->_a_errors) {
                $this->_a_errors = [];
            }
            $this->_a_all_errors = $this->_a_errors;
        }
        // resetting errors of current controller or widget from session
        if (is_array($this->_a_controller_errors) && !empty($this->_a_controller_errors)) {
            foreach ($this->_a_controller_errors as $error_name => $controller_name) {
                if ($controller_name == $current_controller_name) {
                    unset($this->_a_all_errors[$error_name]);
                    unset($this->_a_controller_errors[$error_name]);
                }
            }
        } else {
            $this->_a_all_errors = [];
        }
        Registry::get_session()->set_variable('ErrorController', $this->_a_controller_errors);
        Registry::get_session()->set_variable('Errors', $this->_a_all_errors);
        return $this->_a_errors;
    }
    /**
     * This function is only executed one time here we perform checks if we
     * only need once per session.
     */
    protected function run_once()
    {
        $config = Registry::get_config();
        //Ensures config values are available, database connection is established,
        //session is started, a possible SeoUrl is decoded, globals and environment variables are set.
        $config->init();
        $run_once_executed = Registry::get_session()->get_variable('blRunOnceExecuted');
        if (!$run_once_executed && !$this->is_admin() && $config->is_productive_mode()) {
            // check if setup is still there
            $setup_index_file = Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'Setup', 'index.php');
            if (file_exists($setup_index_file)) {
                $tpl = 'message/err_setup';
                $active_view = ox_new(\Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::class);
                $context = ['oViewConf' => $active_view->get_view_config(), 'oView' => $active_view];
                $renderer = $this->get_renderer();
                $error_output = $renderer->render_template($tpl, $context);
                Registry::get_utils()->show_message_and_exit($error_output);
            }
            Registry::get_session()->set_variable('blRunOnceExecuted', true);
        }
    }
    /**
     * Checks if shop is in debug mode.
     *
     * @return bool
     */
    protected function is_debug_mode()
    {
        return Container_Facade::get_parameter('oxid_esales.debug_mode');
    }
    /**
     * Starts resource monitor.
     */
    protected function start_monitor()
    {
        if ($this->is_debug_mode()) {
            $this->_d_time_start = microtime(true);
        }
    }
    /**
     * Stops resource monitor, summarizes and outputs values.
     *
     * @param FrontendController $view View object
     */
    protected function stop_monitoring($view = null)
    {
        if (is_null($view)) {
            $controller_key = $this->get_start_controller_key();
            $controller_class = $this->get_controller_class($controller_key);
            $view = ox_new($controller_class);
        }
        if ($this->is_debug_mode() && !$this->is_admin()) {
            $debug_info = ox_new(\Oxid_Esales\Eshop\Core\Debug_Info::class);
            $log_id = md5(time() . random_int(0, mt_getrandmax()) . random_int(0, mt_getrandmax()));
            $header = $debug_info->format_general_info();
            $display = 'none';
            $monitor_message = $this->form_monitor_message($view);
            $log_message = "\n                <div id='oxidDebugInfo_{$log_id}'>\n                    <div style='color:#630;margin:15px 0 0;cursor:pointer'\n                         onclick='var el=document.getElementById(\"debugInfoBlock_{$log_id}\"); if (el.style.display==\"block\")el.style.display=\"none\"; else el.style.display = \"block\";'>\n                          {$header}(show/hide)\n                    </div>\n                    <div id='debugInfoBlock_{$log_id}' style='display:{$display}' class='debugInfoBlock' align='left'>\n                        {$monitor_message}\n                    </div>\n                    <script>\n                        var b = document.getElementById('oxidDebugInfo_{$log_id}');\n                        var c = document.body;\n                        if (c) { c.appendChild(b.parentNode.removeChild(b));}\n                    </script>\n                </div>";
            $this->get_output_manager()->output('debuginfo', $log_message);
        }
    }
    /**
     * Forms message for displaying monitoring information on the bottom of the page.
     *
     * @param FrontendController $view
     *
     * @return string
     */
    protected function form_monitor_message($view)
    {
        $debug_info = ox_new(\Oxid_Esales\Eshop\Core\Debug_Info::class);
        // Output timing
        $this->_d_time_end = microtime(true);
        $message = $debug_info->format_memory_usage();
        $message .= $debug_info->format_time_stamp();
        return $message . $debug_info->format_execution_time($this->get_total_time());
    }
    /**
     * Shows exceptionError page.
     * possible reason: class does not exist etc. --> just redirect to start page.
     *
     * @param \OxidEsales\Eshop\Core\Exception\StandardException $exception
     */
    protected function handle_system_exception($exception)
    {
        Registry::get_logger()->error($exception->get_message(), [$exception]);
        if ($this->is_debug_mode()) {
            Registry::get_utils_view()->add_error_to_display($exception);
            $this->process('exceptionError', 'displayExceptionError');
        } else {
            Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url() . 'cl=start');
        }
    }
    protected function handle_routing_exception(Routing_Exception $exception)
    {
        Registry::get_logger()->error($exception->get_message(), [$exception]);
        unset($_GET['fnc'], $_POST['fnc']);
        error_404_handler($_SERVER['REQUEST_URI']);
    }
    /**
     * Redirect to start page, in debug mode shows error message.
     *
     * @param \OxidEsales\Eshop\Core\Exception\StandardException $exception Exception
     */
    protected function handle_cookie_exception($exception)
    {
        if ($this->is_debug_mode()) {
            Registry::get_utils_view()->add_error_to_display($exception);
        }
        Registry::get_utils()->redirect(Registry::get_config()->get_shop_home_url() . 'cl=start', true, 302);
    }
    /**
     * Handling other not caught exceptions.
     *
     * @param \OxidEsales\Eshop\Core\Exception\StandardException $exception
     */
    protected function handle_base_exception($exception)
    {
        $this->log_exception($exception);
        if ($this->is_debug_mode()) {
            Registry::get_utils_view()->add_error_to_display($exception);
            $this->process('exceptionError', 'displayExceptionError');
        }
    }
    /**
     * Log an exception.
     *
     * This method forms part of the exception handling process. Any further exceptions must be caught.
     */
    protected function log_exception(\Exception $exception)
    {
        if (!$exception instanceof \Oxid_Esales\Eshop\Core\Exception\Standard_Exception) {
            $exception = new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception($exception->get_message(), $exception->get_code(), $exception);
        }
        Registry::get_logger()->error($exception->get_message(), [$exception]);
    }
    /**
     * Get controller class from key.
     * Fallback is to use key as class if no match can be found.
     *
     * @param string $controllerKey
     *
     * @return string
     */
    protected function get_controller_class($controller_key)
    {
        return $this->resolve_controller_class($controller_key);
    }
    private function process_template_render_error(string $template_name, \Throwable $renderer_error): void
    {
        $display_message = sprintf(Registry::get_lang()->translate_string('EXCEPTION_SYSTEMCOMPONENT_TEMPLATENOTFOUND'), $template_name);
        $displayed_exception = ox_new(Exception\System_Component_Exception::class, $display_message);
        $displayed_exception->set_component($template_name);
        if ($this->is_debug_mode()) {
            $this->_a_errors = null;
            Registry::get_utils_view()->add_error_to_display($displayed_exception);
        }
        Registry::get_logger()->error($displayed_exception->get_message(), [$renderer_error]);
    }
    private function pass_session_errors_to_view_data(View_Controller_Interface $view, array $view_data): array
    {
        $errors = $this->get_errors($view->get_class_key());
        if (\is_array($errors) && count($errors)) {
            Registry::get_utils_view()->pass_all_errors_to_view($view_data, $errors);
        }
        return $view_data;
    }
    private function is_service_controller(string $class_key, string $class): bool
    {
        return isset(Container_Facade::get_parameter('oxid.view_controllers_map')[$class_key]) && Container_Facade::has($class);
    }
}