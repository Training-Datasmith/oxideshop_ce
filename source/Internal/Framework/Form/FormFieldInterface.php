<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

interface Form_Field_Interface
{
    /**
     * @return string
     */
    public function get_name();
    /**
     * @param string $name
     * @return FormFieldInterface
     */
    public function set_name($name);
    /**
     * @return string
     */
    public function get_value();
    /**
     * @param string $value
     * @return FormFieldInterface
     */
    public function set_value($value);
    /**
     * @return string
     */
    public function get_label();
    /**
     * @param string $label
     * @return FormFieldInterface
     */
    public function set_label($label);
    /**
     * @return bool
     */
    public function is_required();
    /**
     * @param bool $isRequired
     * @return FormFieldInterface
     */
    public function set_is_required($is_required);
}