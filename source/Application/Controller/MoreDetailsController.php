<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Article images gallery popup window.
 * If chosen article has more pictures there is ability to create
 * gallery of pictures.
 */
class More_Details_Controller extends \Oxid_Esales\Eshop\Application\Controller\Article_Details_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'moredetails';
    /**
     * Current article id
     *
     * @var string
     */
    protected $_s_product_id;
    /**
     * Active picture id
     *
     * @var string
     */
    protected $_s_act_pic_id;
    /**
     * Article zoom pictures
     *
     * @var array
     */
    protected $_a_art_zoom_pics;
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * Template variable getter. Returns current product id
     *
     * @return string
     */
    public function get_product_id()
    {
        if ($this->_s_product_id === null) {
            $this->_s_product_id = $this->get_product()->get_id();
        }
        return $this->_s_product_id;
    }
    /**
     * Template variable getter. Returns active picture id
     *
     * @return string
     */
    public function get_act_picture_id()
    {
        if ($this->_s_act_pic_id === null) {
            $this->_s_act_pic_id = false;
            $a_pic_gallery = $this->get_product()->get_picture_gallery();
            if ($a_pic_gallery['ZoomPic']) {
                $s_act_pic_id = Registry::get_request()->get_request_escaped_parameter('actpicid');
                $this->_s_act_pic_id = $s_act_pic_id ?: 1;
            }
        }
        return $this->_s_act_pic_id;
    }
    /**
     * Template variable getter. Returns article zoom pictures
     *
     * @return array
     */
    public function get_art_zoom_pics()
    {
        if ($this->_a_art_zoom_pics === null) {
            $this->_a_art_zoom_pics = false;
            //Get picture gallery
            $a_pic_gallery = $this->get_product()->get_picture_gallery();
            $bl_art_pic = $a_pic_gallery['ZoomPic'];
            $a_art_pics = $a_pic_gallery['ZoomPics'];
            if ($bl_art_pic) {
                $this->_a_art_zoom_pics = $a_art_pics;
            }
        }
        return $this->_a_art_zoom_pics;
    }
    /**
     * Template variable getter. Returns active product
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_product()
    {
        if ($this->_o_product === null) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->load(Registry::get_request()->get_request_escaped_parameter('anid'));
            $this->_o_product = $o_article;
        }
        return $this->_o_product;
    }
}