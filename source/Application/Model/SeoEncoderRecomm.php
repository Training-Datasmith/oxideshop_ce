<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Seo encoder base
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class Seo_Encoder_Recomm extends \Oxid_Esales\Eshop\Core\Seo_Encoder
{
    /**
     * Returns SEO uri for tag.
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecomm recommendation list object
     * @param int                                                    $iLang   language
     *
     * @return string
     */
    public function get_recomm_uri($o_recomm, $i_lang = null)
    {
        if (!$s_seo_url = $this->load_from_db('dynamic', $o_recomm->get_id(), $i_lang)) {
            $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            // fetching part of base url
            $s_seo_url = $this->get_static_uri($o_recomm->get_base_std_link($i_lang, false), $my_config->get_shop_id(), $i_lang) . $this->prepare_title($o_recomm->oxrecommlists__oxtitle->value, false, $i_lang);
            // creating unique
            $s_seo_url = $this->process_seo_url($s_seo_url, $o_recomm->get_id(), $i_lang);
            // inserting
            $this->save_to_db('dynamic', $o_recomm->get_id(), $o_recomm->get_base_std_link($i_lang), $s_seo_url, $i_lang, $my_config->get_shop_id());
        }
        return $s_seo_url;
    }
    /**
     * Returns full url for passed tag
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $oRecomm recommendation list object
     * @param int                                                    $iLang   language
     *
     * @return string
     */
    public function get_recomm_url($o_recomm, $i_lang = null)
    {
        if (!isset($i_lang)) {
            $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        }
        return $this->get_full_url($this->get_recomm_uri($o_recomm, $i_lang), $i_lang);
    }
    /**
     * Returns tag SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\RecommendationList $recomm     Recommendation list object.
     * @param int                                                    $pageNumber Number of the page which should be prepared.
     * @param int                                                    $languageId Language id.
     * @param bool                                                   $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    public function get_recomm_page_url($recomm, $page_number, $language_id = null, $is_fixed = false)
    {
        if (!isset($language_id)) {
            $language_id = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        }
        $std_url = $recomm->get_base_std_link($language_id);
        $parameters = null;
        $std_url = $this->trim_url($std_url, $language_id);
        $seo_url = $this->get_recomm_uri($recomm, $language_id);
        return $this->assemble_full_page_url($recomm, 'dynamic', $std_url, $seo_url, $page_number, $parameters, $language_id, $is_fixed);
    }
}