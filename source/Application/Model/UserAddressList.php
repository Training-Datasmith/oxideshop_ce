<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Class oxUserAddressList
 */
class User_Address_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Call parent class constructor
     */
    public function __construct()
    {
        parent::__construct('oxaddress');
    }
    /**
     * Selects and loads all address for particular user.
     *
     * @param string $sUserId user id
     */
    public function load($s_user_id): void
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxcountry');
        $o_base_object = $this->get_base_object();
        $s_select_fields = $o_base_object->get_select_fields();
        $s_select = "\n                SELECT {$s_select_fields}, `oxcountry`.`oxtitle` AS oxcountry\n                FROM oxaddress\n                LEFT JOIN {$s_view_name} AS oxcountry ON oxaddress.oxcountryid = oxcountry.oxid\n                WHERE oxaddress.oxuserid = :oxuserid";
        $this->select_string($s_select, ['oxuserid' => $s_user_id]);
    }
}