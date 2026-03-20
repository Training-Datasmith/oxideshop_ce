<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Widget parent.
 * Gather functionality needed for all widgets but not for other views.
 */
class Widget_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * Widget should rewrite and use only those which  it needs.
     *
     * @var array
     */
    protected $_a_component_names = [];
    /**
     * If active load components
     * Widgets loads active view components
     *
     * @var array
     */
    protected $_bl_load_components = false;
    /**
     * Sets self::$_aCollectedComponentNames to null, as views and widgets
     * controllers loads different components and calls parent::init()
     */
    public function init(): void
    {
        self::$_a_collected_component_names = null;
        foreach ($this->_a_component_names as $s_component_name => $s_comp_cache) {
            $o_act_top_view = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view();
            if ($o_act_top_view) {
                $this->_oa_components[$s_component_name] = $o_act_top_view->get_component($s_component_name);
                if (!isset($this->_oa_components[$s_component_name])) {
                    $this->_bl_load_components = true;
                    break;
                } else {
                    $this->_oa_components[$s_component_name]->set_parent($this);
                }
            }
        }
        parent::init();
    }
    /**
     * In widgets we do not need to parse seo and do any work related to that
     * Shop main control is responsible for that, and that has to be done once
     */
    protected function process_request()
    {
    }
}