<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration;

interface Field_Configuration_Interface
{
    /**
     * @return string
     */
    public function get_name();
    /**
     * @param string $name
     * @return FieldConfigurationInterface
     */
    public function set_name($name);
    /**
     * @return string
     */
    public function get_label();
    /**
     * @param string $label
     * @return FieldConfigurationInterface
     */
    public function set_label($label);
    /**
     * @return bool
     */
    public function is_required();
    /**
     * @param bool $isRequired
     * @return FieldConfigurationInterface
     */
    public function set_is_required($is_required);
}