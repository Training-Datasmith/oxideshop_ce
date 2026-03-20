<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Recomendation list.
 * Forms recomendation list.
 */
class Service_Menu extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_user' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/header/servicemenu';
    /**
     * Template variable getter. Returns comparison article list.
     *
     * @param bool $blJson return json encoded array
     *
     * @return array
     */
    public function get_compare_items($bl_json = false)
    {
        $o_compare = ox_new(\Oxid_Esales\Eshop\Application\Controller\Compare_Controller::class);
        $a_compare_items = $o_compare->get_compare_items();
        if ($bl_json) {
            return json_encode($a_compare_items);
        }
        return $a_compare_items;
    }
}