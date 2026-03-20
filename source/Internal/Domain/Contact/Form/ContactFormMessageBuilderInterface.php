<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form;

use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Interface;
interface Contact_Form_Message_Builder_Interface
{
    /**
     * @return string
     */
    public function get_content(Form_Interface $form);
}