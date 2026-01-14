<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Search\Event\BeforeProductSearchEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequest;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequestInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

final class BeforeProductSearchEventTest extends TestCase
{
    public function testGetSearchRequest(): void
    {
        $request = new ProductSearchRequest();
        $request->setSearchQuery('test');

        $event = new BeforeProductSearchEvent($request);

        $this->assertSame($request, $event->getSearchRequest());
        $this->assertSame('test', $event->getSearchRequest()->getSearchQuery());
    }

    public function testSetSearchRequest(): void
    {
        $originalRequest = new ProductSearchRequest();
        $originalRequest->setSearchQuery('original');

        $newRequest = new ProductSearchRequest();
        $newRequest->setSearchQuery('modified');

        $event = new BeforeProductSearchEvent($originalRequest);
        $event->setSearchRequest($newRequest);

        $this->assertSame($newRequest, $event->getSearchRequest());
        $this->assertSame('modified', $event->getSearchRequest()->getSearchQuery());
    }

    public function testSearchRequestCanBeModifiedInPlace(): void
    {
        $request = new ProductSearchRequest();
        $request->setSearchQuery('original');

        $event = new BeforeProductSearchEvent($request);
        $event->getSearchRequest()->addFilter('categoryId', 'cat123');

        $this->assertSame('cat123', $event->getSearchRequest()->getFilter('categoryId'));
    }
}
