<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Country list manager class.
 * Collects a list of countries according to collection rules (active).
 */
class Country_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Call parent class constructor
     */
    public function __construct()
    {
        parent::__construct('oxcountry');
    }
    /**
     * Selects and loads all active countries
     *
     * @param integer $iLang language
     */
    public function load_active_countries($i_lang = null): void
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxcountry', $i_lang);
        $s_select = "SELECT oxid, oxtitle, oxisoalpha2 FROM {$s_view_name} WHERE oxactive = '1' ORDER BY oxorder, oxtitle ";
        $this->select_string($s_select);
    }
}