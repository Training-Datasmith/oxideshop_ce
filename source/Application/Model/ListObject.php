<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

/**
 * Simple list object
 */
class ListObject
{
    /**
     * Class constructor
     *
     * @param string $_sTableName Table name
     */
    public function __construct(private $_sTableName)
    {
    }

    /**
     * Assigns database record to object
     *
     * @param object $aData Database record
     */
    public function assign($aData): void
    {
        if (!is_array($aData)) {
            return;
        }
        foreach ($aData as $sKey => $sValue) {
            $sFieldName = strtolower($this->_sTableName . '__' . $sKey);
            $this->$sFieldName = new \OxidEsales\Eshop\Core\Field($sValue);
        }
    }

    /**
     * Returns object id
     *
     * @return int
     */
    public function getId()
    {
        $sFieldName = strtolower($this->_sTableName . '__oxid');
        return $this->$sFieldName->value;
    }
}
