<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\FormConfiguration\FormFieldsConfigurationDataProviderInterface;

class ContactFormFieldsConfigurationDataProvider implements FormFieldsConfigurationDataProviderInterface
{
    public function getFormFieldsConfiguration(): array
    {
        return [
            [
                'name'  => 'email',
                'label' => 'EMAIL',
            ],
            [
                'name'  => 'firstName',
                'label' => 'FIRST_NAME',
            ],
            [
                'name'  => 'lastName',
                'label' => 'LAST_NAME',
            ],
            [
                'name'  => 'salutation',
                'label' => 'TITLE',
            ],
            [
                'name'  => 'subject',
                'label' => 'SUBJECT',
            ],
            [
                'name'  => 'message',
                'label' => 'MESSAGE',
            ],
        ];
    }
}
