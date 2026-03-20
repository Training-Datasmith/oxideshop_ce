<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Event;

use Symfony\Contracts\Event_Dispatcher\Event;
/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
class Theme_Setting_Changed_Event extends Event
{
    /**
     * @param string $theme Theme information as in oxconfig.oxmodule
     */
    public function __construct(private readonly string $configuration_variable, private readonly int $shop_id, private readonly string $theme)
    {
    }
    /**
     * Getter for configuration variable name.
     */
    public function get_configuration_variable(): string
    {
        return $this->configuration_variable;
    }
    /**
     * Getter for shop id.
     */
    public function get_shop_id(): int
    {
        return $this->shop_id;
    }
    /**
     * Getter for theme information.
     */
    public function get_theme(): string
    {
        return $this->theme;
    }
}