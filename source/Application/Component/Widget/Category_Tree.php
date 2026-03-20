<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

use function basename;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
class Category_Tree extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * Cartegory component used in template.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_categories' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/sidebar/categorytree';
    /**
     * @return string
     */
    public function render()
    {
        parent::render();
        $widget_type = $this->get_view_parameter('sWidgetType');
        if (!$widget_type) {
            return $this->_s_this_template;
        }
        $template = sprintf('widget/%s/categorylist', basename($widget_type));
        $template_exists = Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer()->exists($template);
        if ($template_exists) {
            $this->_s_this_template = $template;
        }
        return $this->_s_this_template;
    }
    /**
     * Returns the deep level of category tree
     */
    public function get_deep_level()
    {
        return $this->get_view_parameter('deepLevel');
    }
    /**
     * Content category getter.
     *
     * @return bool|string
     */
    public function get_content_category()
    {
        return Registry::get_request()->get_request_parameter('oxcid', false);
    }
}