<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Dao;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchCriteria;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchResult;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Id;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Pagination;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

readonly class ProductSearchDao implements ProductSearchDaoInterface
{
    public function __construct(
        private QueryBuilderFactoryInterface $queryBuilderFactory,
        private ShopAdapterInterface $shopAdapter,
        private ContextInterface $context,
    ) {
    }

    public function findProducts(
        ProductSearchCriteria $criteria,
        int $language,
        array $searchColumns,
        bool $useAndLogic,
    ): ProductSearchResult {
        $queryBuilder = $this->buildQuery($criteria, $language, $searchColumns, $useAndLogic);

        $countQuery = clone $queryBuilder;
        $countQuery->select('COUNT(*)');
        $total = (int) $countQuery->executeQuery()->fetchOne();
        if ($total === 0) {
            return new ProductSearchResult([], 0);
        }

        $this->applySorting($queryBuilder, $criteria->getSorting());
        $this->applyPagination($queryBuilder, $criteria->getPagination());

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();
        $productIds = [];
        foreach ($rows as $row) {
            $productIds[] = Id::fromString((string) reset($row));
        }

        return new ProductSearchResult($productIds, $total);
    }

    /** @param list<string> $searchColumns */
    private function buildQuery(
        ProductSearchCriteria $criteria,
        int $language,
        array $searchColumns,
        bool $useAndLogic,
    ): QueryBuilder
    {
        $filters = $criteria->getFilters();

        $categoryId = null;
        $vendorId = null;
        $manufacturerId = null;

        foreach ($filters as $filter) {
            match ($filter->getField()) {
                'oxcatnid' => $categoryId = $filter->getValues()[0] ?? null,
                'oxvendorid' => $vendorId = $filter->getValues()[0] ?? null,
                'oxmanufacturerid' => $manufacturerId = $filter->getValues()[0] ?? null,
                default => null,
            };
        }

        $searchParam = $criteria->getTerm()->isEmpty() ? null : $criteria->getTerm()->getValue();

        $shopId = $this->context->getCurrentShopId();
        $articleTable = $this->shopAdapter->generateDatabaseViewName('oxarticles', $language, $shopId);

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder->select("{$articleTable}.oxid")
            ->from($articleTable);

        if ($this->shouldReturnEmpty($searchParam, $categoryId, $vendorId, $manufacturerId, $language)) {
            $queryBuilder->andWhere('1 = 0');

            return $queryBuilder;
        }

        $this->applyDescriptionJoin($queryBuilder, $articleTable, $language, $searchColumns);
        $this->applyCategoryFilter($queryBuilder, $categoryId, $articleTable, $language);

        $queryBuilder->andWhere($this->getArticleActiveSnippet($articleTable))
            ->andWhere("{$articleTable}.oxparentid = ''")
            ->andWhere("{$articleTable}.oxissearch = 1");

        if ($vendorId) {
            $queryBuilder->andWhere("{$articleTable}.oxvendorid = :vendorId")
                ->setParameter('vendorId', $vendorId);
        }

        if ($manufacturerId) {
            $queryBuilder->andWhere("{$articleTable}.oxmanufacturerid = :manufacturerId")
                ->setParameter('manufacturerId', $manufacturerId);
        }

        if ($searchParam) {
            $this->applySearchTermConditions(
                $queryBuilder,
                $searchParam,
                $articleTable,
                $language,
                $searchColumns,
                $useAndLogic
            );
        }

        return $queryBuilder;
    }

    private function shouldReturnEmpty(
        ?string $searchParam,
        ?string $categoryId,
        ?string $vendorId,
        ?string $manufacturerId,
        int $language,
    ): bool {
        if (!$searchParam && !$categoryId && !$vendorId && !$manufacturerId) {
            return true;
        }

        if ($categoryId && !$this->isActiveEntity('oxcategories', $categoryId, $language)) {
            return true;
        }

        if ($vendorId && !$this->isActiveEntity('oxvendor', $vendorId, $language)) {
            return true;
        }

        if ($manufacturerId && !$this->isActiveEntity('oxmanufacturers', $manufacturerId, $language)) {
            return true;
        }

        return false;
    }

    private function applyCategoryFilter(
        QueryBuilder $queryBuilder,
        ?string $categoryId,
        string $articleTable,
        int $language
    ): void {
        if (!$categoryId) {
            return;
        }

        $shopId = $this->context->getCurrentShopId();
        $categoryView = $this->shopAdapter->generateDatabaseViewName('oxcategories', $language, $shopId);

        $hasPriceRange = (bool) $this->queryBuilderFactory->create()
            ->select('oxid')
            ->from($categoryView)
            ->where('oxid = :catId')
            ->andWhere("oxpricefrom != 0 OR oxpriceto != 0")
            ->setParameter('catId', $categoryId)
            ->executeQuery()
            ->fetchOne();

        if ($hasPriceRange) {
            $subQuery = "SELECT {$articleTable}.oxid FROM {$articleTable}, " .
                "oxobject2category, {$categoryView} AS oxcategories " .
                "WHERE (oxobject2category.oxcatnid = :catId AND oxobject2category.oxobjectid = {$articleTable}.oxid) " .
                "OR (oxcategories.oxid = :catId AND {$articleTable}.oxprice >= oxcategories.oxpricefrom " .
                "AND {$articleTable}.oxprice <= oxcategories.oxpriceto)";

            $queryBuilder->andWhere("{$articleTable}.oxid IN ({$subQuery})")
                ->setParameter('catId', $categoryId);
        } else {
            $queryBuilder->innerJoin(
                $articleTable,
                'oxobject2category',
                'oxobject2category',
                "oxobject2category.oxobjectid = {$articleTable}.oxid"
            )
            ->andWhere('oxobject2category.oxcatnid = :catId')
            ->setParameter('catId', $categoryId);
        }
    }

    /** @param list<string> $searchColumns */
    private function applySearchTermConditions(
        QueryBuilder $queryBuilder,
        string $searchString,
        string $articleTable,
        int $language,
        array $searchColumns,
        bool $useAndLogic,
    ): void {
        if ($searchColumns === []) {
            return;
        }

        $searchWords = array_filter(explode(' ', $searchString), 'strlen');
        $wordExpressions = [];

        foreach ($searchWords as $index => $word) {
            $columnExpressions = [];

            foreach ($searchColumns as $column) {
                $searchField = $this->getSearchField($articleTable, $column, $language);
                $paramName = "search_{$index}_{$column}";

                $columnExpressions[] = "{$searchField} LIKE :{$paramName}";
                $queryBuilder->setParameter($paramName, "%{$word}%");

                $specialCharsVariant = $this->shopAdapter->prepareStrForSearch($word);
                if ($specialCharsVariant) {
                    $umlParamName = "search_uml_{$index}_{$column}";
                    $columnExpressions[] = "{$searchField} LIKE :{$umlParamName}";
                    $queryBuilder->setParameter($umlParamName, "%{$specialCharsVariant}%");
                }
            }

            $wordExpressions[] = '(' . implode(' OR ', $columnExpressions) . ')';
        }

        $separator = $useAndLogic ? ' AND ' : ' OR ';
        $queryBuilder->andWhere('(' . implode($separator, $wordExpressions) . ')');
    }

    /** @param list<string> $searchColumns */
    private function applyDescriptionJoin(
        QueryBuilder $queryBuilder,
        string $articleTable,
        int $language,
        array $searchColumns,
    ): void {
        if (in_array('oxlongdesc', $searchColumns)) {
            $shopId = $this->context->getCurrentShopId();
            $viewName = $this->shopAdapter->generateDatabaseViewName('oxartextends', $language, $shopId);

            $queryBuilder->leftJoin(
                $articleTable,
                $viewName,
                $viewName,
                "{$articleTable}.oxid = {$viewName}.oxid"
            );
        }
    }

    private function applySorting(QueryBuilder $queryBuilder, array $sorting): void
    {
        foreach ($sorting as $sort) {
            $queryBuilder->addOrderBy($sort->getField(), $sort->getDirection()->value);
        }
    }

    private function applyPagination(QueryBuilder $queryBuilder, Pagination $pagination): void
    {
        $queryBuilder->setFirstResult($pagination->getOffset())
            ->setMaxResults($pagination->getLimit());
    }

    private function getSearchField(string $table, string $field, int $language): string
    {
        if ($field === 'oxlongdesc') {
            $shopId = $this->context->getCurrentShopId();

            return $this->shopAdapter->generateDatabaseViewName('oxartextends', $language, $shopId) . ".{$field}";
        }

        return "{$table}.{$field}";
    }

    private function getArticleActiveSnippet(string $articleTable): string
    {
        $now = date('Y-m-d H:i:00');

        $activeCheck = "{$articleTable}.oxactive = 1 AND {$articleTable}.oxhidden = 0"
            . " OR ({$articleTable}.oxactivefrom < '{$now}' AND {$articleTable}.oxactiveto > '{$now}')";

        $variantActiveCheck = "art.oxactive = 1"
            . " OR (art.oxactivefrom < '{$now}' AND art.oxactiveto > '{$now}')";

        return "({$activeCheck})"
            . " AND ({$articleTable}.oxstockflag != 2 OR ({$articleTable}.oxstock + {$articleTable}.oxvarstock) > 0)"
            . " AND IF({$articleTable}.oxvarcount = 0, 1,"
            . " (SELECT 1 FROM {$articleTable} AS art"
            . " WHERE art.oxparentid = {$articleTable}.oxid"
            . " AND ({$variantActiveCheck})"
            . " AND (art.oxstockflag != 2 OR art.oxstock > 0)"
            . " LIMIT 1))";
    }

    private function isActiveEntity(string $tableName, string $id, int $language): bool
    {
        $shopId = $this->context->getCurrentShopId();
        $entityTable = $this->shopAdapter->generateDatabaseViewName($tableName, $language, $shopId);

        return (bool) $this->queryBuilderFactory->create()
            ->select('1')
            ->from($entityTable)
            ->where("oxid = :oxid")
            ->andWhere("oxactive = 1")
            ->setParameter('oxid', $id)
            ->executeQuery()
            ->fetchOne();
    }
}
