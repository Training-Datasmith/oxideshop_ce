<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

/**
 * Defines and returns delivery and billing required fields.
 */
class RequiredAddressFields
{
    /**
     * Default required fields for use when not set in config.
     */
    private array $_aDefaultRequiredFields = [
        'oxuser__oxfname',
        'oxuser__oxlname',
        'oxuser__oxstreetnr',
        'oxuser__oxstreet',
        'oxuser__oxzip',
        'oxuser__oxcity',
    ];

    /**
     * Required fields.
     *
     * @var array
     */
    private $_aRequiredFields = [];

    /**
     * Sets default required fields either from config or from _aDefaultRequiredFields.
     */
    public function __construct()
    {
        $this->setRequiredFields($this->_aDefaultRequiredFields);

        $aRequiredFields = \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('aMustFillFields');
        if (is_array($aRequiredFields)) {
            $this->setRequiredFields($aRequiredFields);
        }
    }

    /**
     * Sets all required fields.
     *
     * @param array $aRequiredFields
     */
    public function setRequiredFields($aRequiredFields): void
    {
        $this->_aRequiredFields = $aRequiredFields;
    }

    /**
     * Returns all required fields.
     *
     * @return array
     */
    public function getRequiredFields()
    {
        return $this->_aRequiredFields;
    }

    /**
     * Returns required fields for user address validation.
     */
    public function getBillingFields(): array
    {
        $aRequiredFields = $this->getRequiredFields();

        return $this->filterFields($aRequiredFields, 'oxuser__');
    }

    /**
     * Returns required fields for delivery address validation.
     */
    public function getDeliveryFields(): array
    {
        $aRequiredFields = $this->getRequiredFields();

        return $this->filterFields($aRequiredFields, 'oxaddress__');
    }

    /**
     * Removes delivery fields from fields list.
     *
     *
     * @return mixed[]
     */
    private function filterFields(array $aFields, string $sPrefix): array
    {
        $aAllowed = [];
        foreach ($aFields as $sKey => $sValue) {
            if (str_starts_with((string) $sValue, $sPrefix)) {
                $aAllowed[] = $aFields[$sKey];
            }
        }

        return $aAllowed;
    }
}
