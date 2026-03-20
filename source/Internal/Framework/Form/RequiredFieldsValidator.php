<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

class Required_Fields_Validator implements Form_Validator_Interface
{
    private array $errors = [];
    /**
     * @return bool
     */
    public function is_valid(Form_Interface $form)
    {
        $is_valid = true;
        foreach ($form->get_fields() as $field) {
            if ($field->is_required() === true && !$field->get_value()) {
                $this->add_error();
                $is_valid = false;
                break;
            }
        }
        return $is_valid;
    }
    public function get_errors(): array
    {
        return $this->errors;
    }
    /**
     * Add error.
     */
    private function add_error(): void
    {
        $this->errors[] = 'ERROR_MESSAGE_INPUT_NOTALLFIELDS';
    }
}