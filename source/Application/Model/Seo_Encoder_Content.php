<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Seo encoder base
 */
class Seo_Encoder_Content extends \Oxid_Esales\Eshop\Core\Seo_Encoder
{
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
     * Returns SEO uri for content object. Includes parent category path info if
     * content is assigned to it
     *
     * @param \OxidEsales\Eshop\Application\Model\Content $oCont        content category object
     * @param int                                         $iLang        language
     * @param bool                                        $blRegenerate if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function get_content_uri($o_cont, $i_lang = null, $bl_regenerate = false)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_cont->get_language();
        }
        //load details link from DB
        if ($bl_regenerate || !$s_seo_url = $this->load_from_db('oxContent', $o_cont->get_id(), $i_lang)) {
            if ($i_lang != $o_cont->get_language()) {
                $s_id = $o_cont->get_id();
                $o_cont = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
                $o_cont->load_in_lang($i_lang, $s_id);
            }
            $s_seo_url = '';
            if ($o_cont->get_category_id() && $o_cont->get_type() === 2) {
                $o_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
                if ($o_cat->load_in_lang($i_lang, $o_cont->oxcontents__oxcatid->value)) {
                    $s_parent_id = $o_cat->oxcategories__oxparentid->value;
                    if ($s_parent_id && $s_parent_id != 'oxrootid') {
                        $o_parent_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
                        if ($o_parent_cat->load_in_lang($i_lang, $o_cat->oxcategories__oxparentid->value)) {
                            $s_seo_url .= \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class)->get_category_uri($o_parent_cat);
                        }
                    }
                }
            }
            $s_seo_url .= $this->prepare_title($o_cont->oxcontents__oxtitle->value, false, $o_cont->get_language()) . '/';
            $s_seo_url = $this->process_seo_url($s_seo_url, $o_cont->get_id(), $i_lang);
            $this->save_to_db('oxcontent', $o_cont->get_id(), $o_cont->get_base_std_link($i_lang), $s_seo_url, $i_lang);
        }
        return $s_seo_url;
    }
    /**
     * encodeContentUrl encodes content link
     *
     * @param \OxidEsales\Eshop\Application\Model\Content $oCont category object
     * @param int                                         $iLang language
     *
     * @return string|bool
     */
    public function get_content_url($o_cont, $i_lang = null)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_cont->get_language();
        }
        return $this->get_full_url($this->get_content_uri($o_cont, $i_lang), $i_lang);
    }
    /**
     * deletes content seo entries
     *
     * @param string $sId content ids
     */
    public function on_delete_content($s_id): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxcontent'", ['oxobjectid' => $s_id]);
        $o_db->execute('delete from oxobject2seodata where oxobjectid = :oxobjectid', ['oxobjectid' => $s_id]);
        $o_db->execute('delete from oxseohistory where oxobjectid = :oxobjectid', ['oxobjectid' => $s_id]);
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
        $o_cont = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
        if ($o_cont->load_in_lang($i_lang, $s_object_id)) {
            return $this->get_content_uri($o_cont, $i_lang, true);
        }
        return $s_seo_url;
    }
}