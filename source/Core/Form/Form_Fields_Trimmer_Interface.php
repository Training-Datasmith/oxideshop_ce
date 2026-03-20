<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Form;

use Oxid_Esales\Eshop\Core\Form\Form_Fields as EshopFormFields;
/**
 * Trimm FormFields.
 */
interface Form_Fields_Trimmer_Interface
{
    /**
     * Returns trimmed fields.
     */
    public function trim(Eshop_Form_Fields $form_fields);
}