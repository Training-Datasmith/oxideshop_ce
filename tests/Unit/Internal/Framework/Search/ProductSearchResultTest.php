<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search;

use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchResult;
use PHPUnit\Framework\TestCase;

final class ProductSearchResultTest extends TestCase
{
    public function testGetProducts(): void
    {
        $products = ['product1', 'product2', 'product3'];
        $result = new ProductSearchResult($products, 100);

        $this->assertSame($products, $result->getProducts());
    }

    public function testEmptyProducts(): void
    {
        $result = new ProductSearchResult([]);

        $this->assertSame([], $result->getProducts());
        $this->assertSame(0, $result->getTotalResults());
    }
}
