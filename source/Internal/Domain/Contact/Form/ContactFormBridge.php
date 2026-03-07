<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\FormFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface;

class ContactFormBridge implements ContactFormBridgeInterface
{
    public function __construct(
        private readonly FormFactoryInterface $contactFormFactory,
        private readonly ContactFormMessageBuilderInterface $contactFormMessageBuilder,
        private readonly FormConfigurationInterface $contactFormConfiguration
    ) {
    }

    /**
     * @return FormInterface
     */
    public function getContactForm()
    {
        return $this->contactFormFactory->getForm();
    }

    /**
     * @return string
     */
    public function getContactFormMessage(FormInterface $form)
    {
        return $this->contactFormMessageBuilder->getContent($form);
    }

    public function getContactFormConfiguration(): \OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormConfigurationInterface
    {
        return $this->contactFormConfiguration;
    }
}
