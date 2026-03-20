<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Category seo config class
 */
class Category_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Object_Seo
{
    /**
     * Updating showsuffix field
     */
    public function save()
    {
        $s_oxid = $this->get_edit_object_id();
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        if ($o_category->load($s_oxid)) {
            $bl_show_suffix_parameter = Registry::get_request()->get_request_escaped_parameter('blShowSuffix');
            $s_show_suffix_field = 'oxcategories__oxshowsuffix';
            $o_category->{$s_show_suffix_field} = new \Oxid_Esales\Eshop\Core\Field((int) $bl_show_suffix_parameter);
            $o_category->save();
            $this->get_encoder()->mark_related_as_expired($o_category);
        }
        return parent::save();
    }
    /**
     * Returns current object type seo encoder object
     *
     * @return \OxidEsales\Eshop\Application\Model\SeoEncoderCategory
     */
    protected function get_encoder()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Category::class);
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
        return 'oxcategory';
    }
    /**
     * Returns true if SEO object id has suffix enabled
     *
     * @return bool
     */
    public function is_entry_suffixed()
    {
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        if ($o_category->load($this->get_edit_object_id())) {
            return (bool) $o_category->oxcategories__oxshowsuffix->value;
        }
    }
    /**
     * Returns seo uri
     *
     * @return string
     */
    public function get_entry_uri()
    {
        $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        if ($o_category->load($this->get_edit_object_id())) {
            return $this->get_encoder()->get_category_uri($o_category, $this->get_edit_lang());
        }
    }
}