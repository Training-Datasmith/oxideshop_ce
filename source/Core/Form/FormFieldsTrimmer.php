<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Form;

use Oxid_Esales\Eshop\Core\Form\Form_Fields as EshopFormFields;
use Oxid_Esales\Eshop\Core\Form\Form_Fields_Trimmer_Interface as EshopFormFieldsTrimmerInterface;
/**
 * Trim FormFields.
 */
class Form_Fields_Trimmer implements Eshop_Form_Fields_Trimmer_Interface
{
    /**
     * Returns trimmed fields.
     *
     * @param EshopFormFields $fields to trim.
     */
    public function trim(Eshop_Form_Fields $fields): \ArrayIterator
    {
        $updatable_fields = $fields->get_updatable_fields()->get_array_copy();
        array_walk_recursive($updatable_fields, function (&$value): void {
            $value = $this->is_trimmable_field($value) ? $this->trim_field($value) : $value;
        });
        return new \ArrayIterator($updatable_fields);
    }
    /**
     * @param mixed $value
     */
    private function is_trimmable_field($value): bool
    {
        return is_string($value);
    }
    /**
     * Returns trimmed field value.
     *
     * @param   string $field
     */
    private function trim_field($field): string
    {
        return trim($field);
    }
}