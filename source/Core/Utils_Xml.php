<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Dom_Document;
/**
 * XML document handler
 */
class Utils_Xml extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Takes XML string and makes DOMDocument
     * Returns DOMDocument or false, if it can't be loaded
     *
     * @param string      $sXml         XML as a string
     * @param DOMDocument $oDomDocument DOM handler
     *
     * @return DOMDocument|bool
     */
    public function load_xml($s_xml, $o_dom_document = null)
    {
        if (!$o_dom_document) {
            $o_dom_document = new Dom_Document('1.0', 'utf-8');
        }
        libxml_use_internal_errors(true);
        $o_dom_document->load_xml($s_xml);
        $errors = libxml_get_errors();
        $bl_loaded = empty($errors);
        libxml_clear_errors();
        if ($bl_loaded) {
            return $o_dom_document;
        }
        return false;
    }
}