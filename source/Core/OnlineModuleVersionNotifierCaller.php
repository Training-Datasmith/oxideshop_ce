<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class makes call to given URL address and sends request parameter.
 *
 * The Online Module Version Notification is used for checking if newer versions of modules are available.
 * Will be used by the upcoming online one click installer.
 * Is still under development - still changes at the remote server are necessary - therefore ignoring the results for now
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_Module_Version_Notifier_Caller extends \Oxid_Esales\Eshop\Core\Online_Caller
{
    /** Online Module Version Notifier web service url. */
    public const WEB_SERVICE_URL = 'https://omvn.oxid-esales.com/check.php';
    /** XML document tag name. */
    public const XML_DOCUMENT_NAME = 'omvnRequest';
    /**
     * Performs Web service request
     *
     * @param \OxidEsales\Eshop\Core\OnlineModulesNotifierRequest $oRequest Object with request parameters
     */
    public function do_request(\Oxid_Esales\Eshop\Core\Online_Modules_Notifier_Request $o_request): void
    {
        $this->call($o_request);
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