<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Comparing Products.
 * Takes a few products and show attribute values to compare them.
 */
class Compare_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Number of possible compare pages.
     *
     * @var integer
     */
    protected $_i_cnt_pages = 1;
    /**
     * Number of user's orders.
     *
     * @var integer
     */
    protected $_i_order_cnt;
    /**
     * Number of articles per page.
     *
     * @var integer
     */
    protected $_i_articles_per_page = 3;
    /**
     * Number of user's orders.
     *
     * @var integer
     */
    protected $_i_comp_items_cnt;
    /**
     * Items which are currently to show in comparison.
     *
     * @var array
     */
    protected $_a_comp_items;
    /**
     * Article list in comparison.
     *
     * @var object
     */
    protected $_o_art_list;
    /**
     * Article attribute list in comparison.
     *
     * @var object
     */
    protected $_o_attribute_list;
    /**
     * Recomendation list
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var object
     */
    protected $_o_recomm_list;
    /**
     * Page navigation
     *
     * @var object
     */
    protected $_o_page_navigation;
    /**
     * Sign if to load and show bargain action
     *
     * @var bool
     */
    protected $_bl_bargain_action = true;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/compare/compare';
    /**
     * Array of id to form recommendation list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @var array
     */
    protected $_a_similar_recomm_list_ids;
    /**
     * moves current article to the left in compare items array
     */
    public function move_left(): void
    {
        $s_article_id = Registry::get_request()->get_request_escaped_parameter('aid');
        if ($s_article_id && $a_items = $this->get_compare_items()) {
            $s_prev_article_id = null;
            $bl_found = false;
            foreach ($a_items as $s_oxid => $s_val) {
                if ($s_oxid == $s_article_id) {
                    $bl_found = true;
                }
                if (!$bl_found) {
                    $s_prev_article_id = $s_oxid;
                }
            }
            if ($s_prev_article_id) {
                $a_new_items = [];
                foreach ($a_items as $s_oxid => $s_val) {
                    if ($s_oxid == $s_prev_article_id) {
                        $a_new_items[$s_article_id] = true;
                    } elseif ($s_oxid == $s_article_id) {
                        $a_new_items[$s_prev_article_id] = true;
                    } else {
                        $a_new_items[$s_oxid] = true;
                    }
                }
                $this->set_compare_items($a_new_items);
            }
        }
    }
    /**
     * moves current article to the right in compare items array
     */
    public function move_right(): void
    {
        $s_article_id = Registry::get_request()->get_request_escaped_parameter('aid');
        if ($s_article_id && $a_items = $this->get_compare_items()) {
            $s_next_article_id = 0;
            $bl_found = false;
            foreach ($a_items as $s_oxid => $s_val) {
                if ($bl_found) {
                    $s_next_article_id = $s_oxid;
                    $bl_found = false;
                }
                if ($s_oxid == $s_article_id) {
                    $bl_found = true;
                }
            }
            if ($s_next_article_id) {
                $a_new_items = [];
                foreach ($a_items as $s_oxid => $s_val) {
                    if ($s_oxid == $s_article_id) {
                        $a_new_items[$s_next_article_id] = true;
                    } elseif ($s_oxid == $s_next_article_id) {
                        $a_new_items[$s_article_id] = true;
                    } else {
                        $a_new_items[$s_oxid] = true;
                    }
                }
                $this->set_compare_items($a_new_items);
            }
        }
    }
    /**
     * changes default template for compare in popup
     */
    public function in_popup(): void
    {
        $this->_s_this_template = 'compare_popup';
        $this->_i_articles_per_page = -1;
    }
    /**
     * Articlelist count in comparison setter
     *
     * @param integer $iCount compare items count
     */
    public function set_compare_items_cnt($i_count): void
    {
        $this->_i_comp_items_cnt = $i_count;
    }
    /**
     * Template variable getter. Returns article list count in comparison
     *
     * @return integer
     */
    public function get_compare_items_cnt()
    {
        if ($this->_i_comp_items_cnt === null) {
            $this->_i_comp_items_cnt = 0;
            if ($a_items = $this->get_compare_items()) {
                $this->_i_comp_items_cnt = count($a_items);
            }
        }
        return $this->_i_comp_items_cnt;
    }
    /**
     * Compare item $_aCompItems getter
     */
    public function get_compare_items()
    {
        if ($this->_a_comp_items === null) {
            $a_items = Registry::get_session()->get_variable('aFiltcompproducts');
            if (is_array($a_items) && count($a_items)) {
                $this->_a_comp_items = $a_items;
            }
        }
        return $this->_a_comp_items;
    }
    /**
     * Compare item $_aCompItems setter
     *
     * @param array $aItems compare items i new order
     */
    public function set_compare_items($a_items): void
    {
        $this->_a_comp_items = $a_items;
        Registry::get_session()->set_variable('aFiltcompproducts', $a_items);
    }
    /**
     *  $_iArticlesPerPage setter
     *
     * @param int $iNumber article count in compare page
     */
    protected function set_articles_per_page($i_number)
    {
        $this->_i_articles_per_page = $i_number;
    }
    /**
     *  turn off paging
     */
    public function set_no_paging(): void
    {
        $this->set_articles_per_page(0);
    }
    /**
     * Template variable getter. Returns comparison's article
     * list in order per page
     *
     * @return object
     */
    public function get_comp_art_list()
    {
        if ($this->_o_art_list === null) {
            if ($a_items = $this->get_compare_items()) {
                // counts how many pages
                $o_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
                $o_list->load_ids(array_keys($a_items));
                // cut page articles
                if ($this->_i_articles_per_page > 0) {
                    $this->_i_cnt_pages = ceil($o_list->count() / $this->_i_articles_per_page);
                    $a_items = $this->remove_articles_from_page($a_items, $o_list);
                }
                $this->_o_art_list = $this->change_art_list_order($a_items, $o_list);
            }
        }
        return $this->_o_art_list;
    }
    /**
     * Template variable getter. Returns attribute list
     *
     * @return object
     */
    public function get_attribute_list()
    {
        if ($this->_o_attribute_list === null) {
            $this->_o_attribute_list = false;
            if ($o_art_list = $this->get_comp_art_list()) {
                $a_product_ids = array_keys($o_art_list);
                foreach ($o_art_list as $o_article) {
                    if ($o_article->get_parent_id()) {
                        $a_product_ids[] = $o_article->get_parent_id();
                    }
                }
                $o_attribute_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute_List::class);
                $this->_o_attribute_list = $o_attribute_list->load_attributes_by_ids($a_product_ids);
            }
        }
        return $this->_o_attribute_list;
    }
    /**
     * Return array of id to form recommend list.
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return array
     */
    public function get_similar_recomm_list_ids()
    {
        if ($this->_a_similar_recomm_list_ids === null) {
            $this->_a_similar_recomm_list_ids = false;
            if ($o_art_list = $this->get_comp_art_list()) {
                $this->_a_similar_recomm_list_ids = array_keys($o_art_list);
            }
        }
        return $this->_a_similar_recomm_list_ids;
    }
    /**
     * Template variable getter. Returns page navigation
     *
     * @return object
     */
    public function get_page_navigation()
    {
        if ($this->_o_page_navigation === null) {
            $this->_o_page_navigation = false;
            $this->_o_page_navigation = $this->generate_page_navigation();
        }
        return $this->_o_page_navigation;
    }
    /**
     * Cuts page articles
     *
     * @param array  $aItems article array
     * @param object $oList  article list array
     *
     * @return array $aNewItems
     */
    protected function remove_articles_from_page($a_items, $o_list)
    {
        //#1106S $aItems changed to $oList.
        //2006-08-10 Alfonsas, compare arrows fixed, array position is very important here, preserve it.
        $a_list_keys = $o_list->array_keys();
        $a_item_keys = array_keys($a_items);
        $a_keys = array_intersect($a_item_keys, $a_list_keys);
        $a_new_items = [];
        $i_act_page = $this->get_act_page();
        $max_page_index = $this->_i_articles_per_page * $i_act_page + $this->_i_articles_per_page;
        for ($i = $this->_i_articles_per_page * $i_act_page; $i < $max_page_index; $i++) {
            if (!isset($a_keys[$i])) {
                break;
            }
            $a_new_items[$a_keys[$i]] =& $a_items[$a_keys[$i]];
        }
        return $a_new_items;
    }
    /**
     * Changes order of list elements
     *
     * @param array  $aItems article array
     * @param object $oList  article list array
     *
     * @return array $oNewList
     */
    protected function change_art_list_order($a_items, $o_list)
    {
        // #777C changing order of list elements, according to $aItems
        $o_new_list = [];
        $i_cnt = 0;
        $i_act_page = $this->get_act_page();
        foreach ($a_items as $s_oxid => $s_val) {
            //#4391T, skipping non loaded products
            if (!isset($o_list[$s_oxid])) {
                continue;
            }
            $i_cnt++;
            $o_new_list[$s_oxid] = $o_list[$s_oxid];
            // hide arrow if article is first in the list
            $o_new_list[$s_oxid]->hide_prev = false;
            if ($i_act_page == 0 && $i_cnt == 1) {
                $o_new_list[$s_oxid]->hide_prev = true;
            }
            // hide arrow if article is last in the list
            $o_new_list[$s_oxid]->hide_next = false;
            if ($i_act_page + 1 == $this->_i_cnt_pages && $i_cnt == count($a_items)) {
                $o_new_list[$s_oxid]->hide_next = true;
            }
        }
        return $o_new_list;
    }
    /**
     * changes default template for compare in popup
     */
    public function get_order_cnt()
    {
        if ($this->_i_order_cnt === null) {
            $this->_i_order_cnt = 0;
            if ($o_user = $this->get_user()) {
                $this->_i_order_cnt = $o_user->get_order_count();
            }
        }
        return $this->_i_order_cnt;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $a_path = [];
        $a_path['title'] = Registry::get_lang()->translate_string('MY_ACCOUNT', Registry::get_lang()->get_base_language(), false);
        $a_path['link'] = Registry::get_seo_encoder()->get_static_url($this->get_view_config()->get_self_link() . 'cl=account');
        $a_paths[] = $a_path;
        $a_path['title'] = Registry::get_lang()->translate_string('PRODUCT_COMPARISON', Registry::get_lang()->get_base_language(), false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}