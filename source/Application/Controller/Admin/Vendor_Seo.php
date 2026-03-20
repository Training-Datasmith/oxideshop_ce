<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Vendor seo config class
 */
class Vendor_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Object_Seo
{
    /**
     * Updating showsuffix field
     */
    public function save()
    {
        $o_vendor = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $o_vendor->init('oxvendor');
        if ($o_vendor->load($this->get_edit_object_id())) {
            $s_show_suffix_field = 'oxvendor__oxshowsuffix';
            $bl_show_suffix_parameter = Registry::get_request()->get_request_escaped_parameter('blShowSuffix');
            $o_vendor->{$s_show_suffix_field} = new \Oxid_Esales\Eshop\Core\Field((int) $bl_show_suffix_parameter);
            $o_vendor->save();
        }
        return parent::save();
    }
    /**
     * Returns current object type seo encoder object
     *
     * @return \OxidEsales\Eshop\Application\Model\SeoEncoderVendor
     */
    protected function get_encoder()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Vendor::class);
    }
    /**
     * This SEO object supports suffixes so return TRUE
     *
     * @return bool
     */
    public function is_suffix_supported()
    {
        return true;
    }
    /**
     * Returns true if SEO object id has suffix enabled
     *
     * @return bool
     */
    public function is_entry_suffixed()
    {
        $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        if ($o_vendor->load($this->get_edit_object_id())) {
            return (bool) $o_vendor->oxvendor__oxshowsuffix->value;
        }
    }
    /**
     * Returns url type
     *
     * @return string
     */
    protected function get_type()
    {
        return 'oxvendor';
    }
    /**
     * Returns seo uri
     *
     * @return string
     */
    public function get_entry_uri()
    {
        $o_vendor = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor::class);
        if ($o_vendor->load($this->get_edit_object_id())) {
            return $this->get_encoder()->get_vendor_uri($o_vendor, $this->get_edit_lang());
        }
    }
}