<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * @internal Please do not use or extend this class.
 */
class Sorting_Validator
{
    /**
     * @param string $sortBy
     * @param string $sortOrder
     */
    public function is_valid($sort_by, $sort_order): bool
    {
        if ($sort_by && $sort_order && in_array(strtolower($sort_order), $this->get_sorting_orders()) && in_array($sort_by, \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aSortCols'))) {
            return true;
        }
        return false;
    }
    public function get_sorting_orders(): array
    {
        return ['desc', 'asc'];
    }
}