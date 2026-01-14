<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Search\Event\AfterProductSearchEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequest;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchResult;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

final class AfterProductSearchEventTest extends TestCase
{
    public function testGetSearchRequest(): void
    {
        $request = new ProductSearchRequest();
        $request->setSearchQuery('test');
        $result = new ProductSearchResult([]);

        $event = new AfterProductSearchEvent($request, $result);

        $this->assertSame($request, $event->getSearchRequest());
        $this->assertSame('test', $event->getSearchRequest()->getSearchQuery());
    }

    public function testGetSearchResult(): void
    {
        $request = new ProductSearchRequest();
        $result = new ProductSearchResult(['product1', 'product2']);

        $event = new AfterProductSearchEvent($request, $result);

        $this->assertSame($result, $event->getSearchResult());
        $this->assertSame(['product1', 'product2'], $event->getSearchResult()->getProducts());
        $this->assertSame(2, $event->getSearchResult()->getTotalResults());
    }

    public function testSetSearchResult(): void
    {
        $request = new ProductSearchRequest();
        $originalResult = new ProductSearchResult(['product1']);
        $newResult = new ProductSearchResult(['product1', 'product2', 'product3']);

        $event = new AfterProductSearchEvent($request, $originalResult);
        $event->setSearchResult($newResult);

        $this->assertSame($newResult, $event->getSearchResult());
        $this->assertSame(['product1', 'product2', 'product3'], $event->getSearchResult()->getProducts());
        $this->assertSame(3, $event->getSearchResult()->getTotalResults());
    }
}
