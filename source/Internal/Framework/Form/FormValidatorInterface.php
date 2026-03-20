<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

interface Form_Validator_Interface
{
    /**
     * @return bool
     */
    public function is_valid(Form_Interface $form);
    /**
     * @return array
     */
    public function get_errors();
}