<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Field_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Validator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Interface;
class Contact_Form_Email_Validator implements Form_Validator_Interface
{
    private ?array $errors = null;
    public function __construct(private readonly Email_Validator_Service_Interface $email_validator_service)
    {
    }
    /**
     * @return bool
     */
    public function is_valid(Form_Interface $form)
    {
        $is_valid = true;
        $email = $form->email;
        if ($this->is_validation_needed($email)) {
            $is_valid = $this->email_validator_service->is_email_valid($email->get_value());
            if ($is_valid !== true) {
                $this->errors[] = 'ERROR_MESSAGE_INPUT_NOVALIDEMAIL';
            }
        }
        return $is_valid;
    }
    private function is_validation_needed(Form_Field_Interface $email): bool
    {
        if ($this->is_not_empty_email($email)) {
            return true;
        }
        return $email->is_required();
    }
    private function is_not_empty_email(Form_Field_Interface $email): bool
    {
        return $email->get_value() !== '';
    }
    /**
     * @return array
     */
    public function get_errors(): ?array
    {
        return $this->errors;
    }
}