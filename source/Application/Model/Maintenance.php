<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Maintenance task handler. Maintenance tasks are called periodically, by cronTab (configure on your needs)
 */
class Maintenance
{
    /**
     * Executes maintenance tasks. Currently calls oxArticleList::updateUpcomingPrices()
     */
    public function execute(): void
    {
        // updating upcoming prices
        ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class)->update_upcoming_prices(true);
    }
}