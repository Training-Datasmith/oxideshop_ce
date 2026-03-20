<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration;

interface Form_Configuration_Interface
{
    /**
     * @return self
     */
    public function add_field_configuration(Field_Configuration_Interface $field_configuration);
    /**
     * @return array
     */
    public function get_field_configurations();
}