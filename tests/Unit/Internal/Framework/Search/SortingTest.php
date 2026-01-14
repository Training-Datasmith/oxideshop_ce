<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Search;

use OxidEsales\EshopCommunity\Internal\Framework\Search\Sorting;
use PHPUnit\Framework\TestCase;

final class SortingTest extends TestCase
{
    public function testFieldProperty(): void
    {
        $sorting = new Sorting('oxtitle');

        $this->assertSame('oxtitle', $sorting->field);
    }

    public function testDirectionDefaultsToAsc(): void
    {
        $sorting = new Sorting('oxtitle');

        $this->assertSame(Sorting::ASC, $sorting->direction);
    }

    public function testDirectionCanBeSetToDesc(): void
    {
        $sorting = new Sorting('oxtitle', Sorting::DESC);

        $this->assertSame(Sorting::DESC, $sorting->direction);
    }

    public function testDirectionCanBeSetToAsc(): void
    {
        $sorting = new Sorting('oxtitle', Sorting::ASC);

        $this->assertSame(Sorting::ASC, $sorting->direction);
    }

    public function testAscConstantValue(): void
    {
        $this->assertSame('ASC', Sorting::ASC);
    }

    public function testDescConstantValue(): void
    {
        $this->assertSame('DESC', Sorting::DESC);
    }

    public function testSortingIsReadonly(): void
    {
        $sorting = new Sorting('oxtitle', Sorting::DESC);

        $this->assertSame('oxtitle', $sorting->field);
        $this->assertSame(Sorting::DESC, $sorting->direction);
    }
}
