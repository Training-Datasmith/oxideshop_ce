<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration;

interface Form_Fields_Configuration_Data_Provider_Interface
{
    /**
     * @return array
     */
    public function get_form_fields_configuration();
}