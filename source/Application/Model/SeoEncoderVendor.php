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
class Seo_Encoder_Vendor extends \Oxid_Esales\Eshop\Core\Seo_Encoder
{
    /**
     * Root vendor uri cache
     *
     * @var string
     */
    protected $_a_root_vendor_uri;
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
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor           Vendor object
     * @param int                                        $languageId       Language id
     * @param bool                                       $shouldRegenerate If TRUE - forces seo url regeneration
     *
     * @return string
     */
    public function get_vendor_uri($vendor, $language_id = null, $should_regenerate = false)
    {
        if (!isset($language_id)) {
            $language_id = $vendor->get_language();
        }
        // load from db
        if ($should_regenerate || !$seo_url = $this->load_from_db('oxvendor', $vendor->get_id(), $language_id)) {
            if ($language_id != $vendor->get_language()) {
                $vendor_id = $vendor->get_id();
                $vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
                $vendor->load_in_lang($language_id, $vendor_id);
            }
            $seo_url = '';
            if ($vendor->get_id() != 'root') {
                if (!isset($this->_a_root_vendor_uri[$language_id])) {
                    $root_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
                    $root_vendor->load_in_lang($language_id, 'root');
                    $this->_a_root_vendor_uri[$language_id] = $this->get_vendor_uri($root_vendor, $language_id);
                }
                $seo_url .= $this->_a_root_vendor_uri[$language_id];
            }
            $seo_url .= $this->prepare_title($vendor->oxvendor__oxtitle->value, false, $vendor->get_language()) . '/';
            $seo_url = $this->process_seo_url($seo_url, $vendor->get_id(), $language_id);
            // save to db
            $this->save_to_db('oxvendor', $vendor->get_id(), $vendor->get_base_std_link($language_id), $seo_url, $language_id);
        }
        return $seo_url;
    }
    /**
     * Returns vendor SEO url for specified page
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor     Vendor object.
     * @param int                                        $pageNumber Number of the page which should be prepared.
     * @param int                                        $languageId Language id.
     * @param bool                                       $isFixed    Fixed url marker (default is null).
     *
     * @return string
     */
    public function get_vendor_page_url($vendor, $page_number, $language_id = null, $is_fixed = null)
    {
        if (!isset($language_id)) {
            $language_id = $vendor->get_language();
        }
        $std_url = $vendor->get_base_std_link($language_id);
        $parameters = null;
        $std_url = $this->trim_url($std_url, $language_id);
        $seo_url = $this->get_vendor_uri($vendor, $language_id);
        if ($is_fixed === null) {
            $is_fixed = $this->is_fixed('oxvendor', $vendor->get_id(), $language_id);
        }
        return $this->assemble_full_page_url($vendor, 'oxvendor', $std_url, $seo_url, $page_number, $parameters, $language_id, $is_fixed);
    }
    /**
     * Encodes vendor category URLs into SEO format.
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor     Vendor object
     * @param int                                        $languageId Language id
     */
    public function get_vendor_url($vendor, $language_id = null)
    {
        if (!isset($language_id)) {
            $language_id = $vendor->get_language();
        }
        return $this->get_full_url($this->get_vendor_uri($vendor, $language_id), $language_id);
    }
    /**
     * Deletes Vendor seo entry
     *
     * @param \OxidEsales\Eshop\Application\Model\Vendor $vendor Vendor object
     */
    public function on_delete_vendor($vendor): void
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $vendor_id = $vendor->get_id();
        $database->execute("delete from oxseo where oxobjectid = :oxobjectid and oxtype = 'oxvendor'", ['oxobjectid' => $vendor_id]);
        $database->execute('delete from oxobject2seodata where oxobjectid = :oxobjectid', ['oxobjectid' => $vendor_id]);
        $database->execute('delete from oxseohistory where oxobjectid = :oxobjectid', ['oxobjectid' => $vendor_id]);
    }
    /**
     * Returns alternative uri used while updating seo.
     *
     * @param string $vendorId   Vendor id
     * @param int    $languageId Language id
     *
     * @return string
     */
    protected function get_alt_uri($vendor_id, $language_id)
    {
        $seo_url = null;
        $vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        if ($vendor->load_in_lang($language_id, $vendor_id)) {
            return $this->get_vendor_uri($vendor, $language_id, true);
        }
        return $seo_url;
    }
}