<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Field;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Field_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Validator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Field_Configuration_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration_Interface;
class Contact_Form_Factory implements Form_Factory_Interface
{
    public function __construct(private readonly Form_Configuration_Interface $contact_form_configuration, private readonly Form_Validator_Interface $contact_form_email_validator, private readonly Form_Validator_Interface $required_fields_validator)
    {
    }
    /**
     * @return FormInterface
     */
    public function get_form(): \Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form
    {
        $form = new Form();
        foreach ($this->contact_form_configuration->get_field_configurations() as $field_configuration) {
            $field = $this->get_form_field($field_configuration);
            $form->add($field);
        }
        $form->add_validator($this->required_fields_validator);
        $form->add_validator($this->contact_form_email_validator);
        return $form;
    }
    /**
     * @return FormFieldInterface
     */
    private function get_form_field(Field_Configuration_Interface $field_configuration): \Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Field
    {
        $field = new Form_Field();
        $field->set_name($field_configuration->get_name())->set_label($field_configuration->get_label())->set_is_required($field_configuration->is_required());
        return $field;
    }
}