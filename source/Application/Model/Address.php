<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Address handler
 */
class Address extends \Oxid_Esales\Eshop\Core\Model\Base_Model implements \Stringable
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxaddress';
    /**
     * Active address status
     *
     * @var bool
     */
    protected $_bl_selected = false;
    /**
     * @var \OxidEsales\Eshop\Application\Model\State
     */
    protected $_o_state_object;
    /**
     * Returns oxState object
     *
     * @return \OxidEsales\Eshop\Application\Model\State
     */
    protected function get_state_object()
    {
        if (is_null($this->_o_state_object)) {
            $this->_o_state_object = ox_new(\Oxid_Esales\Eshop\Application\Model\State::class);
        }
        return $this->_o_state_object;
    }
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxaddress');
    }
    /**
     * Magic getter returns address as a single line string
     */
    public function __toString(): string
    {
        return $this->to_string();
    }
    /**
     * Formats address as a single line string
     *
     * @return string
     */
    public function to_string()
    {
        $s_first_name = $this->oxaddress__oxfname->value;
        $s_last_name = $this->oxaddress__oxlname->value;
        $s_street = $this->oxaddress__oxstreet->value;
        $s_street_nr = $this->oxaddress__oxstreetnr->value;
        $s_city = $this->oxaddress__oxcity->value;
        //format it
        $s_address = '';
        if ($s_first_name || $s_last_name) {
            $s_address = $s_first_name . ($s_first_name ? ' ' : '') . "{$s_last_name}, ";
        }
        $s_address .= "{$s_street} {$s_street_nr}, {$s_city}";
        return trim($s_address);
    }
    /**
     * Returns encoded address.
     *
     * @return string
     */
    public function get_encoded_delivery_address()
    {
        return md5($this->get_merged_address_fields());
    }
    /**
     * Get state id for current address
     *
     * @return mixed
     */
    public function get_state_id()
    {
        return $this->oxaddress__oxstateid->value;
    }
    /**
     * Get state title
     *
     * @param string $sId state ID
     *
     * @return string
     */
    public function get_state_title($s_id = null)
    {
        $o_state = $this->get_state_object();
        if (is_null($s_id)) {
            $s_id = $this->get_state_id();
        }
        return $o_state->get_title_by_id($s_id);
    }
    /**
     * Returns TRUE if current address is selected
     *
     * @return bool
     */
    public function is_selected()
    {
        return $this->_bl_selected;
    }
    /**
     * Sets address state as selected
     */
    public function set_selected(): void
    {
        $this->_bl_selected = true;
    }
    /**
     * Returns merged address fields.
     *
     * @return string
     */
    protected function get_merged_address_fields()
    {
        $s_del_address = '';
        $s_del_address .= $this->oxaddress__oxcompany;
        $s_del_address .= $this->oxaddress__oxfname;
        $s_del_address .= $this->oxaddress__oxlname;
        $s_del_address .= $this->oxaddress__oxstreet;
        $s_del_address .= $this->oxaddress__oxstreetnr;
        $s_del_address .= $this->oxaddress__oxaddinfo;
        $s_del_address .= $this->oxaddress__oxcity;
        $s_del_address .= $this->oxaddress__oxcountryid;
        $s_del_address .= $this->oxaddress__oxstateid;
        $s_del_address .= $this->oxaddress__oxzip;
        $s_del_address .= $this->oxaddress__oxfon;
        $s_del_address .= $this->oxaddress__oxfax;
        return $s_del_address . $this->oxaddress__oxsal;
    }
}