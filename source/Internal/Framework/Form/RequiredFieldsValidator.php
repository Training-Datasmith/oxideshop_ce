<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Form;

class RequiredFieldsValidator implements FormValidatorInterface
{
    private array $errors = [];

    /**
     * @return bool
     */
    public function isValid(FormInterface $form)
    {
        $isValid = true;

        foreach ($form->getFields() as $field) {
            if ($field->isRequired() === true && !$field->getValue()) {
                $this->addError();
                $isValid = false;

                break;
            }
        }

        return $isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add error.
     */
    private function addError(): void
    {
        $this->errors[] = 'ERROR_MESSAGE_INPUT_NOTALLFIELDS';
    }
}
