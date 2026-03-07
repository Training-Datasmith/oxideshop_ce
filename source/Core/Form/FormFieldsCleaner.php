<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Form;

/**
 * Return those fields which could be changed by a customer.
 */
class FormFieldsCleaner
{
    public function __construct(private readonly \OxidEsales\Eshop\Core\Form\FormFields $updatableFields)
    {
    }

    /**
     * Return only those items which exist in both lists.
     *
     * @param array $listToClean All fields.
     *
     * @return array
     */
    public function filterByUpdatableFields(array $listToClean)
    {
        $allowedFields = $this->updatableFields->getUpdatableFields();
        if ($allowedFields->count() > 0) {
            return $this->filterFieldsByWhiteList($allowedFields, $listToClean);
        }

        return $listToClean;
    }

    /**
     * Return fields by performing a case-insensitive compare.
     * Does not change original case-sensitivity of fields.
     *
     *
     */
    private function filterFieldsByWhiteList(\ArrayIterator $allowedFields, array $listToClean): array
    {
        $allowedFieldsLowerCase = array_map(strtolower(...), (array)$allowedFields);

        return array_filter($listToClean, fn ($field): bool => in_array(strtolower((string) $field), $allowedFieldsLowerCase), ARRAY_FILTER_USE_KEY);
    }
}
