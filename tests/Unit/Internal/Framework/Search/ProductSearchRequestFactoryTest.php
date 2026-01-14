<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search;

use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequest;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequestFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequestInterface;
use PHPUnit\Framework\TestCase;

final class ProductSearchRequestFactoryTest extends TestCase
{
    public function testCreateReturnsProductSearchRequestInterface(): void
    {
        $factory = new ProductSearchRequestFactory();
        $request = $factory->create();

        $this->assertInstanceOf(ProductSearchRequestInterface::class, $request);
    }

    public function testCreateReturnsProductSearchRequest(): void
    {
        $factory = new ProductSearchRequestFactory();
        $request = $factory->create();

        $this->assertInstanceOf(ProductSearchRequest::class, $request);
    }
}
