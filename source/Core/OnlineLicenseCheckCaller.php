<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Ox_Exception;
/**
 * Class makes call to given URL address and sends request parameter.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_License_Check_Caller extends \Oxid_Esales\Eshop\Core\Online_Caller
{
    /** Online License Key Check web service url. */
    public const WEB_SERVICE_URL = 'https://olc.oxid-esales.com/check.php';
    /** XML document tag name. */
    public const XML_DOCUMENT_NAME = 'olcRequest';
    /**
     * Expected response element in the XML response message fom web service.
     */
    private string $_s_response_element = 'olc';
    /**
     * Performs Web service request
     *
     * @param \OxidEsales\Eshop\Core\OnlineLicenseCheckRequest $oRequest Object with request parameters
     *
     * @throws oxException
     * @return \OxidEsales\Eshop\Core\OnlineLicenseCheckResponse
     */
    public function do_request(\Oxid_Esales\Eshop\Core\Online_License_Check_Request $o_request)
    {
        return $this->form_response($this->call($o_request));
    }
    /**
     * Removes serial keys from request and forms email body.
     *
     * @param \OxidEsales\Eshop\Core\OnlineLicenseCheckRequest $oRequest
     *
     * @return string
     */
    protected function form_email($o_request)
    {
        $o_request->keys = null;
        return parent::form_email($o_request);
    }
    /**
     * Parse response message received from Online License Key Check web service and save it to response object.
     *
     * @param string $sRawResponse UnResponse from server
     *
     * @throws oxException
     *
     * @return \OxidEsales\Eshop\Core\OnlineLicenseCheckResponse
     */
    protected function form_response($s_raw_response)
    {
        /** @var \OxidEsales\Eshop\Core\UtilsXml $oUtilsXml */
        $o_utils_xml = \Oxid_Esales\Eshop\Core\Registry::get_utils_xml();
        if (empty($s_raw_response) || !$o_dom_doc = $o_utils_xml->load_xml($s_raw_response)) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('OLC_ERROR_RESPONSE_NOT_VALID');
        }
        if ($o_dom_doc->document_element->node_name != $this->_s_response_element) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('OLC_ERROR_RESPONSE_UNEXPECTED');
        }
        $o_response_node = $o_dom_doc->first_child;
        if (!$o_response_node->has_child_nodes()) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('OLC_ERROR_RESPONSE_NOT_VALID');
        }
        $o_nodes = $o_response_node->child_nodes;
        /** @var \OxidEsales\Eshop\Core\OnlineLicenseCheckResponse $oResponse */
        $o_response = ox_new(\Oxid_Esales\Eshop\Core\Online_License_Check_Response::class);
        // iterate through response node to get response parameters
        for ($i = 0; $i < $o_nodes->length; $i++) {
            $s_node_name = $o_nodes->item($i)->node_name;
            $s_node_value = $o_nodes->item($i)->node_value;
            $o_response->{$s_node_name} = $s_node_value;
        }
        return $o_response;
    }
    /**
     * Gets XML document name.
     *
     * @return string XML document tag name.
     */
    protected function get_xml_document_name()
    {
        return self::XML_DOCUMENT_NAME;
    }
    /**
     * Gets service url.
     *
     * @return string Web service url.
     */
    protected function get_service_url()
    {
        return self::WEB_SERVICE_URL;
    }
}