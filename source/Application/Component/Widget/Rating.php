<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Product Ratings widget.
 * Forms product ratings.
 */
class Rating extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
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
    protected $_s_this_template = 'widget/reviews/rating';
    /**
     * Rating value
     *
     * @var double
     */
    protected $_d_rating_value;
    /**
     * Rating count
     *
     * @var integer
     */
    protected $_i_rating_cnt;
    /**
     * Executes parent::render().
     * Returns name of template file to render.
     *
     * @return string current template file name
     */
    public function render()
    {
        parent::render();
        return $this->_s_this_template;
    }
    /**
     * Template variable getter. Returns rating value
     *
     * @return double
     */
    public function get_rating_value()
    {
        if ($this->_d_rating_value === null) {
            $this->_d_rating_value = 0.0;
            $d_value = $this->get_view_parameter('dRatingValue');
            if ($d_value) {
                $this->_d_rating_value = round($d_value, 1);
            }
        }
        return (float) $this->_d_rating_value;
    }
    /**
     * Template variable getter. Returns rating count
     *
     * @return integer
     */
    public function get_rating_count()
    {
        return $d_count = $this->get_view_parameter('dRatingCount');
    }
    /**
     * Template variable getter. Returns rating url
     *
     * @return string
     */
    public function get_rate_url()
    {
        return $this->get_view_parameter('sRateUrl');
    }
    /**
     * Template variable getter. Returns rating count
     *
     * @return integer
     */
    public function can_rate()
    {
        return $this->get_view_parameter('blCanRate');
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
}