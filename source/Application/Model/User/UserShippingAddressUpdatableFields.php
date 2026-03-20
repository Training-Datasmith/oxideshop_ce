<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model\User;

use Oxid_Esales\Eshop\Application\Model\Address;
use Oxid_Esales\Eshop\Core\Contract\Abstract_Updatable_Fields;
/**
 * @inheritdoc
 */
class User_Shipping_Address_Updatable_Fields extends Abstract_Updatable_Fields
{
    /**
     * UserShippingAddressUpdatableFields constructor.
     */
    public function __construct()
    {
        $address = ox_new(Address::class);
        $this->table_name = $address->get_core_table_name();
    }
    /**
     * Return list of fields which could be updated by shop customer.
     *
     * @return array
     */
    public function get_updatable_fields()
    {
        return ['OXCOMPANY', 'OXFNAME', 'OXLNAME', 'OXSTREET', 'OXSTREETNR', 'OXADDINFO', 'OXCITY', 'OXCOUNTRY', 'OXCOUNTRYID', 'OXSTATEID', 'OXZIP', 'OXFON', 'OXFAX', 'OXSAL', 'OXTIMESTAMP'];
    }
}