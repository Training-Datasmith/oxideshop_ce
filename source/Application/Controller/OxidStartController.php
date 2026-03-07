<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\SystemEventHandler;

/**
 * Encapsulates methods for application initialization.
 */
class OxidStartController extends \OxidEsales\Eshop\Application\Controller\FrontendController
{
    /**
     * Initializes globals and environment vars
     */
    public function appInit(): void
    {
        $this->pageStart();

        if ('oxstart' == \OxidEsales\Eshop\Core\Registry::getConfig()->getRequestControllerId() || $this->isAdmin()) {
            return;
        }

        $oSystemEventHandler = $this->getSystemEventHandler();
        $oSystemEventHandler->onShopStart();
    }

    /**
     * Renders error screen
     *
     * @return string
     */
    public function render()
    {
        parent::render();

        $errorNumber = Registry::getRequest()->getRequestEscapedParameter('execerror');
        $templates = $this->getErrorTemplates();

        if (array_key_exists($errorNumber, $templates)) {
            return $templates[$errorNumber];
        }
        return 'message/err_unknown';
    }

    /**
     * Creates and starts session object, sets default currency.
     */
    public function pageStart(): void
    {
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();

        $config->setConfigParam('iMaxMandates', $config->getConfigParam('IMS'));
        $config->setConfigParam('iMaxArticles', $config->getConfigParam('IMA'));
    }

    /**
     * Finalizes the script.
     */
    public function pageClose(): void
    {
        $systemEventHandler = $this->getSystemEventHandler();
        $systemEventHandler->onShopEnd();

        $session = \OxidEsales\Eshop\Core\Registry::getSession();
        if (isset($session)) {
            $session->freeze();
        }
    }

    /**
     * Return error number
     *
     * @return integer
     */
    public function getErrorNumber()
    {
        return Registry::getRequest()->getRequestEscapedParameter('errornr');
    }

    /**
     * Returns which template should be used for specific error.
     *
     * @return array
     */
    protected function getErrorTemplates()
    {
        return [
            'unknown' => 'message/err_unknown',
        ];
    }

    /**
     * Gets system event handler.
     *
     * @return SystemEventHandler
     */
    protected function getSystemEventHandler()
    {
        return oxNew(\OxidEsales\Eshop\Core\SystemEventHandler::class);
    }
}
