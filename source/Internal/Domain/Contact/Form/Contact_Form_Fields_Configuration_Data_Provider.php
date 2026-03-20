<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Fields_Configuration_Data_Provider_Interface;
class Contact_Form_Fields_Configuration_Data_Provider implements Form_Fields_Configuration_Data_Provider_Interface
{
    public function get_form_fields_configuration(): array
    {
        return [['name' => 'email', 'label' => 'EMAIL'], ['name' => 'firstName', 'label' => 'FIRST_NAME'], ['name' => 'lastName', 'label' => 'LAST_NAME'], ['name' => 'salutation', 'label' => 'TITLE'], ['name' => 'subject', 'label' => 'SUBJECT'], ['name' => 'message', 'label' => 'MESSAGE']];
    }
}