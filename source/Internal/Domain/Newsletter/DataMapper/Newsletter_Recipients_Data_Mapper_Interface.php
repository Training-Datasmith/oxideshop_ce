<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Object\Newsletter_Recipient;
interface Newsletter_Recipients_Data_Mapper_Interface
{
    /**
     * @param NewsletterRecipient[] $newsletterRecipient
     */
    public function map_recipient_list_data_to_array(array $newsletter_recipient): array;
}