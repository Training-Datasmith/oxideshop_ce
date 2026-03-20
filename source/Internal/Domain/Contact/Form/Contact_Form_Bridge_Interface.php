<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Form_Configuration_Interface;
interface Contact_Form_Bridge_Interface
{
    /**
     * @return FormInterface
     */
    public function get_contact_form();
    /**
     * @return string
     */
    public function get_contact_form_message(Form_Interface $form);
    /**
     * @return FormConfigurationInterface
     */
    public function get_contact_form_configuration();
}