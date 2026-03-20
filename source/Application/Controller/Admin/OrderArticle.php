<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Admin order article manager.
 * Collects order articles information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Articles.
 */
class Order_Article extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Product which was currently found by search
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_search_product;
    /**
     * Product list:
     *  - if product is not variant - list contains only product which was found by search;
     *  - if product is variant - list consist with variant paret and its variants
     *
     * @var \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected $_o_search_product_list;
    /**
     * Product found by search. If product is variant - it keeps parent object
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_main_search_product;
    /**
     * Active order object
     *
     * @var \OxidEsales\Eshop\Application\Model\Order
     */
    protected $_o_edit_object;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        if ($o_order = $this->get_edit_object()) {
            $this->_a_view_data['edit'] = $o_order;
            $this->_a_view_data['aProductVats'] = $o_order->get_product_vats(true);
        }
        return 'order_article';
    }
    /**
     * Returns editable order object
     *
     * @return \OxidEsales\Eshop\Application\Model\Order
     */
    public function get_edit_object()
    {
        $sox_id = $this->get_edit_object_id();
        if ($this->_o_edit_object === null && isset($sox_id) && $sox_id != '-1') {
            $this->_o_edit_object = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            $this->_o_edit_object->load($sox_id);
        }
        return $this->_o_edit_object;
    }
    /**
     * Returns user written product number
     *
     * @return string
     */
    public function get_search_product_art_nr()
    {
        return Registry::get_request()->get_request_escaped_parameter('sSearchArtNum');
    }
    /**
     * If possible returns searched/found oxarticle object
     *
     * @return \OxidEsales\Eshop\Application\Model\Article|false
     */
    public function get_search_product()
    {
        if ($this->_o_search_product === null) {
            $this->_o_search_product = false;
            $s_search_art_num = $this->get_search_product_art_nr();
            foreach ($this->get_product_list() as $o_product) {
                if ($o_product->oxarticles__oxartnum->value == $s_search_art_num) {
                    $this->_o_search_product = $o_product;
                    break;
                }
            }
        }
        return $this->_o_search_product;
    }
    /**
     * Returns product found by search. If product is variant - returns parent object
     *
     * @return object
     */
    public function get_main_product()
    {
        if ($this->_o_main_search_product === null && $s_art_num = $this->get_search_product_art_nr()) {
            $this->_o_main_search_product = false;
            $database = Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $table = $table_view_name_generator->get_view_name('oxarticles');
            $products = $database->select(sprintf('select oxid, oxparentid from %s where oxartnum = :oxartnum limit 1', $table), ['oxartnum' => $s_art_num]);
            if ($products != false && $products->count() > 0) {
                $article_id = $products->fields['OXPARENTID'] ?: $products->fields['OXID'];
                $product = ox_new(Article::class);
                if ($product->load($article_id)) {
                    $this->_o_main_search_product = $product;
                }
            }
        }
        return $this->_o_main_search_product;
    }
    /**
     * Returns product list containing searchable product or its parent and its variants
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_product_list()
    {
        if ($this->_o_search_product_list === null) {
            $this->_o_search_product_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            // main search product is found?
            if ($o_main_search_product = $this->get_main_product()) {
                // storing self to first list position
                $this->_o_search_product_list->offsetSet($o_main_search_product->get_id(), $o_main_search_product);
                // adding variants..
                foreach ($o_main_search_product->get_variants() as $o_variant) {
                    $this->_o_search_product_list->offsetSet($o_variant->get_id(), $o_variant);
                }
            }
        }
        return $this->_o_search_product_list;
    }
    /**
     * Adds article to order list.
     */
    public function add_this_article(): void
    {
        $s_oxid = Registry::get_request()->get_request_escaped_parameter('aid');
        $d_amount = Registry::get_request()->get_request_escaped_parameter('am');
        $o_product = ox_new(Article::class);
        if ($s_oxid && $d_amount && $o_product->load($s_oxid)) {
            $s_order_id = $this->get_edit_object_id();
            $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            if ($s_order_id && $o_order->load($s_order_id)) {
                $o_order_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_Article::class);
                $o_order_article->oxorderarticles__oxartid = new Field($o_product->get_id());
                $o_order_article->oxorderarticles__oxartnum = new Field($o_product->oxarticles__oxartnum->value);
                $o_order_article->oxorderarticles__oxamount = new Field($d_amount);
                $o_order_article->oxorderarticles__oxselvariant = new Field(Registry::get_request()->get_request_escaped_parameter('sel'));
                $o_order->recalculate_order([$o_order_article]);
            }
        }
    }
    /**
     * Removes article from order list.
     */
    public function delete_this_article(): void
    {
        // get article id
        $s_order_art_id = Registry::get_request()->get_request_escaped_parameter('sArtID');
        $s_order_id = $this->get_edit_object_id();
        $o_order_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_Article::class);
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        // order and order article exits?
        if ($o_order_article->load($s_order_art_id) && $o_order->load($s_order_id)) {
            // deleting record
            $o_order_article->delete();
            // recalculating order
            $o_order->recalculate_order();
        }
    }
    /**
     * Cancels order item
     */
    public function storno(): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_order_art_id = Registry::get_request()->get_request_escaped_parameter('sArtID');
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_Article::class);
        $o_article->load($s_order_art_id);
        if ($o_article->oxorderarticles__oxstorno->value == 1) {
            $o_article->oxorderarticles__oxstorno->set_value(0);
            $s_stock_sign = -1;
        } else {
            $o_article->oxorderarticles__oxstorno->set_value(1);
            $s_stock_sign = 1;
        }
        // stock information
        if ($my_config->get_config_param('blUseStock')) {
            $o_article->update_article_stock($o_article->oxorderarticles__oxamount->value * $s_stock_sign, $my_config->get_config_param('blAllowNegativeStock'));
        }
        $o_db = Database_Provider::get_db();
        $s_q = 'update oxorderarticles set oxstorno = :oxstorno where oxid = :oxid';
        $o_db->execute($s_q, ['oxstorno' => $o_article->oxorderarticles__oxstorno->value, 'oxid' => $s_order_art_id]);
        //get article id
        $s_q = 'select oxartid from oxorderarticles where oxid = :oxid';
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        if ($s_art_id = Database_Provider::get_master()->get_one($s_q, ['oxid' => $s_order_art_id])) {
            $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            if ($o_order->load($this->get_edit_object_id())) {
                $o_order->recalculate_order();
            }
        }
    }
    /**
     * Updates order articles stock and recalculates order
     */
    public function update_order(): void
    {
        $a_order_articles = Registry::get_request()->get_request_escaped_parameter('aOrderArticles');
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if (is_array($a_order_articles) && $o_order->load($this->get_edit_object_id())) {
            $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            $o_order_articles = $o_order->get_order_articles(true);
            $bl_use_stock = $my_config->get_config_param('blUseStock');
            foreach ($o_order_articles as $o_order_article) {
                $s_item_id = $o_order_article->get_id();
                if (isset($a_order_articles[$s_item_id])) {
                    // update stock
                    if ($bl_use_stock) {
                        $o_order_article->set_new_amount($a_order_articles[$s_item_id]['oxamount']);
                    } else {
                        $o_order_article->assign($a_order_articles[$s_item_id]);
                        $o_order_article->save();
                    }
                }
            }
            // recalculating order
            $o_order->recalculate_order();
        }
    }
}