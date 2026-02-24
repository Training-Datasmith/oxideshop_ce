<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\Domain\Product\Search;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use OxidEsales\Eshop\Application\Model\Manufacturer;
use OxidEsales\Eshop\Application\Model\Object2Category;
use OxidEsales\Eshop\Application\Model\Vendor;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchCriteria;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchResult;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchServiceInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\EqualsFilter;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Pagination;
use OxidEsales\EshopCommunity\Internal\Framework\Search\SearchTerm;
use OxidEsales\EshopCommunity\Internal\Framework\Search\SortDirection;
use OxidEsales\EshopCommunity\Internal\Framework\Search\Sorting;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;

final class ProductSearchServiceTest extends IntegrationTestCase
{
    private ProductSearchServiceInterface $service;

    private array $originalSearchCols;
    private bool $originalSearchUseAND;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = $this->get(ProductSearchServiceInterface::class);

        $config = Registry::getConfig();
        $this->originalSearchCols = $config->getConfigParam('aSearchCols') ?? [];
        $this->originalSearchUseAND = (bool) $config->getConfigParam('blSearchUseAND');

        $config->setConfigParam('aSearchCols', ['oxtitle', 'oxsearchkeys']);
        $config->setConfigParam('blSearchUseAND', false);
    }

    public function tearDown(): void
    {
        $config = Registry::getConfig();
        $config->setConfigParam('aSearchCols', $this->originalSearchCols);
        $config->setConfigParam('blSearchUseAND', $this->originalSearchUseAND);

        parent::tearDown();
    }

    public function testEmptyCriteriaReturnsEmptyResult(): void
    {
        $criteria = $this->createCriteria();

        $result = $this->service->search($criteria);

        $this->assertSame(0, $result->getTotal());
        $this->assertEmpty($result->getProductIds());
    }

    public function testSearchByTermMatchingSingleProduct(): void
    {
        $this->createProduct('_test_alpha', 'UniqueAlphaWidget');

        $result = $this->service->search($this->createCriteria('UniqueAlphaWidget'));

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_alpha'], $this->extractIds($result));
    }

    public function testSearchByTermMatchingMultipleProducts(): void
    {
        $this->createProduct('_test_multi_1', 'Gadget Pro');
        $this->createProduct('_test_multi_2', 'Gadget Lite');

        $result = $this->service->search($this->createCriteria('Gadget'));

        $this->assertSame(2, $result->getTotal());
        $this->assertEqualsCanonicalizing(
            ['_test_multi_1', '_test_multi_2'],
            $this->extractIds($result)
        );
    }

    public function testSearchByTermNoHitsReturnsEmptyResult(): void
    {
        $this->createProduct('_test_nohit', 'RealProduct');

        $result = $this->service->search($this->createCriteria('NonExistentXYZ'));

        $this->assertSame(0, $result->getTotal());
        $this->assertEmpty($result->getProductIds());
    }

    public function testSearchMatchesSearchKeysColumn(): void
    {
        $this->createProduct('_test_keys', 'PlainTitle', ['oxsearchkeys' => 'secretkeyword']);

        $result = $this->service->search($this->createCriteria('secretkeyword'));

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_keys'], $this->extractIds($result));
    }

    public function testAndSearchRequiresAllWords(): void
    {
        Registry::getConfig()->setConfigParam('blSearchUseAND', true);

        $this->createProduct('_test_and_1', 'Red Shoe');
        $this->createProduct('_test_and_2', 'Blue Shoe');

        $result = $this->service->search($this->createCriteria('Red Shoe'));

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_and_1'], $this->extractIds($result));
    }

    public function testOrSearchMatchesAnyWord(): void
    {
        Registry::getConfig()->setConfigParam('blSearchUseAND', false);

        $this->createProduct('_test_or_1', 'Red Shoe');
        $this->createProduct('_test_or_2', 'Blue Shoe');

        $result = $this->service->search($this->createCriteria('Red Shoe'));

        $this->assertSame(2, $result->getTotal());
        $this->assertEqualsCanonicalizing(
            ['_test_or_1', '_test_or_2'],
            $this->extractIds($result)
        );
    }

    public function testFilterByCategoryReturnsAssignedProducts(): void
    {
        $categoryId = '_test_cat';
        $this->createCategory($categoryId, 'Test Category');

        $this->createProduct('_test_cat_prod_1', 'CatProduct1');
        $this->createProduct('_test_cat_prod_2', 'CatProduct2');
        $this->createProduct('_test_cat_prod_3', 'Unassigned');

        $this->assignProductToCategory('_test_cat_prod_1', $categoryId);
        $this->assignProductToCategory('_test_cat_prod_2', $categoryId);

        $criteria = $this->createCriteria('', 1, 10, [
            new EqualsFilter('oxcatnid', $categoryId),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(2, $result->getTotal());
        $this->assertEqualsCanonicalizing(
            ['_test_cat_prod_1', '_test_cat_prod_2'],
            $this->extractIds($result)
        );
    }

    public function testFilterByNonExistentCategoryReturnsEmptyResult(): void
    {
        $criteria = $this->createCriteria('', 1, 10, [
            new EqualsFilter('oxcatnid', 'nonexistent_category_id'),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(0, $result->getTotal());
        $this->assertEmpty($result->getProductIds());
    }

    public function testFilterByVendorReturnsVendorProducts(): void
    {
        $vendorId = '_test_vendor';
        $this->createVendor($vendorId, 'Test Vendor');

        $this->createProduct('_test_vend_prod', 'VendorProduct', ['oxvendorid' => $vendorId]);
        $this->createProduct('_test_other_prod', 'OtherProduct');

        $criteria = $this->createCriteria('', 1, 10, [
            new EqualsFilter('oxvendorid', $vendorId),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_vend_prod'], $this->extractIds($result));
    }

    public function testFilterByNonExistentVendorReturnsEmptyResult(): void
    {
        $criteria = $this->createCriteria('', 1, 10, [
            new EqualsFilter('oxvendorid', 'nonexistent_vendor_id'),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(0, $result->getTotal());
        $this->assertEmpty($result->getProductIds());
    }

    public function testFilterByManufacturerReturnsManufacturerProducts(): void
    {
        $manufacturerId = '_test_manuf';
        $this->createManufacturer($manufacturerId, 'Test Manufacturer');

        $this->createProduct('_test_manuf_prod', 'ManufProduct', ['oxmanufacturerid' => $manufacturerId]);
        $this->createProduct('_test_other_manuf', 'OtherManufProduct');

        $criteria = $this->createCriteria('', 1, 10, [
            new EqualsFilter('oxmanufacturerid', $manufacturerId),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_manuf_prod'], $this->extractIds($result));
    }

    public function testFilterByNonExistentManufacturerReturnsEmptyResult(): void
    {
        $criteria = $this->createCriteria('', 1, 10, [
            new EqualsFilter('oxmanufacturerid', 'nonexistent_manuf_id'),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(0, $result->getTotal());
        $this->assertEmpty($result->getProductIds());
    }

    public function testSearchTermCombinedWithVendorFilter(): void
    {
        $vendorId = '_test_comb_vend';
        $this->createVendor($vendorId, 'CombVendor');

        $this->createProduct('_test_cv_1', 'Laptop Pro', ['oxvendorid' => $vendorId]);
        $this->createProduct('_test_cv_2', 'Laptop Lite');
        $this->createProduct('_test_cv_3', 'Tablet Pro', ['oxvendorid' => $vendorId]);

        $criteria = $this->createCriteria('Laptop', 1, 10, [
            new EqualsFilter('oxvendorid', $vendorId),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_cv_1'], $this->extractIds($result));
    }

    public function testSearchTermCombinedWithManufacturerFilter(): void
    {
        $manufacturerId = '_test_comb_manuf';
        $this->createManufacturer($manufacturerId, 'CombManuf');

        $this->createProduct('_test_cm_1', 'Phone Ultra', ['oxmanufacturerid' => $manufacturerId]);
        $this->createProduct('_test_cm_2', 'Phone Basic');
        $this->createProduct('_test_cm_3', 'Watch Ultra', ['oxmanufacturerid' => $manufacturerId]);

        $criteria = $this->createCriteria('Phone', 1, 10, [
            new EqualsFilter('oxmanufacturerid', $manufacturerId),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_cm_1'], $this->extractIds($result));
    }

    public function testPaginationFirstPage(): void
    {
        $this->createProduct('_test_page_a', 'PageItem Alpha');
        $this->createProduct('_test_page_b', 'PageItem Beta');
        $this->createProduct('_test_page_c', 'PageItem Gamma');

        $result = $this->service->search($this->createCriteria('PageItem', 1, 2));

        $this->assertCount(2, $result->getProductIds());
        $this->assertSame(3, $result->getTotal());
    }

    public function testPaginationSecondPage(): void
    {
        $this->createProduct('_test_pg2_a', 'PgItem Alpha');
        $this->createProduct('_test_pg2_b', 'PgItem Beta');
        $this->createProduct('_test_pg2_c', 'PgItem Gamma');

        $result = $this->service->search($this->createCriteria('PgItem', 2, 2));

        $this->assertCount(1, $result->getProductIds());
        $this->assertSame(3, $result->getTotal());
    }

    public function testSortByTitleAscending(): void
    {
        $this->createProduct('_test_sort_c', 'Coral Widget');
        $this->createProduct('_test_sort_a', 'Amber Widget');
        $this->createProduct('_test_sort_b', 'Bronze Widget');

        $criteria = $this->createCriteria('Widget', 1, 10, [], [
            new Sorting('oxtitle', SortDirection::Asc),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(3, $result->getTotal());
        $this->assertSame(
            ['_test_sort_a', '_test_sort_b', '_test_sort_c'],
            $this->extractIds($result)
        );
    }

    public function testSortByTitleDescending(): void
    {
        $this->createProduct('_test_sortd_c', 'Coral Gizmo');
        $this->createProduct('_test_sortd_a', 'Amber Gizmo');
        $this->createProduct('_test_sortd_b', 'Bronze Gizmo');

        $criteria = $this->createCriteria('Gizmo', 1, 10, [], [
            new Sorting('oxtitle', SortDirection::Desc),
        ]);

        $result = $this->service->search($criteria);

        $this->assertSame(3, $result->getTotal());
        $this->assertSame(
            ['_test_sortd_c', '_test_sortd_b', '_test_sortd_a'],
            $this->extractIds($result)
        );
    }

    public function testNonSearchableProductIsExcluded(): void
    {
        $this->createProduct('_test_nosearch', 'Invisible Item', ['oxissearch' => 0]);
        $this->createProduct('_test_searchable', 'Invisible Item');

        $result = $this->service->search($this->createCriteria('Invisible Item'));

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_searchable'], $this->extractIds($result));
    }

    public function testVariantProductIsExcluded(): void
    {
        $this->createProduct('_test_parent', 'Variant Parent');
        $this->createProduct('_test_variant', 'Variant Parent', ['oxparentid' => '_test_parent']);

        $result = $this->service->search($this->createCriteria('Variant Parent'));

        $this->assertSame(1, $result->getTotal());
        $this->assertEqualsCanonicalizing(['_test_parent'], $this->extractIds($result));
    }

    public function testTotalCountReflectsFullMatchingSet(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createProduct("_test_total_{$i}", "TotalCountItem {$i}");
        }

        $result = $this->service->search($this->createCriteria('TotalCountItem', 1, 2));

        $this->assertCount(2, $result->getProductIds());
        $this->assertSame(5, $result->getTotal());
    }

    private function createCriteria(
        string $searchTerm = '',
        int $page = 1,
        int $limit = 10,
        array $filters = [],
        array $sorting = []
    ): ProductSearchCriteria {
        $term = $searchTerm === '' ? SearchTerm::empty() : new SearchTerm($searchTerm);

        return new ProductSearchCriteria(
            Pagination::fromPage($page, $limit),
            $term,
            $filters,
            $sorting,
        );
    }

    private function createProduct(string $id, string $title, array $extraFields = []): void
    {
        $product = oxNew(Article::class);
        $product->setId($id);
        $product->assign([
            'oxtitle' => $title,
            'oxprice' => 10.0,
            'oxissearch' => $extraFields['oxissearch'] ?? 1,
            'oxparentid' => $extraFields['oxparentid'] ?? '',
            'oxvendorid' => $extraFields['oxvendorid'] ?? '',
            'oxmanufacturerid' => $extraFields['oxmanufacturerid'] ?? '',
            'oxsearchkeys' => $extraFields['oxsearchkeys'] ?? '',
            'oxactive' => 1,
            'oxstock' => 10,
            'oxstockflag' => 1,
            'oxshopid' => 1,
            'oxlowstockactive' => 0,
        ]);
        $product->save();
    }

    private function createCategory(string $id, string $title): void
    {
        $category = oxNew(Category::class);
        $category->setId($id);
        $category->assign([
            'oxtitle' => $title,
            'oxactive' => 1,
            'oxhidden' => 0,
            'oxparentid' => 'oxrootid',
        ]);
        $category->save();
    }

    private function assignProductToCategory(string $productId, string $categoryId): void
    {
        $relation = oxNew(Object2Category::class);
        $relation->assign([
            'oxobjectid' => $productId,
            'oxcatnid' => $categoryId,
        ]);
        $relation->save();
    }

    private function createVendor(string $id, string $title): void
    {
        $vendor = oxNew(Vendor::class);
        $vendor->setId($id);
        $vendor->assign([
            'oxtitle' => $title,
            'oxactive' => 1,
        ]);
        $vendor->save();
    }

    private function createManufacturer(string $id, string $title): void
    {
        $manufacturer = oxNew(Manufacturer::class);
        $manufacturer->setId($id);
        $manufacturer->assign([
            'oxtitle' => $title,
            'oxactive' => 1,
        ]);
        $manufacturer->save();
    }

    private function extractIds(ProductSearchResult $result): array
    {
        return array_map(
            static fn ($id) => (string) $id,
            $result->getProductIds()
        );
    }
}
