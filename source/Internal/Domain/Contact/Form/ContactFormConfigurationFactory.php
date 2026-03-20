<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Field_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Field_Configuration_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Fields_Configuration_Data_Provider_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Contact_Form_Configuration_Factory implements Form_Configuration_Factory_Interface
{
    public function __construct(private readonly Form_Fields_Configuration_Data_Provider_Interface $contact_form_configuration_data_provider, private readonly Context_Interface $context)
    {
    }
    /**
     * @return FormConfigurationInterface
     */
    public function get_form_configuration(): \Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration
    {
        $form_configuration = new Form_Configuration();
        $fields_configuration_data = $this->contact_form_configuration_data_provider->get_form_fields_configuration();
        foreach ($fields_configuration_data as $field_configuration_data) {
            $field_configuration = $this->get_field_configuration($field_configuration_data);
            $form_configuration->add_field_configuration($field_configuration);
        }
        return $form_configuration;
    }
    private function get_field_configuration(array $field_configuration_data): \Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Field_Configuration
    {
        $field_configuration = new Field_Configuration();
        $field_configuration->set_name($field_configuration_data['name']);
        $field_configuration->set_label($field_configuration_data['label']);
        if ($this->is_field_required($field_configuration)) {
            $field_configuration->set_is_required(true);
        }
        return $field_configuration;
    }
    private function is_field_required(Field_Configuration_Interface $field_configuration): bool
    {
        return in_array($field_configuration->get_name(), $this->context->get_required_contact_form_fields());
    }
}