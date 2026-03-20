<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Form;

/**
 * Return those fields which could be changed by a customer.
 */
class Form_Fields_Cleaner
{
    public function __construct(private readonly \Oxid_Esales\Eshop\Core\Form\Form_Fields $updatable_fields)
    {
    }
    /**
     * Return only those items which exist in both lists.
     *
     * @param array $listToClean All fields.
     *
     * @return array
     */
    public function filter_by_updatable_fields(array $list_to_clean)
    {
        $allowed_fields = $this->updatable_fields->get_updatable_fields();
        if ($allowed_fields->count() > 0) {
            return $this->filter_fields_by_white_list($allowed_fields, $list_to_clean);
        }
        return $list_to_clean;
    }
    /**
     * Return fields by performing a case-insensitive compare.
     * Does not change original case-sensitivity of fields.
     *
     *
     */
    private function filter_fields_by_white_list(\ArrayIterator $allowed_fields, array $list_to_clean): array
    {
        $allowed_fields_lower_case = array_map(strtolower(...), (array) $allowed_fields);
        return array_filter($list_to_clean, fn($field): bool => in_array(strtolower((string) $field), $allowed_fields_lower_case), ARRAY_FILTER_USE_KEY);
    }
}