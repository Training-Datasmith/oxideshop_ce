<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Event\AfterProductSearchEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Event\BeforeProductSearchEvent;
use OxidEsales\Eshop\Core\TableViewNameGenerator;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductSearchService implements ProductSearchServiceInterface
{
    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function search(ProductSearchRequestInterface $request): ProductSearchResultInterface
    {
        $beforeEvent = new BeforeProductSearchEvent($request);
        $this->eventDispatcher->dispatch($beforeEvent);
        $request = $beforeEvent->getSearchRequest();

        $productIds = $this->executeSearch($request);

        $result = new ProductSearchResult($productIds);

        $afterEvent = new AfterProductSearchEvent($request, $result);
        $this->eventDispatcher->dispatch($afterEvent);

        return $afterEvent->getSearchResult();
    }

    private function executeSearch(ProductSearchRequestInterface $request): array
    {
        $queryBuilder = $this->buildSearchQuery($request);

        $queryBuilder->setFirstResult($request->getOffset());
        $queryBuilder->setMaxResults($request->getLimit());

        $this->applySorting($queryBuilder, $request);

        return $queryBuilder->executeQuery()->fetchFirstColumn();
    }

    private function buildSearchQuery(ProductSearchRequestInterface $request): QueryBuilder
    {
        $languageId = Registry::getLang()->getBaseLanguage();
        $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
        $articleTable = $tableViewNameGenerator->getViewName('oxarticles', $languageId);
        $o2cTable = $tableViewNameGenerator->getViewName('oxobject2category', $languageId);

        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder->select("DISTINCT $articleTable.oxid");

        $queryBuilder->from($articleTable);

        $categoryId = $request->getFilter('categoryId');
        if ($categoryId) {
            $this->applyCategoryFilter($queryBuilder, $categoryId, $articleTable, $o2cTable, $languageId);
        }

        $this->applySearchableProductConditions($queryBuilder, $articleTable);

        $vendorId = $request->getFilter('vendorId');
        if ($vendorId) {
            $queryBuilder->andWhere("$articleTable.oxvendorid = :vendorId");
            $queryBuilder->setParameter('vendorId', $vendorId);
        }

        $manufacturerId = $request->getFilter('manufacturerId');
        if ($manufacturerId) {
            $queryBuilder->andWhere("$articleTable.oxmanufacturerid = :manufacturerId");
            $queryBuilder->setParameter('manufacturerId', $manufacturerId);
        }

        $searchQuery = $request->getSearchQuery();
        if ($searchQuery) {
            $this->applySearchQuery($queryBuilder, $searchQuery, $articleTable, $languageId);
        }

        return $queryBuilder;
    }

    private function applyCategoryFilter(
        QueryBuilder $queryBuilder,
        string $categoryId,
        string $articleTable,
        string $o2cTable,
        int $languageId
    ): void {
        $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
        $categoryTable = $tableViewNameGenerator->getViewName('oxcategories', $languageId);

        $isPriceCategory = (bool) $this->queryBuilderFactory->create()
            ->select('1')
            ->from($categoryTable)
            ->where("oxid = :priceCatId")
            ->andWhere("(oxpricefrom != 0 OR oxpriceto != 0)")
            ->setParameter('priceCatId', $categoryId)
            ->executeQuery()
            ->fetchOne();

        if ($isPriceCategory) {
            $queryBuilder->leftJoin(
                $articleTable,
                $o2cTable,
                'o2c',
                "o2c.oxobjectid = $articleTable.oxid"
            );
            $queryBuilder->leftJoin(
                $articleTable,
                $categoryTable,
                'cat',
                "cat.oxid = :categoryId"
            );
            $queryBuilder->andWhere(
                sprintf(
                    '(o2c.oxcatnid = :categoryId OR (cat.oxid = :categoryId AND'
                    . ' %s.oxprice >= cat.oxpricefrom AND %s.oxprice <= cat.oxpriceto))',
                    $articleTable,
                    $articleTable
                )
            );
        } else {
            $queryBuilder->innerJoin(
                $articleTable,
                $o2cTable,
                'o2c',
                "o2c.oxobjectid = $articleTable.oxid AND o2c.oxcatnid = :categoryId"
            );
        }

        $queryBuilder->setParameter('categoryId', $categoryId);
    }

    private function applySearchableProductConditions(QueryBuilder $queryBuilder, string $articleTable): void
    {
        $queryBuilder->andWhere("$articleTable.oxactive = 1");
        $queryBuilder->andWhere("$articleTable.oxparentid = ''");
        $queryBuilder->andWhere("$articleTable.oxissearch = 1");
    }

    private function applySearchQuery(
        QueryBuilder $queryBuilder,
        string $searchQuery,
        string $articleTable,
        int $languageId
    ): void {
        $searchColumns = Registry::getConfig()->getConfigParam('aSearchCols');
        if (!is_array($searchColumns) || empty($searchColumns)) {
            return;
        }

        $useAnd = (bool) Registry::getConfig()->getConfigParam('blSearchUseAND');
        $searchTerms = explode(' ', $searchQuery);
        $searchTerms = array_filter($searchTerms, fn($term) => strlen($term) > 0);

        if (empty($searchTerms)) {
            return;
        }

        $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
        $descriptionTable = $tableViewNameGenerator->getViewName('oxartextends', $languageId);

        if (in_array('oxlongdesc', $searchColumns, true)) {
            $queryBuilder->leftJoin(
                $articleTable,
                $descriptionTable,
                'artextends',
                "$articleTable.oxid = artextends.oxid"
            );
        }

        $termConditions = [];
        $paramIndex = 0;
        $utilsString = Registry::getUtilsString();

        foreach ($searchTerms as $term) {
            $columnConditions = [];

            foreach ($searchColumns as $column) {
                $field = $column === 'oxlongdesc'
                    ? "artextends.$column"
                    : "$articleTable.$column";

                $paramName = "search_$paramIndex";
                $columnConditions[] = "$field LIKE :$paramName";
                $queryBuilder->setParameter($paramName, "%$term%");
                $paramIndex++;

                $umlTerm = $utilsString->prepareStrForSearch($term);
                if ($umlTerm) {
                    $paramName = "search_$paramIndex";
                    $columnConditions[] = "$field LIKE :$paramName";
                    $queryBuilder->setParameter($paramName, "%$umlTerm%");
                    $paramIndex++;
                }
            }

            if (!empty($columnConditions)) {
                $termConditions[] = '(' . implode(' OR ', $columnConditions) . ')';
            }
        }

        if (!empty($termConditions)) {
            $connector = $useAnd ? ' AND ' : ' OR ';
            $queryBuilder->andWhere('(' . implode($connector, $termConditions) . ')');
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, ProductSearchRequestInterface $request): void
    {
        $sorting = $request->getSorting();

        if (empty($sorting)) {
            return;
        }

        foreach ($sorting as $sort) {
            $direction = strtoupper($sort->direction) === Sorting::DESC ? Sorting::DESC : Sorting::ASC;
            $queryBuilder->addOrderBy($sort->field, $direction);
        }
    }
}
