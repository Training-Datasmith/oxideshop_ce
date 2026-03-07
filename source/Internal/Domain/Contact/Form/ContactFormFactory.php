<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\Form;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormField;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFieldInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormValidatorInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FieldConfigurationInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;

class ContactFormFactory implements FormFactoryInterface
{
    public function __construct(
        private readonly FormConfigurationInterface $contactFormConfiguration,
        private readonly FormValidatorInterface $contactFormEmailValidator,
        private readonly FormValidatorInterface $requiredFieldsValidator
    ) {
    }

    /**
     * @return FormInterface
     */
    public function getForm(): \OxidEsales\EshopCommunity\Internal\Framework\Form\Form
    {
        $form = new Form();

        foreach ($this->contactFormConfiguration->getFieldConfigurations() as $fieldConfiguration) {
            $field = $this->getFormField($fieldConfiguration);
            $form->add($field);
        }

        $form->addValidator($this->requiredFieldsValidator);
        $form->addValidator($this->contactFormEmailValidator);

        return $form;
    }

    /**
     * @return FormFieldInterface
     */
    private function getFormField(FieldConfigurationInterface $fieldConfiguration): \OxidEsales\EshopCommunity\Internal\Framework\Form\FormField
    {
        $field = new FormField();
        $field
            ->setName($fieldConfiguration->getName())
            ->setLabel($fieldConfiguration->getLabel())
            ->setIsRequired($fieldConfiguration->isRequired());

        return $field;
    }
}
