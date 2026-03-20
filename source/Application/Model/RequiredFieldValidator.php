<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Class for validating address
 */
class Required_Field_Validator
{
    /**
     * Validates field value.
     *
     * @param string $sFieldValue Field value
     *
     * @return bool
     */
    public function validate_field_value($s_field_value)
    {
        $bl_valid = true;
        if (is_array($s_field_value)) {
            $bl_valid = $this->validate_field_value_array($s_field_value);
        } else if (!trim($s_field_value ?? '')) {
            $bl_valid = false;
        }
        return $bl_valid;
    }
    /**
     * Checks if all values are filled up
     *
     * @param array $aFieldValues field values
     *
     * @return bool
     */
    private function validate_field_value_array(array $a_field_values)
    {
        $bl_valid = true;
        foreach ($a_field_values as $s_value) {
            if (!trim((string) $s_value)) {
                $bl_valid = false;
                break;
            }
        }
        return $bl_valid;
    }
}