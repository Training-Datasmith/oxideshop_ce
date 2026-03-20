<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Str;
/**
 * Variant selection lists manager class
 */
class Variant_Select_List implements \Oxid_Esales\Eshop\Core\Contract\I_Select_List
{
    /**
     * Variant selection list label
     */
    protected string $_s_label;
    /**
     * List with selections
     *
     * @var array
     */
    protected $_a_list = [];
    /**
     * Active variant selection object
     *
     * @var \OxidEsales\Eshop\Application\Model\Selection
     */
    protected $_o_active_selection;
    /**
     * Builds current selection list
     *
     * @param string $sLabel list label
     * @param int $_iIndex list index
     */
    public function __construct(
        $s_label,
        /**
         * Selection list index
         */
        protected $_i_index
    )
    {
        $this->_s_label = trim($s_label);
    }
    /**
     * Returns variant selection list label
     *
     * @return string
     */
    public function get_label()
    {
        return Str::get_str()->htmlspecialchars($this->_s_label);
    }
    /**
     * Adds given variant info to current variant selection list
     *
     * @param string $sName      selection name
     * @param string $sValue     selection value
     * @param string $blDisabled selection state - disabled/enabled
     * @param string $blActive   selection state - active/inactive
     */
    public function add_variant($s_name, $s_value, $bl_disabled, $bl_active): void
    {
        $s_name = trim($s_name);
        //#6053 Allow "0" as a valid value.
        if (!empty($s_name) || $s_name === '0') {
            $s_key = $s_value;
            // creating new
            if (!isset($this->_a_list[$s_key])) {
                $this->_a_list[$s_key] = ox_new(\Oxid_Esales\Eshop\Application\Model\Selection::class, $s_name, $s_value, $bl_disabled, $bl_active);
            } else {
                // overriding states
                if ($this->_a_list[$s_key]->is_disabled() && !$bl_disabled) {
                    $this->_a_list[$s_key]->set_disabled($bl_disabled);
                }
                if (!$this->_a_list[$s_key]->is_active() && $bl_active) {
                    $this->_a_list[$s_key]->set_active_state($bl_active);
                }
            }
            // storing active selection
            if ($this->_a_list[$s_key]->is_active()) {
                $this->_o_active_selection = $this->_a_list[$s_key];
            }
        }
    }
    /**
     * Returns active selection object
     *
     * @return \OxidEsales\Eshop\Application\Model\Selection
     */
    public function get_active_selection()
    {
        return $this->_o_active_selection;
    }
    /**
     * Returns array of oxSelection's
     *
     * @return array
     */
    public function get_selections()
    {
        return $this->_a_list;
    }
}