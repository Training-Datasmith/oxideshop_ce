<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Locator;

/**
 * Interface NavigationFileLocatorInterface
 * @package OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator
 */
interface Navigation_File_Locator_Interface
{
    /**
     * Returns a full path for a given file name.
     *
     * @return array An array of file paths
     */
    public function locate(): array;
}