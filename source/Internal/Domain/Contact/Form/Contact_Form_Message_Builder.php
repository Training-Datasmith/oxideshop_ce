<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
class Contact_Form_Message_Builder implements Contact_Form_Message_Builder_Interface
{
    public function __construct(private readonly Shop_Adapter_Interface $shop_adapter)
    {
    }
    public function get_content(Form_Interface $form): string
    {
        $message = $this->shop_adapter->translate_string('MESSAGE_FROM') . ' ';
        $salutation = $form->salutation->get_value();
        if ($salutation) {
            $message .= $this->shop_adapter->translate_string($salutation) . ' ';
        }
        if ($form->first_name->get_value()) {
            $message .= $form->first_name->get_value() . ' ';
        }
        if ($form->last_name->get_value()) {
            $message .= $form->last_name->get_value() . ' ';
        }
        $message .= '(' . $form->email->get_value() . ')<br /><br />';
        if ($form->message->get_value()) {
            $message .= nl2br((string) $form->message->get_value());
        }
        return $message;
    }
}