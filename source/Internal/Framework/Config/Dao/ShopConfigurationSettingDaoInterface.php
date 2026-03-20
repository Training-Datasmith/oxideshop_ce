<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Config\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Data_Object\Shop_Configuration_Setting;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
interface Shop_Configuration_Setting_Dao_Interface
{
    public function save(Shop_Configuration_Setting $shop_configuration_setting);
    /**
     *
     * @throws EntryDoesNotExistDaoException
     */
    public function get(string $name, int $shop_id): Shop_Configuration_Setting;
    public function delete(Shop_Configuration_Setting $setting);
}