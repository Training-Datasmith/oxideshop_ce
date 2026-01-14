<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search;

use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequest;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Sorting;
use PHPUnit\Framework\TestCase;

final class ProductSearchRequestTest extends TestCase
{
    public function testSearchQueryDefaultsToNull(): void
    {
        $request = new ProductSearchRequest();

        $this->assertNull($request->getSearchQuery());
    }

    public function testSetAndGetSearchQuery(): void
    {
        $request = new ProductSearchRequest();
        $request->setSearchQuery('test query');

        $this->assertSame('test query', $request->getSearchQuery());
    }

    public function testAddAndGetFilter(): void
    {
        $request = new ProductSearchRequest();
        $request->addFilter('categoryId', 'cat123');

        $this->assertSame('cat123', $request->getFilter('categoryId'));
    }

    public function testGetFilterReturnsNullForNonExistentFilter(): void
    {
        $request = new ProductSearchRequest();

        $this->assertNull($request->getFilter('nonexistent'));
    }

    public function testGetFiltersReturnsAllFilters(): void
    {
        $request = new ProductSearchRequest();
        $request->addFilter('categoryId', 'cat123');
        $request->addFilter('vendorId', 'vendor456');

        $expected = [
            'categoryId' => 'cat123',
            'vendorId' => 'vendor456',
        ];

        $this->assertSame($expected, $request->getFilters());
    }

    public function testSetAndGetLimit(): void
    {
        $request = new ProductSearchRequest();
        $request->setLimit(25);

        $this->assertSame(25, $request->getLimit());
    }

    public function testSetAndGetOffset(): void
    {
        $request = new ProductSearchRequest();
        $request->setOffset(50);

        $this->assertSame(50, $request->getOffset());
    }

    public function testAddMultipleSorting(): void
    {
        $request = new ProductSearchRequest();
        $sorting1 = new Sorting('oxtitle', Sorting::ASC);
        $sorting2 = new Sorting('oxprice', Sorting::DESC);
        $request->addSorting($sorting1);
        $request->addSorting($sorting2);

        $sortingList = $request->getSorting();

        $this->assertCount(2, $sortingList);
        $this->assertSame($sorting1, $sortingList[0]);
        $this->assertSame($sorting2, $sortingList[1]);
    }
}
