<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Transition\ShopEvents;

use OxidEsales\Eshop\Application\Component\BasketComponent;
use Symfony\Contracts\EventDispatcher\Event;

class BasketChangedEvent extends Event
{
    public function __construct(private readonly BasketComponent $basketComponent)
    {
    }

    /**
     * Getter for basket component object.
     */
    public function getBasket(): BasketComponent
    {
        return $this->basketComponent;
    }
}
