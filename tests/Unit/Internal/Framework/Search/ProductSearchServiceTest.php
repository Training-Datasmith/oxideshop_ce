<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search;

use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchService;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ProductSearchServiceTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $service = new ProductSearchService(
            $this->createMock(QueryBuilderFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        $this->assertInstanceOf(ProductSearchServiceInterface::class, $service);
    }
}
