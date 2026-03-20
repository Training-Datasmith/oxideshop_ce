<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Dao;

interface Newsletter_Recipients_Dao_Interface
{
    public function get_newsletter_recipients(int $shop_id): array;
}