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
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class Recommendation extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_cur' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/sidebar/recommendation';
    /**
     * Returns similar recommendation list.
     *
     * @return array
     */
    public function get_similar_recomm_lists()
    {
        $a_article_ids = $this->get_view_parameter('aArticleIds');
        $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Recommendation_List::class);
        return $o_recomm_list->get_recomm_lists_by_ids($a_article_ids);
    }
    /**
     * Return recomm list object.
     *
     * @return object
     */
    public function get_recomm_list()
    {
        return ox_new(\Oxid_Esales\Eshop\Application\Controller\Recomm_List_Controller::class);
    }
}