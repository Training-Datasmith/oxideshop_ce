<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Mailing\Factory;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

readonly class TransportFactory implements TransportFactoryInterface
{
    public function __construct(private string $dsn)
    {
    }

    public function create(): TransportInterface
    {
        return Transport::fromDsn($this->dsn);
    }
}
