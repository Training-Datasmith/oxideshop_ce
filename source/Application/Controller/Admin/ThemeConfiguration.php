<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use oxAdminDetails;
use OxidEsales\Eshop\Core\Registry;

class ThemeConfiguration extends \OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration
{
    protected $_sTheme;

    /** @inheritdoc */
    public function render()
    {
        $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();

        $sTheme = $this->_sTheme = $this->getEditObjectId();
        $sShopId = $myConfig->getShopId();

        if (!isset($sTheme)) {
            $sTheme = $this->_sTheme = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sTheme');
        }

        $oTheme = oxNew(\OxidEsales\Eshop\Core\Theme::class);
        if ($oTheme->load($sTheme)) {
            $this->_aViewData['oTheme'] = $oTheme;

            try {
                $aDbVariables = $this->loadConfVars($sShopId, $this->getModuleForConfigVars());
                $this->_aViewData['var_constraints'] = $aDbVariables['constraints'];
                $this->_aViewData['var_grouping'] = $aDbVariables['grouping'];
                foreach ($this->_aConfParams as $sType => $sParam) {
                    $this->_aViewData[$sParam] = $aDbVariables['vars'][$sType] ?? null;
                }
            } catch (\OxidEsales\Eshop\Core\Exception\StandardException $exception) {
                Registry::getUtilsView()->addErrorToDisplay($exception);
                Registry::getLogger()->error($exception->getMessage(), [$exception]);
            }
        } else {
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay(oxNew(\OxidEsales\Eshop\Core\Exception\StandardException::class, 'EXCEPTION_THEME_NOT_LOADED'));
        }

        return 'theme_config';
    }

    /**
     * return theme filter for config variables
     *
     * @return string
     */
    protected function getModuleForConfigVars()
    {
        if ($this->_sTheme === null) {
            $this->_sTheme = $this->getEditObjectId();
        }

        return \OxidEsales\Eshop\Core\Config::OXMODULE_THEME_PREFIX . $this->_sTheme;
    }

    /**
     * Saves shop configuration variables
     */
    public function saveConfVars(): void
    {
        $myConfig = \OxidEsales\Eshop\Core\Registry::getConfig();

        oxAdminDetails::save();

        $sShopId = $myConfig->getShopId();

        $sModule = $this->getModuleForConfigVars();

        foreach ($this->_aConfParams as $sType => $sParam) {
            $aConfVars = Registry::getRequest()->getRequestEscapedParameter($sParam);
            if (is_array($aConfVars)) {
                foreach ($aConfVars as $sName => $sValue) {
                    $myConfig->saveShopConfVar(
                        $sType,
                        $sName,
                        $this->serializeConfVar($sType, $sName, $sValue),
                        $sShopId,
                        $sModule
                    );
                }
            }
        }
    }
}
