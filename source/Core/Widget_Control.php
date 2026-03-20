<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller;
use Oxid_Esales\Eshop\Core\Exception\Object_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
/**
 * Main shop actions controller. Processes user actions, logs
 * them (if needed), controls output, redirects according to
 * processed methods logic. This class is initialized from index.php
 */
class Widget_Control extends \Oxid_Esales\Eshop\Core\Shop_Control
{
    /**
     * Skip main tasks as it already handled in oxShopControl.
     *
     * @var bool
     */
    protected $_bl_main_tasks_executed = true;
    /**
     * Array of Views added to the view chain
     *
     * @var array
     */
    protected $parents_added = [];
    /**
     * Main shop widget manager. Sets needed parameters and calls parent::start method.
     *
     * Session variables:
     * <b>actshop</b>
     *
     * @param string $class      Class name
     * @param string $function   Function name
     * @param array  $parameters Parameters array
     * @param array  $viewsChain Array of views names that should be initialized also
     */
    public function start($class = null, $function = null, $parameters = null, $views_chain = null): void
    {
        if (!isset($views_chain) && Registry::get_request()->get_request_escaped_parameter('oxwparent')) {
            $views_chain = explode('|', (string) Registry::get_request()->get_request_escaped_parameter('oxwparent'));
        }
        parent::start($class, $function, $parameters, $views_chain);
        //perform tasks that should be done at the end of widget processing
        $this->run_last();
    }
    /**
     * Runs actions that should be performed at the controller finish.
     */
    protected function run_last()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($o_config->has_active_views_chain()) {
            // Removing current active view.
            $o_config->drop_last_active_view();
            foreach ($this->parents_added as $s_parent_class_name) {
                $o_config->drop_last_active_view();
            }
            // Setting back last active view.
            $engine = $this->get_renderer()->get_template_engine();
            $engine->add_global('oView', $o_config->get_active_view());
        }
    }
    /**
     * Initialize and return widget view object.
     *
     * @param string $class      View class
     * @param string $function   Function name
     * @param array  $parameters Parameters array
     * @param array  $viewsChain Array of views keys that should be initialized as well
     *
     * @throws ObjectException
     *
     * @return \OxidEsales\Eshop\Core\Controller\BaseController Current active view
     */
    protected function initialize_view_object($class, $function, $parameters = null, $views_chain = null)
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $active_views_ids = $config->get_active_views_ids();
        $active_views_ids = array_map(strtolower(...), $active_views_ids);
        $class_key = Registry::get_controller_class_name_resolver()->get_id_by_class_name($class);
        $class_key = !is_null($class_key) ? $class_key : $class;
        //fallback
        // if exists views chain, initializing these view at first
        if (is_array($views_chain) && !empty($views_chain)) {
            foreach ($views_chain as $parent_class_key) {
                $parent_class = Registry::get_controller_class_name_resolver()->get_class_name_by_id($parent_class_key);
                if ($parent_class_key != $class_key && !in_array(strtolower((string) $parent_class_key), $active_views_ids) && $parent_class) {
                    // creating parent view object
                    $view_object = ox_new($parent_class);
                    if ('oxubase' != strtolower((string) $parent_class_key)) {
                        $view_object->set_class_key($parent_class_key);
                    }
                    $config->set_active_view($view_object);
                    $this->parents_added[] = $parent_class_key;
                }
            }
        }
        $widget_view_object = parent::initialize_view_object($class, $function, $parameters, null);
        if (!is_a($widget_view_object, Widget_Controller::class)) {
            /** @var ObjectException $exception */
            $exception = ox_new(Object_Exception::class, $widget_view_object::class . ' is not an instance of ' . Widget_Controller::class);
            throw $exception;
        }
        // Set template name for current widget.
        if (!empty($parameters['oxwtemplate'])) {
            $widget_view_object->set_template_name($parameters['oxwtemplate']);
        }
        return $widget_view_object;
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
}