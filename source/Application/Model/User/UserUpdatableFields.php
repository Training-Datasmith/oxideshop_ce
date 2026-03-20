<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model\User;

use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Core\Contract\Abstract_Updatable_Fields;
/**
 * @inheritdoc
 */
class User_Updatable_Fields extends Abstract_Updatable_Fields
{
    /**
     * UserUpdatableFields constructor.
     */
    public function __construct()
    {
        $user = ox_new(User::class);
        $this->table_name = $user->get_core_table_name();
    }
    /**
     * Return list of fields which could be updated by shop customer.
     *
     * @return array
     */
    public function get_updatable_fields()
    {
        return ['OXACTIVE', 'OXRIGHTS', 'OXSHOPID', 'OXUSERNAME', 'OXPASSWORD', 'OXPASSSALT', 'OXCUSTNR', 'OXUSTID', 'OXCOMPANY', 'OXFNAME', 'OXLNAME', 'OXSTREET', 'OXSTREETNR', 'OXADDINFO', 'OXCITY', 'OXCOUNTRYID', 'OXSTATEID', 'OXZIP', 'OXFON', 'OXFAX', 'OXSAL', 'OXCREATE', 'OXREGISTER', 'OXPRIVFON', 'OXMOBFON', 'OXBIRTHDATE', 'OXURL', 'OXUPDATEKEY', 'OXUPDATEEXP', 'OXTIMESTAMP'];
    }
}