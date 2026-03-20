<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Application\Enum;

enum Newsletter_Subscription_Status : int
{
    case Subscribed = 1;
    case SubscriptionConfirmed = 2;
    case Canceled = 3;
    case StayInformed = 4;
}