<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Actions widget.
 * Access actions in tpl.
 */
class Actions extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/product/action';
    /**
     * Are actions on
     *
     * @var bool
     */
    protected $_bl_load_actions;
    /**
     * Returns article list with action articles
     *
     * @return object
     */
    public function get_action()
    {
        $action_id = $this->get_view_parameter('action');
        if ($action_id && $this->get_load_actions_param()) {
            $art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $art_list->load_action_articles($action_id);
            if ($art_list->count()) {
                return $art_list;
            }
        }
    }
    /**
     * Returns if actions are ON
     *
     * @return string
     */
    protected function get_load_actions_param()
    {
        $this->_bl_load_actions = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadAktion');
        return $this->_bl_load_actions;
    }
    /**
     * Returns action name
     *
     * @return string
     */
    public function get_action_name()
    {
        $action_id = $this->get_view_parameter('action');
        $action = ox_new(\Oxid_Esales\Eshop\Application\Model\Actions::class);
        if ($action->load($action_id)) {
            return $action->oxactions__oxtitle->value;
        }
    }
    /**
     * Returns products list type
     *
     * @return string
     */
    public function get_list_type()
    {
        return $this->get_view_parameter('listtype');
    }
}