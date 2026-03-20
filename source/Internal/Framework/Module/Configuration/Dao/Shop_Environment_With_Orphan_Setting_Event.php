<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Symfony\Contracts\Event_Dispatcher\Event;
class Shop_Environment_With_Orphan_Setting_Event extends Event
{
    public function __construct(
        /** @var int */
        private $shop_id,
        /** @var  string */
        private $module_id,
        /** @var string */
        private $setting_id
    )
    {
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
    public function get_module_id(): string
    {
        return $this->module_id;
    }
    public function get_setting_id(): string
    {
        return $this->setting_id;
    }
}