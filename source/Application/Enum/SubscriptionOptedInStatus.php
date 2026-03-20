<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Application\Enum;

enum Subscription_Opted_In_Status : int
{
    case Disabled = 0;
    case Active = 1;
    case Pending = 2;
}