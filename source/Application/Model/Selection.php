<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Str;
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
        protected $_s_name,
        /**
         * Selection value
         */
        protected $_s_value,
        /**
         * Selection state: disabled
         */
        protected $_bl_disabled,
        /**
         * Selection state: active
         */
        protected $_bl_active
    )
    {
    }
    /**
     * Returns selection value
     *
     * @return string
     */
    public function get_value()
    {
        return $this->_s_value;
    }
    /**
     * Returns selection name
     *
     * @return string
     */
    public function get_name()
    {
        return Str::get_str()->htmlspecialchars($this->_s_name);
    }
    /**
     * Returns TRUE if current selection is active (chosen)
     *
     * @return bool
     */
    public function is_active()
    {
        return $this->_bl_active;
    }
    /**
     * Returns TRUE if current selection is disabled
     *
     * @return bool
     */
    public function is_disabled()
    {
        return $this->_bl_disabled;
    }
    /**
     * Sets selection active/inactive
     *
     * @param bool $blActive selection state TRUE/FALSE
     */
    public function set_active_state($bl_active): void
    {
        $this->_bl_active = $bl_active;
    }
    /**
     * Sets selection disabled/enables
     *
     * @param bool $blDisabled selection state TRUE/FALSE
     */
    public function set_disabled($bl_disabled): void
    {
        $this->_bl_disabled = $bl_disabled;
    }
    /**
     * Returns selection link (currently returns "#")
     */
    public function get_link(): string
    {
        return '#';
    }
}