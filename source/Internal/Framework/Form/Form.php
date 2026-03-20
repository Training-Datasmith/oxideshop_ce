<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

class Form implements Form_Interface
{
    private array $fields = [];
    private array $errors = [];
    private array $validators = [];
    public function add(Form_Field_Interface $field): void
    {
        $this->fields[$field->get_name()] = $field;
    }
    /**
     * @return FormField
     */
    public function __get(string $name): mixed
    {
        return $this->fields[$name];
    }
    public function get_fields(): array
    {
        return $this->fields;
    }
    /**
     * @param array $request
     */
    public function handle_request($request): void
    {
        foreach ($request as $field_name => $value) {
            $this->{$field_name}->set_value($value);
        }
    }
    public function add_validator(Form_Validator_Interface $validator): void
    {
        $this->validators[] = $validator;
    }
    /**
     * @return bool
     */
    public function is_valid()
    {
        $is_valid = true;
        foreach ($this->validators as $validator) {
            if ($validator->is_valid($this) !== true) {
                $is_valid = false;
                $this->errors = array_merge($this->errors, $validator->get_errors());
            }
        }
        return $is_valid;
    }
    public function get_errors(): array
    {
        return $this->errors;
    }
}