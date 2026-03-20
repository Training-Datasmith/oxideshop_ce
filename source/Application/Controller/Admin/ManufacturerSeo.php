<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Manufacturer seo config class
 */
class Manufacturer_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Object_Seo
{
    /**
     * Updating showsuffix field
     */
    public function save()
    {
        $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $o_manufacturer->init('oxmanufacturers');
        if ($o_manufacturer->load($this->get_edit_object_id())) {
            $s_show_suffix_field = 'oxmanufacturers__oxshowsuffix';
            $bl_show_suffix_parameter = Registry::get_request()->get_request_escaped_parameter('blShowSuffix');
            $o_manufacturer->{$s_show_suffix_field} = new \Oxid_Esales\Eshop\Core\Field((int) $bl_show_suffix_parameter);
            $o_manufacturer->save();
        }
        return parent::save();
    }
    /**
     * Returns current object type seo encoder object
     *
     * @return \OxidEsales\Eshop\Application\Model\SeoEncoderManufacturer
     */
    protected function get_encoder()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Manufacturer::class);
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
     * Returns url type
     *
     * @return string
     */
    protected function get_type()
    {
        return 'oxmanufacturer';
    }
    /**
     * Returns true if SEO object id has suffix enabled
     *
     * @return bool
     */
    public function is_entry_suffixed()
    {
        $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
        if ($o_manufacturer->load($this->get_edit_object_id())) {
            return (bool) $o_manufacturer->oxmanufacturers__oxshowsuffix->value;
        }
    }
    /**
     * Returns seo uri
     *
     * @return string
     */
    public function get_entry_uri()
    {
        $o_manufacturer = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer::class);
        if ($o_manufacturer->load($this->get_edit_object_id())) {
            return $this->get_encoder()->get_manufacturer_uri($o_manufacturer, $this->get_edit_lang());
        }
    }
}