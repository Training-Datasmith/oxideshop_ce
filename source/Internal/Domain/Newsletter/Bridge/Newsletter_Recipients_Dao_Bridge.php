<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Dao\Newsletter_Recipients_Dao_Interface;
class Newsletter_Recipients_Dao_Bridge implements Newsletter_Recipients_Dao_Interface
{
    public function __construct(private readonly Newsletter_Recipients_Dao_Interface $newsletter_recipients_dao)
    {
    }
    public function get_newsletter_recipients(int $shop_id): array
    {
        return $this->newsletter_recipients_dao->get_newsletter_recipients($shop_id);
    }
}