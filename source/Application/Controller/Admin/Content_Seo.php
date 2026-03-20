<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Content seo config class
 */
class Content_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Object_Seo
{
    /**
     * Returns url type
     *
     * @return string
     */
    protected function get_type()
    {
        return 'oxcontent';
    }
    /**
     * Returns current object type seo encoder object
     *
     * @return \OxidEsales\Eshop\Application\Model\SeoEncoderContent
     */
    protected function get_encoder()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Content::class);
    }
    /**
     * Returns seo uri
     *
     * @return string
     */
    public function get_entry_uri()
    {
        $o_content = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
        if ($o_content->load($this->get_edit_object_id())) {
            return $this->get_encoder()->get_content_uri($o_content, $this->get_edit_lang());
        }
    }
}