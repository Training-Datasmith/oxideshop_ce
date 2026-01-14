<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Framework\Search;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequestFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Sorting;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;

final class ProductSearchServiceTest extends IntegrationTestCase
{
    private string $configKeySearchColumns = 'aSearchCols';
    private string $searchString = 'test-search-term';
    private string $productId1 = 'test-product-1';
    private string $productId2 = 'test-product-2';
    private string $productId3 = 'test-product-3';

    public function setUp(): void
    {
        parent::setUp();
        $this->prepareProducts();
    }

    public function testServiceIsRegisteredInContainer(): void
    {
        $service = $this->get(ProductSearchServiceInterface::class);

        $this->assertInstanceOf(ProductSearchServiceInterface::class, $service);
    }

    public function testRequestFactoryIsRegisteredInContainer(): void
    {
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $this->assertInstanceOf(ProductSearchRequestFactoryInterface::class, $factory);
    }

    public function testSearchWithNoResultsReturnsEmptyArray(): void
    {
        Registry::getConfig()->setConfigParam($this->configKeySearchColumns, ['oxtitle']);

        $service = $this->get(ProductSearchServiceInterface::class);
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $request = $factory->create();
        $request->setSearchQuery('nonexistent-query-xyz-12345');

        $result = $service->search($request);

        $this->assertSame(0, $result->getTotalResults());
    }

    public function testSearchWithMatchingResultsReturnsProductIds(): void
    {
        Registry::getConfig()->setConfigParam($this->configKeySearchColumns, ['oxtitle']);

        $service = $this->get(ProductSearchServiceInterface::class);
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $request = $factory->create();
        $request->setSearchQuery($this->searchString);

        $result = $service->search($request);

        $this->assertContains($this->productId1, $result->getProducts());
        $this->assertContains($this->productId2, $result->getProducts());
        $this->assertNotContains($this->productId3, $result->getProducts());
    }

    public function testSearchReturnsCorrectTotalCount(): void
    {
        Registry::getConfig()->setConfigParam($this->configKeySearchColumns, ['oxtitle']);

        $service = $this->get(ProductSearchServiceInterface::class);
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $request = $factory->create();
        $request->setSearchQuery($this->searchString);

        $result = $service->search($request);

        $this->assertSame(2, $result->getTotalResults());
    }

    public function testSearchWithLimitReturnsLimitedResults(): void
    {
        Registry::getConfig()->setConfigParam($this->configKeySearchColumns, ['oxtitle']);

        $service = $this->get(ProductSearchServiceInterface::class);
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $request = $factory->create();
        $request->setSearchQuery($this->searchString);
        $request->setLimit(1);

        $result = $service->search($request);

        $this->assertSame(1, $result->getTotalResults());
    }

    public function testSearchWithOffsetSkipsResults(): void
    {
        Registry::getConfig()->setConfigParam($this->configKeySearchColumns, ['oxtitle']);

        $service = $this->get(ProductSearchServiceInterface::class);
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $request = $factory->create();
        $request->setSearchQuery($this->searchString);
        $request->setLimit(10);
        $request->setOffset(1);

        $result = $service->search($request);

        $this->assertSame(1, $result->getTotalResults());
    }

    public function testSearchInMultipleColumnsFindsResults(): void
    {
        Registry::getConfig()->setConfigParam($this->configKeySearchColumns, ['oxtitle', 'oxsearchkeys']);

        $service = $this->get(ProductSearchServiceInterface::class);
        $factory = $this->get(ProductSearchRequestFactoryInterface::class);

        $request = $factory->create();
        $request->setSearchQuery('unique-search-key');

        $result = $service->search($request);

        $this->assertContains($this->productId3, $result->getProducts());
    }

    private function prepareProducts(): void
    {
        $product1 = oxNew(Article::class);
        $product1->setId($this->productId1);
        $product1->oxarticles__oxtitle = new Field($this->searchString . ' Product A');
        $product1->oxarticles__oxsearchkeys = new Field('');
        $product1->oxarticles__oxprice = new Field(10.00);
        $product1->oxarticles__oxactive = new Field(1);
        $product1->oxarticles__oxissearch = new Field(1);
        $product1->save();

        $product2 = oxNew(Article::class);
        $product2->setId($this->productId2);
        $product2->oxarticles__oxtitle = new Field($this->searchString . ' Product B');
        $product2->oxarticles__oxsearchkeys = new Field('');
        $product2->oxarticles__oxprice = new Field(20.00);
        $product2->oxarticles__oxactive = new Field(1);
        $product2->oxarticles__oxissearch = new Field(1);
        $product2->save();

        $product3 = oxNew(Article::class);
        $product3->setId($this->productId3);
        $product3->oxarticles__oxtitle = new Field('Different title');
        $product3->oxarticles__oxsearchkeys = new Field('unique-search-key');
        $product3->oxarticles__oxprice = new Field(30.00);
        $product3->oxarticles__oxactive = new Field(1);
        $product3->oxarticles__oxissearch = new Field(1);
        $product3->save();
    }
}
