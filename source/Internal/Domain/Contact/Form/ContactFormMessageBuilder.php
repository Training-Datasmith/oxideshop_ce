<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Contact\Form;

use OxidEsales\EshopCommunity\Internal\Framework\Form\FormInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\ShopAdapterInterface;

class ContactFormMessageBuilder implements ContactFormMessageBuilderInterface
{
    public function __construct(private readonly ShopAdapterInterface $shopAdapter)
    {
    }

    public function getContent(FormInterface $form): string
    {
        $message = $this->shopAdapter->translateString('MESSAGE_FROM') . ' ';

        $salutation = $form->salutation->getValue();
        if ($salutation) {
            $message .= $this->shopAdapter->translateString($salutation) . ' ';
        }

        if ($form->firstName->getValue()) {
            $message .= $form->firstName->getValue() . ' ';
        }

        if ($form->lastName->getValue()) {
            $message .= $form->lastName->getValue() . ' ';
        }

        $message .= '(' . $form->email->getValue() . ')<br /><br />';

        if ($form->message->getValue()) {
            $message .= nl2br((string) $form->message->getValue());
        }

        return $message;
    }
}
