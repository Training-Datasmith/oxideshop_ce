<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Legacy\Application\Controller;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\EshopCommunity\Application\Controller\SearchController;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;

final class SearchControllerTest extends IntegrationTestCase
{
    private array $originalSearchCols;
    private bool $originalSearchUseAND;

    public function setUp(): void
    {
        parent::setUp();

        $config = Registry::getConfig();
        $this->originalSearchCols = $config->getConfigParam('aSearchCols') ?? [];
        $this->originalSearchUseAND = (bool) $config->getConfigParam('blSearchUseAND');

        $config->setConfigParam('aSearchCols', ['oxtitle', 'oxsearchkeys']);
        $config->setConfigParam('blSearchUseAND', false);

        $this->createProduct('_test_product_1', 'Wireless Keyboard');
        $this->createProduct('_test_product_2', 'Wireless Mouse');
    }

    public function tearDown(): void
    {
        unset($_POST['searchparam']);

        $config = Registry::getConfig();
        $config->setConfigParam('aSearchCols', $this->originalSearchCols);
        $config->setConfigParam('blSearchUseAND', $this->originalSearchUseAND);

        parent::tearDown();
    }

    public function testSearchAnd(): void
    {
        Registry::getConfig()->setConfigParam('blSearchUseAND', true);

        $this->setRequestParameter('searchparam', 'Keyboard Mouse');

        $searchController = oxNew(SearchController::class);
        $searchController->init();

        $this->assertEquals(0, $searchController->getArticleList()->count());

        $this->setRequestParameter('searchparam', 'Keyboard');
        $searchController->init();

        $articleList = $searchController->getArticleList();

        $this->assertEquals(1, $articleList->count());
        $this->assertEquals('_test_product_1', $articleList->current()->getId());
    }

    public function testSearchOr(): void
    {
        Registry::getConfig()->setConfigParam('blSearchUseAND', false);

        $this->setRequestParameter('searchparam', 'Keyboard Mouse');

        $searchController = oxNew(SearchController::class);
        $searchController->init();

        $articleList = $searchController->getArticleList();
        $this->assertEquals(2, $articleList->count());

        $articleArray = $articleList->getArray();
        $this->assertArrayHasKey('_test_product_1', $articleArray);
        $this->assertArrayHasKey('_test_product_2', $articleArray);
    }

    private function createProduct(string $id, string $title): void
    {
        $product = oxNew(Article::class);
        $product->setId($id);
        $product->assign([
            'oxtitle' => $title,
            'oxsearchkeys' => $title,
            'oxprice' => 10.0,
            'oxissearch' => 1,
            'oxparentid' => '',
            'oxactive' => 1,
            'oxstock' => 10,
            'oxstockflag' => 1,
            'oxshopid' => 1,
            'oxlowstockactive' => 0,
        ]);
        $product->save();
    }

    private function setRequestParameter(string $key, string $value): void
    {
        $_POST[$key] = $value;
    }
}
