<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Model;

/**
 * Helper to work with field names of a model.
 *
 * @internal Do not make a module extension for this class.
 */
class Field_Name_Helper
{
    /**
     * Return field names with and without table name as a prefix.
     *
     * @param string $tableName
     * @param array  $fieldNames
     */
    public function get_full_field_names($table_name, $field_names): array
    {
        $combined_fields = [];
        $table_prefix = strtolower($table_name) . '__';
        foreach ($field_names as $field_name) {
            $field_name = strtolower((string) $field_name);
            $field_name_without_table_name = str_replace($table_prefix, '', $field_name);
            $combined_fields[] = $field_name_without_table_name;
            if (!str_starts_with($field_name, $table_prefix)) {
                $field_name = $table_prefix . $field_name;
            }
            $combined_fields[] = $field_name;
        }
        return $combined_fields;
    }
}