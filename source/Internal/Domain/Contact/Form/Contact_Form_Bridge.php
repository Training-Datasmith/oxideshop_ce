<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration_Interface;
class Contact_Form_Bridge implements Contact_Form_Bridge_Interface
{
    public function __construct(private readonly Form_Factory_Interface $contact_form_factory, private readonly Contact_Form_Message_Builder_Interface $contact_form_message_builder, private readonly Form_Configuration_Interface $contact_form_configuration)
    {
    }
    /**
     * @return FormInterface
     */
    public function get_contact_form()
    {
        return $this->contact_form_factory->get_form();
    }
    /**
     * @return string
     */
    public function get_contact_form_message(Form_Interface $form)
    {
        return $this->contact_form_message_builder->get_content($form);
    }
    public function get_contact_form_configuration(): \Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration_Interface
    {
        return $this->contact_form_configuration;
    }
}