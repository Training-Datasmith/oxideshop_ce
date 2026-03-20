<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Application\Model\Category;
use Oxid_Esales\Eshop\Core\Database_Provider;
/**
 * Seo encoder category
 */
class Seo_Encoder_Category extends \Oxid_Esales\Eshop\Core\Seo_Encoder
{
    /** @var array _aCatCache cache for categories. */
    protected $_a_cat_cache = [];
    /**
     * Returns target "extension" (/)
     *
     * @return string
     */
    protected function get_url_extension()
    {
        return '/';
    }
    /**
     * _categoryUrlLoader loads category from db
     * returns false if cat needs to be encoded (load failed)
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat  category object
     * @param int                                          $iLang active language id
     *
     * @access protected
     *
     * @return boolean
     */
    protected function category_url_loader($o_cat, $i_lang)
    {
        $s_cache_id = $this->get_category_cache_id($o_cat, $i_lang);
        if (isset($this->_a_cat_cache[$s_cache_id])) {
            $s_seo_url = $this->_a_cat_cache[$s_cache_id];
        } elseif ($s_seo_url = $this->load_from_db('oxcategory', $o_cat->get_id(), $i_lang)) {
            // caching
            $this->_a_cat_cache[$s_cache_id] = $s_seo_url;
        }
        return $s_seo_url;
    }
    /**
     * _getCatecgoryCacheId return string for isntance cache id
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat  category object
     * @param int                                          $iLang active language
     *
     * @access private
     */
    private function get_category_cache_id($o_cat, $i_lang): string
    {
        return $o_cat->get_id() . '_' . (int) $i_lang;
    }
    /**
     * Returns SEO uri for passed category
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCat         category object
     * @param int                                          $iLang        language
     * @param bool                                         $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function get_category_uri($o_cat, $i_lang = null, $bl_regenerate = false)
    {
        start_profile(__FUNCTION__);
        $s_cat_id = $o_cat->get_id();
        // skipping external category URLs
        if ($o_cat->oxcategories__oxextlink->value) {
            $s_seo_url = null;
        } else {
            // not found in cache, process it from the top
            if (!isset($i_lang)) {
                $i_lang = $o_cat->get_language();
            }
            $a_cache_map = [];
            $a_std_links = [];
            while ($o_cat && !$s_seo_url = $this->category_url_loader($o_cat, $i_lang)) {
                if ($i_lang != $o_cat->get_language()) {
                    $s_id = $o_cat->get_id();
                    $o_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
                    $o_cat->load_in_lang($i_lang, $s_id);
                }
                // prepare oCat title part
                $s_title = $this->prepare_title($o_cat->oxcategories__oxtitle->value, false, $o_cat->get_language());
                foreach (array_keys($a_cache_map) as $id) {
                    $a_cache_map[$id] = $s_title . '/' . $a_cache_map[$id];
                }
                $a_cache_map[$o_cat->get_id()] = $s_title;
                $a_std_links[$o_cat->get_id()] = $o_cat->get_base_std_link($i_lang);
                // load parent
                $o_cat = $o_cat->get_parent_category();
            }
            foreach ($a_cache_map as $s_id => $s_uri) {
                $this->_a_cat_cache[$s_id . '_' . $i_lang] = $this->process_seo_url($s_seo_url . $s_uri . '/', $s_id, $i_lang);
                $this->save_to_db('oxcategory', $s_id, $a_std_links[$s_id], $this->_a_cat_cache[$s_id . '_' . $i_lang], $i_lang);
            }
            $s_seo_url = $this->_a_cat_cache[$s_cat_id . '_' . $i_lang];
        }
        stop_profile(__FUNCTION__);
        return $s_seo_url;
    }
    /**
     * Returns category SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $category   Category object.
     * @param int                                          $pageNumber Number of the page which should be prepared.
     * @param int                                          $languageId Language id.
     * @param bool                                         $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    public function get_category_page_url($category, $page_number, $language_id = null, $is_fixed = null)
    {
        if (!isset($language_id)) {
            $language_id = $category->get_language();
        }
        $std_url = $category->get_base_std_link($language_id);
        $parameters = null;
        $std_url = $this->trim_url($std_url, $language_id);
        $seo_url = $this->get_category_uri($category, $language_id);
        if ($is_fixed === null) {
            $is_fixed = $this->is_fixed('oxcategory', $category->get_id(), $language_id);
        }
        return $this->assemble_full_page_url($category, 'oxcategory', $std_url, $seo_url, $page_number, $parameters, $language_id, $is_fixed);
    }
    /**
     * Category URL encoder. If category has external URLs, skip encoding
     * for this category. If SEO id is not set, generates and saves SEO id
     * for category (\OxidEsales\Eshop\Core\SeoEncoder::_getSeoId()).
     * If category has subcategories, it iterates through them.
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory Category object
     * @param int                                          $iLang     Language
     *
     * @return string
     */
    public function get_category_url($o_category, $i_lang = null)
    {
        $s_url = '';
        if (!isset($i_lang)) {
            $i_lang = $o_category->get_language();
        }
        // category may have specified url
        if ($s_seo_url = $this->get_category_uri($o_category, $i_lang)) {
            return $this->get_full_url($s_seo_url, $i_lang);
        }
        return $s_url;
    }
    /**
     * Marks related to category objects as expired
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory Category object
     */
    public function mark_related_as_expired($o_category): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // select it from table instead of using object carrying value
        // this is because this method is usually called inside update,
        // where object may already be carrying changed id
        $a_cat_info = $o_db->get_row('select oxrootid, oxleft, oxright from oxcategories where oxid = :oxid limit 1', ['oxid' => $o_category->get_id()]);
        // update sub cats
        $s_q = "update oxseo as seo1, (select oxid from oxcategories \n            where oxrootid = :oxrootid \n            and oxleft > :oxleft \n            and oxright < :oxright ) as seo2 \n                set seo1.oxexpired = '1' where seo1.oxtype = 'oxcategory' and seo1.oxobjectid = seo2.oxid";
        $o_db->execute($s_q, ['oxrootid' => $a_cat_info['oxrootid'], 'oxleft' => (int) $a_cat_info['oxleft'], 'oxright' => (int) $a_cat_info['oxright']]);
        // update subarticles
        $s_q = 'update oxseo as seo1, (select distinct o2c.oxobjectid as id from oxcategories as cat left join oxobject2category ' . 'as o2c on o2c.oxcatnid=cat.oxid where cat.oxrootid = :oxrootid and cat.oxleft >= :oxleft ' . 'and cat.oxright <= :oxright) as seo2 ' . "set seo1.oxexpired = '1' where seo1.oxtype = 'oxarticle' and seo1.oxobjectid = seo2.id " . 'and seo1.oxfixed = 0';
        $o_db->execute($s_q, ['oxrootid' => $a_cat_info['oxrootid'], 'oxleft' => (int) $a_cat_info['oxleft'], 'oxright' => (int) $a_cat_info['oxright']]);
    }
    /**
     * @param Category $category
     */
    public function on_delete_category($category): void
    {
        $this->set_related_to_category_seo_urls_as_expired($category);
        $database = Database_Provider::get_db();
        $database->execute("delete from oxseo where oxseo.oxtype = 'oxarticle' and oxseo.oxparams = :oxparams", ['oxparams' => $category->get_id()]);
        $database->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxcategory'", ['oxobjectid' => $category->get_id()]);
        $database->execute('delete from oxobject2seodata where oxobjectid = :oxobjectid', ['oxobjectid' => $category->get_id()]);
        $database->execute('delete from oxseohistory where oxobjectid = :oxobjectid', ['oxobjectid' => $category->get_id()]);
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
        $o_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        if ($o_cat->load_in_lang($i_lang, $s_object_id)) {
            return $this->get_category_uri($o_cat, $i_lang);
        }
        return $s_seo_url;
    }
    private function set_related_to_category_seo_urls_as_expired(Category $category): void
    {
        foreach ($this->get_seo_urls_for_category($category) as $seo_url) {
            $this->set_seo_urls_as_expired($this->get_related_products_and_sub_categories($seo_url));
        }
    }
    private function get_seo_urls_for_category(Category $category): array
    {
        return Database_Provider::get_db()->get_col("select oxseourl from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxcategory'", ['oxobjectid' => $category->get_id()]);
    }
    private function get_related_products_and_sub_categories(string $root_category_url): array
    {
        return Database_Provider::get_db()->get_col("\n            select oxident\n            from oxseo\n            where oxseo.oxseourl like CONCAT(:url, '%') \n              and oxtype in ('oxarticle', 'oxcategory')", ['url' => $root_category_url]);
    }
    private function set_seo_urls_as_expired(array $idents): void
    {
        Database_Provider::get_db()->execute(sprintf("update oxseo set oxseo.oxexpired=1 where oxseo.oxident in ('%s')", implode("','", $idents)));
    }
}