<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Newsletter\Dao;

interface NewsletterRecipientsDaoInterface
{
    public function getNewsletterRecipients(int $shopId): array;
}
