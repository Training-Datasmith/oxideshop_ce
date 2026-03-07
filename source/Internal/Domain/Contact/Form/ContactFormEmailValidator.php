<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFieldInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Utility\Email\EmailValidatorServiceInterface;

class ContactFormEmailValidator implements FormValidatorInterface
{
    private ?array $errors = null;

    public function __construct(private readonly EmailValidatorServiceInterface $emailValidatorService)
    {
    }

    /**
     * @return bool
     */
    public function isValid(FormInterface $form)
    {
        $isValid = true;
        $email = $form->email;

        if ($this->isValidationNeeded($email)) {
            $isValid = $this
                ->emailValidatorService
                ->isEmailValid($email->getValue());

            if ($isValid !== true) {
                $this->errors[] = 'ERROR_MESSAGE_INPUT_NOVALIDEMAIL';
            }
        }

        return $isValid;
    }

    private function isValidationNeeded(FormFieldInterface $email): bool
    {
        if ($this->isNotEmptyEmail($email)) {
            return true;
        }
        return $email->isRequired();
    }

    private function isNotEmptyEmail(FormFieldInterface $email): bool
    {
        return $email->getValue() !== '';
    }

    /**
     * @return array
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
