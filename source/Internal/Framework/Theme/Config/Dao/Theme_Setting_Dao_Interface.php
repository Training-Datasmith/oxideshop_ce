<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Config\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Config\Data_Object\Theme_Setting;
interface Theme_Setting_Dao_Interface
{
    public function save(Theme_Setting $setting): void;
    /**
     * @throws EntryDoesNotExistDaoException
     */
    public function get(string $name, int $shop_id, string $theme_id): Theme_Setting;
    public function delete(Theme_Setting $setting): void;
}