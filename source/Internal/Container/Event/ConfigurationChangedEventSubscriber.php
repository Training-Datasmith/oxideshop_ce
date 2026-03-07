<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Container\Event;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\DIContainer\Event\ProjectYamlChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigurationChangedEventSubscriber implements EventSubscriberInterface
{
    public function resetContainer(ProjectYamlChangedEvent $event): void
    {
        ContainerFactory::resetContainer();
    }

    public static function getSubscribedEvents(): array
    {
        return [ProjectYamlChangedEvent::class => 'resetContainer'];
    }
}
