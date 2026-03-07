<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Review\Dao;

use Doctrine\Common\Collections\ArrayCollection;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataMapper\RatingDataMapperInterface;
use OxidEsales\EshopCommunity\Internal\Domain\Review\DataObject\Rating;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class RatingDao implements RatingDaoInterface
{
    public function __construct(
        private readonly QueryBuilderFactoryInterface $queryBuilderFactory,
        private readonly RatingDataMapperInterface $ratingDataMapper
    ) {
    }

    /**
     * Returns User Ratings.
     *
     * @param string $userId
     */
    public function getRatingsByUserId($userId): \Doctrine\Common\Collections\ArrayCollection
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('r.*')
            ->from('oxratings', 'r')
            ->where('r.oxuserid = :userId')
            ->orderBy('r.oxtimestamp', 'DESC')
            ->setParameter('userId', $userId);

        return $this->mapRatings($queryBuilder->fetchAllAssociative());
    }

    public function delete(Rating $rating): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxratings')
            ->where('oxid = :id')
            ->setParameter('id', $rating->getId())
            ->executeStatement();
    }

    /**
     * Returns Ratings for a product.
     *
     * @param string $productId
     */
    public function getRatingsByProductId($productId): \Doctrine\Common\Collections\ArrayCollection
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('r.*')
            ->from('oxratings', 'r')
            ->where('r.oxobjectid = :productId')
            ->andWhere('r.oxtype = :productType')
            ->orderBy('r.oxtimestamp', 'DESC')
            ->setParameters(
                [
                    'productId'     => $productId,
                    'productType'   => 'oxarticle',
                ]
            );

        return $this->mapRatings($queryBuilder->fetchAllAssociative());
    }

    /**
     * Maps rating data from database to Ratings Collection.
     *
     * @param array $ratingsData
     */
    private function mapRatings($ratingsData): \Doctrine\Common\Collections\ArrayCollection
    {
        $ratings = new ArrayCollection();

        foreach ($ratingsData as $ratingData) {
            $rating = new Rating();
            $ratings->add($this->ratingDataMapper->map($rating, $ratingData));
        }

        return $ratings;
    }
}
