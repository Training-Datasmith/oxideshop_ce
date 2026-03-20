<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Seo encoder for articles
 */
class Seo_Encoder_Article extends \Oxid_Esales\Eshop\Core\Seo_Encoder
{
    /**
     * Product parent title cache
     *
     * @var array
     */
    protected static $_a_title_cache = [];
    /**
     * Returns target "extension" (.html)
     *
     * @return string
     */
    protected function get_url_extension()
    {
        return '.html';
    }
    /**
     * Checks if current article is in same language as preferred (language id passed by param).
     * In case languages are not the same - reloads article object in different language
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article to check language
     * @param int                                         $iLang    user defined language id
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function get_product_for_lang($o_article, $i_lang)
    {
        if (isset($i_lang) && $i_lang != $o_article->get_language()) {
            $s_id = $o_article->get_id();
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->set_skip_assign(true);
            $o_article->load_in_lang($i_lang, $s_id);
        }
        return $o_article;
    }
    /**
     * Returns SEO uri for passed article and active tag
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @param int                                         $iLang    language id
     *
     * @return string
     */
    public function get_article_recomm_uri($o_article, $i_lang)
    {
        $s_seo_uri = null;
        if ($o_recomm = $this->_get_recomm($o_article, $i_lang)) {
            //load details link from DB
            if (!$s_seo_uri = $this->load_from_db('oxarticle', $o_article->get_id(), $i_lang, null, $o_recomm->get_id(), true)) {
                $o_article = $this->get_product_for_lang($o_article, $i_lang);
                // create title part for uri
                $s_title = $this->prepare_article_title($o_article);
                // create uri for all categories
                $s_seo_uri = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Recomm::class)->get_recomm_uri($o_recomm, $i_lang);
                $s_seo_uri = $this->process_seo_url($s_seo_uri . $s_title, $o_article->get_id(), $i_lang);
                $a_std_params = ['recommid' => $o_recomm->get_id(), 'listtype' => $this->get_list_type()];
                $this->save_to_db('oxarticle', $o_article->get_id(), \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->append_url($o_article->get_base_std_link($i_lang), $a_std_params), $s_seo_uri, $i_lang, null, 0, $o_recomm->get_id());
            }
        }
        return $s_seo_uri;
    }
    /**
     * Returns active recommendation list object if available
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
     *
     * @return \OxidEsales\Eshop\Application\Model\RecommendationList|null
     */
    protected function _get_recomm($o_article, $i_lang)
    {
        $o_list = null;
        $o_view = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view();
        if ($o_view instanceof \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller) {
            return $o_view->get_active_recomm_list();
        }
        return $o_list;
    }
    /**
     * Returns active list type
     *
     * @return string
     */
    protected function get_list_type()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view()->get_list_type();
    }
    /**
     * create article uri for given category and save it
     *
     * @param \OxidEsales\Eshop\Application\Model\Article  $oArticle  article object
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory category object
     * @param int                                          $iLang     language to generate uri for
     *
     * @return string
     */
    protected function create_article_category_uri($o_article, $o_category, $i_lang)
    {
        start_profile(__FUNCTION__);
        $o_article = $this->get_product_for_lang($o_article, $i_lang);
        // create title part for uri
        $s_title = $this->prepare_article_title($o_article);
        // writing category path
        $s_seo_uri = $this->process_seo_url(\Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class)->get_category_uri($o_category, $i_lang) . $s_title, $o_article->get_id(), $i_lang);
        $s_cat_id = $o_category->get_id();
        $this->save_to_db('oxarticle', $o_article->get_id(), \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->append_url($o_article->get_base_std_link($i_lang), ['cnid' => $s_cat_id]), $s_seo_uri, $i_lang, null, 0, $s_cat_id);
        stop_profile(__FUNCTION__);
        return $s_seo_uri;
    }
    /**
     * Returns SEO uri for passed article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle     article object
     * @param int                                         $iLang        language id
     * @param bool                                        $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function get_article_uri($o_article, $i_lang, $bl_regenerate = false)
    {
        start_profile(__FUNCTION__);
        $s_act_cat_id = '';
        $o_act_cat = $this->get_category($o_article, $i_lang);
        if ($o_act_cat instanceof \Oxid_Esales\Eshop\Application\Model\Category) {
            $s_act_cat_id = $o_act_cat->get_id();
        } elseif ($o_act_cat = $this->get_main_category($o_article)) {
            $s_act_cat_id = $o_act_cat->get_id();
        }
        //load details link from DB
        if ($bl_regenerate || !$s_seo_uri = $this->load_from_db('oxarticle', $o_article->get_id(), $i_lang, null, $s_act_cat_id, true)) {
            if ($o_act_cat) {
                $bl_in_cat = $o_act_cat->is_price_category() ? $o_article->in_price_category($s_act_cat_id) : $o_article->in_category($s_act_cat_id);
                if ($bl_in_cat) {
                    $s_seo_uri = $this->create_article_category_uri($o_article, $o_act_cat, $i_lang);
                }
            }
        }
        stop_profile(__FUNCTION__);
        return $s_seo_uri;
    }
    /**
     * Returns active category if available
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return \OxidEsales\Eshop\Application\Model\Category|null
     */
    protected function get_category($o_article, $i_lang)
    {
        $o_cat = null;
        $o_view = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view();
        if ($o_view instanceof \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller) {
            $o_cat = $o_view->get_active_category();
        } elseif ($o_view instanceof \Oxid_Esales\Eshop\Core\Controller\Base_Controller) {
            $o_cat = $o_view->get_act_category();
        }
        return $o_cat;
    }
    /**
     * Returns products main category id
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle product
     *
     * @return string
     */
    protected function get_main_category($o_article)
    {
        $o_main_cat = null;
        // if variant parent id must be used
        $s_art_id = $o_article->get_id();
        if (isset($o_article->oxarticles__oxparentid->value) && $o_article->oxarticles__oxparentid->value) {
            $s_art_id = $o_article->oxarticles__oxparentid->value;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $category_view_name = $table_view_name_generator->get_view_name('oxobject2category');
        // add main category caching;
        $s_q = 'select oxcatnid from ' . $category_view_name . ' where oxobjectid = :oxobjectid order by oxtime';
        $s_ident = md5($category_view_name . $s_art_id);
        if (($s_main_cat_id = $this->load_from_cache($s_ident, 'oxarticle')) === false) {
            $s_main_cat_id = $o_db->get_one($s_q, ['oxobjectid' => $s_art_id]);
            // storing in cache
            $this->save_in_cache($s_ident, $s_main_cat_id, 'oxarticle');
        }
        if ($s_main_cat_id) {
            $o_main_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            if (!$o_main_cat->load($s_main_cat_id)) {
                $o_main_cat = null;
            }
        }
        return $o_main_cat;
    }
    /**
     * Returns SEO uri for passed article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     * @param int                                         $iLang    language id
     *
     * @return string
     */
    public function get_article_main_uri($o_article, $i_lang)
    {
        start_profile(__FUNCTION__);
        $o_main_cat = $this->get_main_category($o_article);
        $s_main_cat_id = $o_main_cat ? $o_main_cat->get_id() : '';
        //load default article url from DB
        if (!$s_seo_uri = $this->load_from_db('oxarticle', $o_article->get_id(), $i_lang, null, $s_main_cat_id, true)) {
            // save for main category
            if ($o_main_cat) {
                $s_seo_uri = $this->create_article_category_uri($o_article, $o_main_cat, $i_lang);
            } else {
                // get default article url
                $o_article = $this->get_product_for_lang($o_article, $i_lang);
                $s_seo_uri = $this->process_seo_url($this->prepare_article_title($o_article), $o_article->get_id(), $i_lang);
                // save default article url
                $this->save_to_db('oxarticle', $o_article->get_id(), $o_article->get_base_std_link($i_lang), $s_seo_uri, $i_lang, null, 0, '');
            }
        }
        stop_profile(__FUNCTION__);
        return $s_seo_uri;
    }
    /**
     * Returns seo title for current article (if oxTitle field is empty, oxArtnum is used).
     * Additionally - if oxVarSelect is set - title is appended with its value
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     *
     * @return string
     */
    protected function prepare_article_title($o_article)
    {
        // create title part for uri
        if (!$s_title = $o_article->oxarticles__oxtitle->value) {
            // taking parent article title
            if ($s_parent_id = $o_article->oxarticles__oxparentid->value) {
                // looking in cache ..
                if (!isset(self::$_a_title_cache[$s_parent_id])) {
                    $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
                    $s_q = 'select oxtitle from ' . $o_article->get_view_name() . ' where oxid = :oxid';
                    self::$_a_title_cache[$s_parent_id] = $o_db->get_one($s_q, ['oxid' => $s_parent_id]);
                }
                $s_title = self::$_a_title_cache[$s_parent_id];
            }
        }
        // variant has varselect value
        if ($o_article->oxarticles__oxvarselect->value) {
            $s_title .= ($s_title ? ' ' : '') . $o_article->oxarticles__oxvarselect->value . ' ';
        } elseif (!$s_title || $o_article->oxarticles__oxparentid->value) {
            // in case nothing was found - looking for number
            $s_title .= ($s_title ? ' ' : '') . $o_article->oxarticles__oxartnum->value;
        }
        return $this->prepare_title($s_title, false, $o_article->get_language()) . $this->get_url_extension();
    }
    /**
     * Returns vendor seo uri for current article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle     article object
     * @param int                                         $iLang        language id
     * @param bool                                        $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function get_article_vendor_uri($o_article, $i_lang, $bl_regenerate = false)
    {
        start_profile(__FUNCTION__);
        $s_seo_uri = null;
        if ($o_vendor = $this->get_vendor($o_article, $i_lang)) {
            //load details link from DB
            if ($bl_regenerate || !$s_seo_uri = $this->load_from_db('oxarticle', $o_article->get_id(), $i_lang, null, $o_vendor->get_id(), true)) {
                $o_article = $this->get_product_for_lang($o_article, $i_lang);
                // create title part for uri
                $s_title = $this->prepare_article_title($o_article);
                // create uri for all categories
                $s_seo_uri = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Vendor::class)->get_vendor_uri($o_vendor, $i_lang);
                $s_seo_uri = $this->process_seo_url($s_seo_uri . $s_title, $o_article->get_id(), $i_lang);
                $a_std_params = ['cnid' => 'v_' . $o_vendor->get_id(), 'listtype' => $this->get_list_type()];
                $this->save_to_db('oxarticle', $o_article->get_id(), \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->append_url($o_article->get_base_std_link($i_lang), $a_std_params), $s_seo_uri, $i_lang, null, 0, $o_vendor->get_id());
            }
            stop_profile(__FUNCTION__);
        }
        return $s_seo_uri;
    }
    /**
     * Returns active vendor if available
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return \OxidEsales\Eshop\Application\Model\Vendor|null
     */
    protected function get_vendor($o_article, $i_lang)
    {
        $o_view = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view();
        $o_vendor = null;
        if ($s_act_vendor_id = $o_article->oxarticles__oxvendorid->value) {
            if ($o_view instanceof \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller && $o_act_vendor = $o_view->get_act_vendor()) {
                $o_vendor = $o_act_vendor;
            } else {
                $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
            }
            if ($o_vendor->get_id() !== $s_act_vendor_id) {
                $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
                if (!$o_vendor->load_in_lang($i_lang, $s_act_vendor_id)) {
                    $o_vendor = null;
                }
            }
        }
        return $o_vendor;
    }
    /**
     * Returns manufacturer seo uri for current article
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle     article object
     * @param int                                         $iLang        language id
     * @param bool                                        $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function get_article_manufacturer_uri($o_article, $i_lang, $bl_regenerate = false)
    {
        $s_seo_uri = null;
        start_profile(__FUNCTION__);
        if ($o_manufacturer = $this->get_manufacturer($o_article, $i_lang)) {
            //load details link from DB
            if ($bl_regenerate || !$s_seo_uri = $this->load_from_db('oxarticle', $o_article->get_id(), $i_lang, null, $o_manufacturer->get_id(), true)) {
                $o_article = $this->get_product_for_lang($o_article, $i_lang);
                // create title part for uri
                $s_title = $this->prepare_article_title($o_article);
                // create uri for all categories
                $s_seo_uri = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Manufacturer::class)->get_manufacturer_uri($o_manufacturer, $i_lang);
                $s_seo_uri = $this->process_seo_url($s_seo_uri . $s_title, $o_article->get_id(), $i_lang);
                $a_std_params = ['mnid' => $o_manufacturer->get_id(), 'listtype' => $this->get_list_type()];
                $this->save_to_db('oxarticle', $o_article->get_id(), \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->append_url($o_article->get_base_std_link($i_lang), $a_std_params), $s_seo_uri, $i_lang, null, 0, $o_manufacturer->get_id());
            }
            stop_profile(__FUNCTION__);
        }
        return $s_seo_uri;
    }
    /**
     * Returns active manufacturer if available
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return \OxidEsales\Eshop\Application\Model\Manufacturer|null
     */
    protected function get_manufacturer($o_article, $i_lang)
    {
        $o_manufacturer = null;
        if ($s_act_manufacturer_id = $o_article->oxarticles__oxmanufacturerid->value) {
            $o_view = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view();
            if ($o_view instanceof \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller && $o_act_manufacturer = $o_view->get_act_manufacturer()) {
                $o_manufacturer = $o_act_manufacturer;
            } else {
                $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
            }
            if ($o_manufacturer->get_id() !== $s_act_manufacturer_id || $o_manufacturer->get_language() != $i_lang) {
                $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
                if (!$o_manufacturer->load_in_lang($i_lang, $s_act_manufacturer_id)) {
                    $o_manufacturer = null;
                }
            }
        }
        return $o_manufacturer;
    }
    /**
     * return article main url, with path of its default category
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle product
     * @param int                                         $iLang    language id
     *
     * @return string
     */
    public function get_article_main_url($o_article, $i_lang = null)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_article->get_language();
        }
        return $this->get_full_url($this->get_article_main_uri($o_article, $i_lang), $i_lang);
    }
    /**
     * Encodes article URLs into SEO format
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article object
     * @param int                                         $iLang    language
     * @param int                                         $iType    type
     *
     * @return string
     */
    public function get_article_url($o_article, $i_lang = null, $i_type = 0)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_article->get_language();
        }
        $s_uri = match ($i_type) {
            OXARTICLE_LINKTYPE_VENDOR => $this->get_article_vendor_uri($o_article, $i_lang),
            OXARTICLE_LINKTYPE_MANUFACTURER => $this->get_article_manufacturer_uri($o_article, $i_lang),
            OXARTICLE_LINKTYPE_RECOMM => $this->get_article_recomm_uri($o_article, $i_lang),
            default => $this->get_article_uri($o_article, $i_lang),
        };
        // if was unable to fetch type uri - returning main
        if (!$s_uri) {
            $s_uri = $this->get_article_main_uri($o_article, $i_lang);
        }
        return $this->get_full_url($s_uri, $i_lang);
    }
    /**
     * deletes article seo entries
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article to remove
     */
    public function on_delete_article($o_article): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxarticle'", ['oxobjectid' => $o_article->get_id()]);
        $o_db->execute('delete from oxobject2seodata where oxobjectid = :oxobjectid', ['oxobjectid' => $o_article->get_id()]);
        $o_db->execute('delete from oxseohistory where oxobjectid = :oxobjectid', ['oxobjectid' => $o_article->get_id()]);
    }
    /**
     * Returns alternative uri used while updating seo
     *
     * @param string $sObjectId object id
     * @param int    $iLang     language id
     *
     * @return string
     */
    protected function get_alt_uri($s_object_id, $i_lang)
    {
        $s_seo_url = null;
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->set_skip_assign(true);
        if ($o_article->load_in_lang($i_lang, $s_object_id)) {
            // choosing URI type to generate
            return match ($this->get_list_type()) {
                'vendor' => $this->get_article_vendor_uri($o_article, $i_lang, true),
                'manufacturer' => $this->get_article_manufacturer_uri($o_article, $i_lang, true),
                default => $this->get_article_uri($o_article, $i_lang, true),
            };
        }
        return $s_seo_url;
    }
}