<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Str;
/**
 * Select list manager
 */
class Select_List extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model implements \Oxid_Esales\Eshop\Core\Contract\I_Select_List
{
    /**
     * Select list fields array
     *
     * @var array
     */
    protected $_a_field_list;
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxselectlist';
    /**
     * Selections array
     *
     * @var array
     */
    protected $_a_list;
    /**
     * Product VAT
     *
     * @var float
     */
    protected $_d_vat;
    /**
     * Active selection object
     *
     * @var \OxidEsales\Eshop\Application\Model\Selection
     */
    protected $_o_active_selection;
    /**
     * Calls parent constructor and initializes selection list
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxselectlist');
    }
    /**
     * Returns select list value list.
     *
     * @param double $dVat VAT value
     *
     * @return array
     */
    public function get_field_list($d_vat = null)
    {
        if ($this->_a_field_list == null && $this->oxselectlist__oxvaldesc->value) {
            $this->_a_field_list = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($this->oxselectlist__oxvaldesc->value, $d_vat);
            foreach ($this->_a_field_list as $s_key => $o_field) {
                $this->_a_field_list[$s_key]->name = Str::get_str()->strip_tags($this->_a_field_list[$s_key]->name);
            }
        }
        return $this->_a_field_list;
    }
    /**
     * Removes selectlists from articles.
     *
     * @param string $sOXID object ID (default null)
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (!$s_oxid) {
            return false;
        }
        // remove selectlists from articles also
        if ($bl_remove = parent::delete($s_oxid)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $o_db->execute('delete from oxobject2selectlist where oxselnid = :oxselnid', ['oxselnid' => $s_oxid]);
        }
        return $bl_remove;
    }
    /**
     * VAT setter
     *
     * @param float $dVat product VAT
     */
    public function set_vat($d_vat): void
    {
        $this->_d_vat = $d_vat;
    }
    /**
     * Returns VAT set by oxSelectList::setVat()
     *
     * @return float
     */
    public function get_vat()
    {
        return $this->_d_vat;
    }
    /**
     * Returns variant selection list label
     *
     * @return string
     */
    public function get_label()
    {
        return $this->oxselectlist__oxtitle->value;
    }
    /**
     * Returns array of oxSelection's
     *
     * @return array
     */
    public function get_selections()
    {
        if ($this->_a_list === null && $this->oxselectlist__oxvaldesc->value) {
            $this->_a_list = false;
            $a_list = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($this->oxselectlist__oxvaldesc->get_raw_value(), $this->get_vat());
            foreach ($a_list as $s_key => $o_field) {
                if ($o_field->name) {
                    $this->_a_list[$s_key] = ox_new(\Oxid_Esales\Eshop\Application\Model\Selection::class, Str::get_str()->strip_tags($o_field->name), $s_key, false, $this->_a_list === false ? true : false);
                }
            }
        }
        return $this->_a_list;
    }
    /**
     * Returns active selection object
     *
     * @return \OxidEsales\Eshop\Application\Model\Selection
     */
    public function get_active_selection()
    {
        if ($this->_o_active_selection === null) {
            if ($a_selections = $this->get_selections()) {
                // first is allways active
                $this->_o_active_selection = reset($a_selections);
            }
        }
        return $this->_o_active_selection;
    }
    /**
     * Activates given by index selection
     *
     * @param int $iIdx selection index
     */
    public function set_active_selection_by_index($i_idx): void
    {
        if ($a_selections = $this->get_selections()) {
            $i_sel_idx = 0;
            foreach ($a_selections as $o_selection) {
                $o_selection->set_active_state($i_sel_idx == $i_idx);
                if ($i_sel_idx == $i_idx) {
                    $this->_o_active_selection = $o_selection;
                }
                $i_sel_idx++;
            }
        }
    }
}