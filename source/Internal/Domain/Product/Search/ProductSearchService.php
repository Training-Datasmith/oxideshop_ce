<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Product\Search;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Dao\ProductSearchDaoInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Event\AfterProductSearchEvent;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Event\BeforeProductSearchEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class ProductSearchService implements ProductSearchServiceInterface
{
    public function __construct(
        private ProductSearchDaoInterface $productSearchDao,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function search(ProductSearchCriteria $criteria, array $context = []): ProductSearchResult
    {
        $this->eventDispatcher->dispatch(new BeforeProductSearchEvent($criteria, $context));

        $config = Registry::getConfig();
        $language = $context['language'] ?? Registry::getLang()->getBaseLanguage();
        $searchColumns = $config->getConfigParam('aSearchCols');
        $useAndLogic = (bool) $config->getConfigParam('blSearchUseAND');

        $result = $this->productSearchDao->findProducts(
            $criteria,
            $language,
            is_array($searchColumns) ? $searchColumns : [],
            $useAndLogic,
        );

        $this->eventDispatcher->dispatch(new AfterProductSearchEvent($criteria, $result, $context));

        return $result;
    }
}
