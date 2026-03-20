<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Ox_U_Base;
/**
 * Handles adding article to recommendation list process.
 * Due to possibility of external modules we recommned to extend the vews from oxUBase view.
 * However expreimentally we extend RecommAdd from Details view here.
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class Recommendation_Add_Controller extends \Oxid_Esales\Eshop\Application\Controller\Article_Details_Controller
{
    /**
     * Template name
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/recommendationadd';
    /**
     * User recommendation lists
     *
     * @var array
     */
    protected $_a_user_recomm_list;
    /**
     * Renders the view
     *
     * @return string
     */
    public function render()
    {
        \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::render();
        return $this->_s_this_template;
    }
    /**
     * Returns user recommlists
     *
     * @return array
     */
    public function get_recomm_lists()
    {
        if ($this->_a_user_recomm_list === null) {
            $o_user = $this->get_user();
            if ($o_user) {
                $this->_a_user_recomm_list = $o_user->get_user_recomm_lists();
            }
        }
        return $this->_a_user_recomm_list;
    }
    /**
     * Returns the title of the product added to the recommendation list.
     *
     * @return string
     */
    public function get_title()
    {
        $o_product = $this->get_product();
        return $o_product->oxarticles__oxtitle->value . ' ' . $o_product->oxarticles__oxvarselect->value;
    }
}