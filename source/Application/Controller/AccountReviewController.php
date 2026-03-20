<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Application\Model\Review;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Request;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge\User_Rating_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge\User_Review_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
/**
 * Class AccountReviewController
 *
 * @package OxidEsales\EshopCommunity\Application\Controller
 */
class Account_Review_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    protected $items_per_page = 10;
    protected $_s_this_template = 'page/account/reviews';
    /**
     * Redirect to My Account, if validation does not pass.
     */
    public function init(): void
    {
        if (!$this->is_user_allowed_to_manage_own_reviews() || !$this->get_user()) {
            $this->redirect_to_account_dashboard();
        }
        parent::init();
    }
    /**
     * Returns Review List
     *
     * @return array
     */
    public function get_review_list()
    {
        $current_page = $this->get_act_page();
        $items_per_page = $this->get_items_per_page();
        $offset = $current_page * $items_per_page;
        $user_id = $this->get_user()->get_id();
        $review_model = ox_new(Review::class);
        $review_and_rating_list = $review_model->get_review_and_rating_list_by_user_id($user_id);
        return $this->get_paginated_review_and_rating_list($review_and_rating_list, $items_per_page, $offset);
    }
    /**
     * Delete review and rating, which belongs to the active user.
     */
    public function delete_review_and_rating(): void
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        if ($session->check_session_challenge()) {
            try {
                $this->delete_review();
                $this->delete_rating();
            } catch (Entry_Does_Not_Exist_Dao_Exception) {
                //if user reloads the page after deletion
            }
        }
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        return [['title' => $this->get_translated_string('MY_ACCOUNT'), 'link' => $this->get_my_account_page_url()], ['title' => $this->get_translated_string('MY_REVIEWS'), 'link' => $this->get_link()]];
    }
    /**
     * Generates the pagination.
     *
     * @return \stdClass
     */
    public function get_page_navigation()
    {
        $this->_i_cnt_pages = $this->get_pages_count();
        $this->_o_page_navigation = $this->generate_page_navigation();
        return $this->_o_page_navigation;
    }
    /**
     * Return how many items will be displayed per page.
     *
     * @return int
     */
    public function get_items_per_page()
    {
        return $this->items_per_page;
    }
    /**
     * Get actual page number.
     *
     * @return int
     */
    public function get_act_page()
    {
        $last_page = $this->get_pages_count();
        $current_page = parent::get_act_page();
        if ($current_page >= $last_page) {
            return $last_page - 1;
        }
        return $current_page;
    }
    /**
     * Deletes Review.
     */
    private function delete_review(): void
    {
        $user_id = $this->get_user()->get_id();
        $review_id = $this->get_review_id_from_request();
        if ($review_id) {
            Container_Facade::get(User_Review_Bridge_Interface::class)->delete_review($user_id, $review_id);
        }
    }
    /**
     * Deletes Rating.
     */
    private function delete_rating(): void
    {
        $user_id = $this->get_user()->get_id();
        $rating_id = $this->get_rating_id_from_request();
        if ($rating_id) {
            Container_Facade::get(User_Rating_Bridge_Interface::class)->delete_rating($user_id, $rating_id);
        }
    }
    /**
     * Retrieve the Review id from the request
     *
     * @return string
     */
    private function get_review_id_from_request()
    {
        $request = ox_new(Request::class);
        return $request->get_request_escaped_parameter('reviewId');
    }
    /**
     * Retrieve the Rating id from the request
     *
     * @return string
     */
    private function get_rating_id_from_request()
    {
        $request = ox_new(Request::class);
        return $request->get_request_escaped_parameter('ratingId');
    }
    /**
     * Redirect to My Account dashboard
     */
    private function redirect_to_account_dashboard(): void
    {
        Registry::get_utils()->redirect($this->get_my_account_page_url(), true, 302);
    }
    /**
     * Returns pages count.
     */
    private function get_pages_count(): float
    {
        return ceil($this->get_review_and_rating_items_count() / $this->get_items_per_page());
    }
    /**
     * Returns My Account page url.
     *
     * @return string
     */
    private function get_my_account_page_url()
    {
        $self_link = $this->get_view_config()->get_self_link();
        return Registry::get_seo_encoder()->get_static_url($self_link . 'cl=account');
    }
    /**
     * Returns translated string.
     *
     *
     * @return string
     */
    private function get_translated_string(string $string)
    {
        $language_id = Registry::get_lang()->get_base_language();
        return Registry::get_lang()->translate_string($string, $language_id, false);
    }
    /**
     * Paginate ReviewAndRating list.
     *
     * @param array $reviewAndRatingList
     * @param int   $itemsCount
     * @param int   $offset
     */
    private function get_paginated_review_and_rating_list($review_and_rating_list, $items_count, int|float $offset): array
    {
        return array_slice($review_and_rating_list, $offset, $items_count, true);
    }
}