<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\Str;

/**
 * Variant selection container class
 */
class Selection
{
    /**
     * Initializes oxSelection object
     *
     * @param string $_sName selection name
     * @param string $_sValue selection value
     * @param string $_blDisabled selection state - disabled/enabled
     * @param string $_blActive selection state - active/inactive
     */
    public function __construct(
        /**
         * Selection name
         */
        protected $_sName,
        /**
         * Selection value
         */
        protected $_sValue,
        /**
         * Selection state: disabled
         */
        protected $_blDisabled,
        /**
         * Selection state: active
         */
        protected $_blActive
    ) {
    }

    /**
     * Returns selection value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_sValue;
    }

    /**
     * Returns selection name
     *
     * @return string
     */
    public function getName()
    {
        return Str::getStr()->htmlspecialchars($this->_sName);
    }

    /**
     * Returns TRUE if current selection is active (chosen)
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->_blActive;
    }

    /**
     * Returns TRUE if current selection is disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->_blDisabled;
    }

    /**
     * Sets selection active/inactive
     *
     * @param bool $blActive selection state TRUE/FALSE
     */
    public function setActiveState($blActive): void
    {
        $this->_blActive = $blActive;
    }

    /**
     * Sets selection disabled/enables
     *
     * @param bool $blDisabled selection state TRUE/FALSE
     */
    public function setDisabled($blDisabled): void
    {
        $this->_blDisabled = $blDisabled;
    }

    /**
     * Returns selection link (currently returns "#")
     */
    public function getLink(): string
    {
        return '#';
    }
}
