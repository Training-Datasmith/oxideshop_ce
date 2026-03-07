<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Model;

/**
 * Helper to work with field names of a model.
 *
 * @internal Do not make a module extension for this class.
 */
class FieldNameHelper
{
    /**
     * Return field names with and without table name as a prefix.
     *
     * @param string $tableName
     * @param array  $fieldNames
     */
    public function getFullFieldNames($tableName, $fieldNames): array
    {
        $combinedFields = [];
        $tablePrefix = strtolower($tableName) . '__';
        foreach ($fieldNames as $fieldName) {
            $fieldName = strtolower((string) $fieldName);

            $fieldNameWithoutTableName = str_replace($tablePrefix, '', $fieldName);
            $combinedFields[] = $fieldNameWithoutTableName;

            if (!str_starts_with($fieldName, $tablePrefix)) {
                $fieldName = $tablePrefix . $fieldName;
            }

            $combinedFields[] = $fieldName;
        }

        return $combinedFields;
    }
}
