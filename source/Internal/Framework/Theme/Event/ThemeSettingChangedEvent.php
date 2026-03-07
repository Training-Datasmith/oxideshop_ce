<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
class ThemeSettingChangedEvent extends Event
{
    /**
     * @param string $theme Theme information as in oxconfig.oxmodule
     */
    public function __construct(
        private readonly string $configurationVariable,
        private readonly int $shopId,
        private readonly string $theme
    ) {
    }

    /**
     * Getter for configuration variable name.
     */
    public function getConfigurationVariable(): string
    {
        return $this->configurationVariable;
    }

    /**
     * Getter for shop id.
     */
    public function getShopId(): int
    {
        return $this->shopId;
    }

    /**
     * Getter for theme information.
     */
    public function getTheme(): string
    {
        return $this->theme;
    }
}
