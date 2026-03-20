<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Event;

use Symfony\Contracts\Event_Dispatcher\Event;
/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
class Setting_Changed_Event extends Event
{
    public function __construct(private readonly string $setting_name, private readonly int $shop_id, private readonly string $module_id)
    {
    }
    public function get_setting_name(): string
    {
        return $this->setting_name;
    }
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
    public function get_module_id(): string
    {
        return $this->module_id;
    }
}