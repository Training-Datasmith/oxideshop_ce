<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Newsletter\Bridge;

use OxidEsales\EshopCommunity\Internal\Domain\Newsletter\Dao\NewsletterRecipientsDaoInterface;

class NewsletterRecipientsDaoBridge implements NewsletterRecipientsDaoInterface
{
    public function __construct(private readonly NewsletterRecipientsDaoInterface $newsletterRecipientsDao)
    {
    }

    public function getNewsletterRecipients(int $shopId): array
    {
        return $this->newsletterRecipientsDao->getNewsletterRecipients($shopId);
    }
}
