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
class Seo_Encoder_Manufacturer extends \Oxid_Esales\Eshop\Core\Seo_Encoder
{
    /**
     * Root manufacturer uri cache
     *
     * @var array
     */
    protected $_a_root_manufacturer_uri;
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
     * Returns part of SEO url excluding path
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer manufacturer object
     * @param int                                              $iLang         language
     * @param bool                                             $blRegenerate  if TRUE forces seo url regeneration
     *
     * @return string
     */
    public function get_manufacturer_uri($o_manufacturer, $i_lang = null, $bl_regenerate = false)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_manufacturer->get_language();
        }
        // load from db
        if ($bl_regenerate || !$s_seo_url = $this->load_from_db('oxmanufacturer', $o_manufacturer->get_id(), $i_lang)) {
            if ($i_lang != $o_manufacturer->get_language()) {
                $s_id = $o_manufacturer->get_id();
                $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
                $o_manufacturer->load_in_lang($i_lang, $s_id);
            }
            $s_seo_url = '';
            if ($o_manufacturer->get_id() != 'root') {
                if (!isset($this->_a_root_manufacturer_uri[$i_lang])) {
                    $o_root_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
                    $o_root_manufacturer->load_in_lang($i_lang, 'root');
                    $this->_a_root_manufacturer_uri[$i_lang] = $this->get_manufacturer_uri($o_root_manufacturer, $i_lang);
                }
                $s_seo_url .= $this->_a_root_manufacturer_uri[$i_lang];
            }
            $s_seo_url .= $this->prepare_title($o_manufacturer->oxmanufacturers__oxtitle->value, false, $o_manufacturer->get_language()) . '/';
            $s_seo_url = $this->process_seo_url($s_seo_url, $o_manufacturer->get_id(), $i_lang);
            // save to db
            $this->save_to_db('oxmanufacturer', $o_manufacturer->get_id(), $o_manufacturer->get_base_std_link($i_lang), $s_seo_url, $i_lang);
        }
        return $s_seo_url;
    }
    /**
     * Returns Manufacturer SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $manufacturer Manufacturer object
     * @param int                                              $pageNumber   Number of the page which should be prepared.
     * @param int                                              $languageId   Language id.
     * @param bool                                             $isFixed      Fixed url marker (default is null).
     *
     * @return string
     */
    public function get_manufacturer_page_url($manufacturer, $page_number, $language_id = null, $is_fixed = null)
    {
        if (!isset($language_id)) {
            $language_id = $manufacturer->get_language();
        }
        $std_url = $manufacturer->get_base_std_link($language_id);
        $parameters = null;
        $std_url = $this->trim_url($std_url, $language_id);
        $seo_url = $this->get_manufacturer_uri($manufacturer, $language_id);
        if ($is_fixed === null) {
            $is_fixed = $this->is_fixed('oxmanufacturer', $manufacturer->get_id(), $language_id);
        }
        return $this->assemble_full_page_url($manufacturer, 'oxmanufacturer', $std_url, $seo_url, $page_number, $parameters, $language_id, $is_fixed);
    }
    /**
     * Encodes manufacturer category URLs into SEO format
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer Manufacturer object
     * @param int                                              $iLang         language
     *
     * @return string
     */
    public function get_manufacturer_url($o_manufacturer, $i_lang = null)
    {
        if (!isset($i_lang)) {
            $i_lang = $o_manufacturer->get_language();
        }
        return $this->get_full_url($this->get_manufacturer_uri($o_manufacturer, $i_lang), $i_lang);
    }
    /**
     * Deletes manufacturer seo entry
     *
     * @param \OxidEsales\Eshop\Application\Model\Manufacturer $oManufacturer Manufacturer object
     */
    public function on_delete_manufacturer($o_manufacturer): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxmanufacturer'", ['oxobjectid' => $o_manufacturer->get_id()]);
        $o_db->execute('delete from oxobject2seodata where oxobjectid = :oxobjectid', ['oxobjectid' => $o_manufacturer->get_id()]);
        $o_db->execute('delete from oxseohistory where oxobjectid = :oxobjectid', ['oxobjectid' => $o_manufacturer->get_id()]);
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
        $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
        if ($o_manufacturer->load_in_lang($i_lang, $s_object_id)) {
            return $this->get_manufacturer_uri($o_manufacturer, $i_lang, true);
        }
        return $s_seo_url;
    }
}