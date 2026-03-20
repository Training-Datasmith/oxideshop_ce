<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

interface Form_Factory_Interface
{
    /**
     * @return FormInterface
     */
    public function get_form();
}