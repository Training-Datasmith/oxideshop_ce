<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Country manager
 */
class Country extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxcountry';
    /**
     * State list
     *
     * @var array
     */
    protected $_a_states;
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxcountry');
    }
    /**
     * returns true if this country is a foreign country
     *
     * @return bool
     */
    public function is_foreign_country()
    {
        return !in_array($this->get_id(), \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aHomeCountry'));
    }
    /**
     * returns true if this country is marked as EU
     *
     * @return bool
     */
    public function is_in_eu()
    {
        return $this->oxcountry__oxvatstatus->value == 1;
    }
    /**
     * Returns current state list
     *
     * @return array
     */
    public function get_states()
    {
        if (!is_null($this->_a_states)) {
            return $this->_a_states;
        }
        $s_country_id = $this->get_id();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxstates', $this->get_language());
        $s_q = "select * from {$s_view_name} where `oxcountryid` = :oxcountryid order by `oxtitle`  ";
        $this->_a_states = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $this->_a_states->init('oxstate');
        $this->_a_states->select_string($s_q, ['oxcountryid' => $s_country_id]);
        return $this->_a_states;
    }
    /**
     * Returns country id by code
     *
     * @param string $sCode country code
     *
     * @return string
     */
    public function get_id_by_code($s_code)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        return $o_db->get_one('select oxid from oxcountry where oxisoalpha2 = :oxisoalpha2', ['oxisoalpha2' => $s_code]);
    }
    /**
     * Method returns VAT identification number prefix.
     *
     * @return string
     */
    public function get_vat_identification_number_prefix()
    {
        return $this->oxcountry__oxvatinprefix->value;
    }
}