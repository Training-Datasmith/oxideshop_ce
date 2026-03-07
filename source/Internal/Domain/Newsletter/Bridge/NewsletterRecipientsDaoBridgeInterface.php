<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Newsletter\Bridge;

interface NewsletterRecipientsDaoBridgeInterface
{
    public function get(int $shopId): array;
}
