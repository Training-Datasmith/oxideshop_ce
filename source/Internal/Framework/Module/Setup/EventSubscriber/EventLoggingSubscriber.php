<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\EventSubscriber;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Event\ServicesYamlConfigurationErrorEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventLoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function logConfigurationError(ServicesYamlConfigurationErrorEvent $event): void
    {
        $this->logger->log(
            LogLevel::ERROR,
            $event->getErrorMessage() . ' (' . $event->getConfigurationFilePath() . ')'
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServicesYamlConfigurationErrorEvent::class => 'logConfigurationError',
        ];
    }
}
