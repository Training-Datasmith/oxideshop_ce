<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article review manager.
 * Collects customer review about article data. There ir possibility to update
 * review text or delete it.
 * Admin Menu: Manage Products -> Articles -> Review.
 */
class Article_Review extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Loads selected article review information, returns name of template
     * file "article_review".
     *
     * @return string
     */
    public function render()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        parent::render();
        $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $this->_a_view_data['edit'] = $article;
        $article_id = $this->get_edit_object_id();
        $review_id = Registry::get_request()->get_request_escaped_parameter('rev_oxid');
        if (isset($article_id) && $article_id != '-1') {
            // load object
            $article->load($article_id);
            if ($article->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            $review_list = $this->get_review_list($article);
            foreach ($review_list as $review) {
                if ($review->oxreviews__oxid->value == $review_id) {
                    $review->selected = 1;
                    break;
                }
            }
            $this->_a_view_data['allreviews'] = $review_list;
            $this->_a_view_data['editlanguage'] = $this->_i_edit_lang;
            if (isset($review_id)) {
                $review_for_editing = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
                $review_for_editing->load($review_id);
                $this->_a_view_data['editreview'] = $review_for_editing;
                $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                $user->load($review_for_editing->oxreviews__oxuserid->value);
                $this->_a_view_data['user'] = $user;
            }
            //show "active" checkbox if moderating is active
            $this->_a_view_data['blShowActBox'] = $config->get_config_param('blGBModerate');
        }
        return 'article_review';
    }
    /**
     * returns reviews list for article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article Article object
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    protected function get_review_list($article)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $query = 'select oxreviews.* from oxreviews
                     where oxreviews.OXOBJECTID = ' . $database->quote($article->oxarticles__oxid->value) . "\n                     and oxreviews.oxtype = 'oxarticle'";
        $variant_list = $article->get_variants();
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowVariantReviews') && count($variant_list)) {
            // verifying rights
            foreach ($variant_list as $variant) {
                $query .= 'or oxreviews.oxobjectid = ' . $database->quote($variant->oxarticles__oxid->value) . ' ';
            }
        }
        //$sSelect .= "and oxreviews.oxtext".\OxidEsales\Eshop\Core\Registry::getLang()->getLanguageTag($this->_iEditLang)." != ''";
        $query .= "and oxreviews.oxlang = '" . $this->_i_edit_lang . "'";
        $query .= "and oxreviews.oxtext != '' ";
        // all reviews
        $review_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $review_list->init('oxreview');
        $review_list->select_string($query);
        return $review_list;
    }
    /**
     * Saves article review information changes.
     */
    public function save(): void
    {
        parent::save();
        $parameters = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blGBModerate') && !isset($parameters['oxreviews__oxactive'])) {
            $parameters['oxreviews__oxactive'] = 0;
        }
        $review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
        $review->load(Registry::get_request()->get_request_escaped_parameter('rev_oxid'));
        $review->assign($parameters);
        $review->save();
    }
    /**
     * Deletes selected article review information.
     */
    public function delete(): void
    {
        $this->reset_content_cache();
        $review_id = Registry::get_request()->get_request_escaped_parameter('rev_oxid');
        $review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
        $review->load($review_id);
        $review->delete();
        // recalculating article average rating
        $rating = ox_new(\Oxid_Esales\Eshop\Application\Model\Rating::class);
        $article_id = $this->get_edit_object_id();
        $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $article->load($article_id);
        //switch database connection to master for the following read/write access.
        \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        $article->set_rating_average($rating->get_rating_average($article_id, 'oxarticle'));
        $article->set_rating_count($rating->get_rating_count($article_id, 'oxarticle'));
        $article->save();
    }
}