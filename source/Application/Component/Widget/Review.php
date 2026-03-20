<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Product reviews widget
 */
class Review extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
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
    protected $_s_this_template = 'widget/reviews/reviews';
    /**
     * Executes parent::render().
     * Returns name of template file to render.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        parent::render();
        return $this->_s_this_template;
    }
    /**
     * Template variable getter. Returns review type
     *
     * @return string
     */
    public function get_review_type()
    {
        return strtolower($this->get_view_parameter('type'));
    }
    /**
     * Template variable getter. Returns article id
     *
     * @return string
     */
    public function get_article_id()
    {
        return $this->get_view_parameter('aid');
    }
    /**
     * Template variable getter. Returns article nid
     *
     * @return string
     */
    public function get_article_n_id()
    {
        return $this->get_view_parameter('anid');
    }
    /**
     * Template variable getter. Returns recommlist id
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return string
     */
    public function get_recomm_list_id()
    {
        return $this->get_view_parameter('recommid');
    }
    /**
     * Template variable getter. Returns whether user can rate
     *
     * @return string
     */
    public function can_rate()
    {
        return $this->get_view_parameter('canrate');
    }
    /**
     * Template variable getter. Returns review user id
     *
     * @return string
     */
    public function get_review_user_hash()
    {
        return $this->get_view_parameter('reviewuserhash');
    }
    /**
     * Template variable getter. Returns active object's reviews from parent class
     *
     * @return array
     */
    public function get_reviews()
    {
        $o_review = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view();
        return $o_review->get_reviews();
    }
}