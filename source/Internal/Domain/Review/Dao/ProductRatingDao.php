<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Dao;

use OxidEsales\EshopCommunity\Internal\Domain\Review\DataMapper\ProductRatingDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\ProductRating;
use OxidEsales\EshopCommunity\Internal\Framework\Dao\InvalidObjectIdDaoException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class ProductRatingDao implements ProductRatingDaoInterface
{
    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
        private readonly ProductRatingDataMapperInterface $productRatingMapper
    ) {
    }

    public function update(ProductRating $productRating): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->update('oxarticles')
            ->set('OXRATING', ':OXRATING')
            ->set('OXRATINGCNT', ':OXRATINGCNT')
            ->where('OXID = :OXID')
            ->setParameters($this->productRatingMapper->getData($productRating));

        $queryBuilder->executeStatement();
    }

    /**
     * @param string $productId
     *
     * @throws InvalidObjectIdDaoException
     */
    public function getProductRatingById($productId): \OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\ProductRating
    {
        $this->validateProductId($productId);

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('OXID', 'OXRATING', 'OXRATINGCNT')
            ->from('oxarticles')
            ->where('oxid = :productId')
            ->setMaxResults(1)
            ->setParameter('productId', $productId);

        return $this->productRatingMapper->map(
            new ProductRating(),
            $queryBuilder->fetchAssociative()
        );
    }

    /**
     * @param string $productId
     *
     * @throws InvalidObjectIdDaoException
     */
    private function validateProductId($productId): void
    {
        if (empty($productId) || !is_string($productId)) {
            throw new InvalidObjectIdDaoException();
        }
    }
}
