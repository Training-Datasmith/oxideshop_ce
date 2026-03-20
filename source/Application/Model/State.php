<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * State handler
 */
class State extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxstate';
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxstates');
    }
    /**
     * Returns country id by code
     *
     * @param string $sCode      country code
     * @param string $sCountryId country id
     *
     * @return string
     */
    public function get_id_by_code($s_code, $s_country_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $params = ['oxisoalpha2' => $s_code, 'oxcountryid' => $s_country_id];
        return $o_db->get_one('SELECT oxid FROM oxstates 
            WHERE oxisoalpha2 = :oxisoalpha2 
              AND oxcountryid = :oxcountryid', $params);
    }
    /**
     * Get state title by id
     *
     * @param integer|string $iStateId
     *
     * @return string
     */
    public function get_title_by_id($i_state_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_q = 'SELECT oxtitle FROM ' . $table_view_name_generator->get_view_name('oxstates') . ' 
            WHERE oxid = :oxid';
        $s_state_title = $o_db->get_one($s_q, ['oxid' => $i_state_id]);
        return (string) $s_state_title;
    }
}