<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Form;

interface Form_Interface
{
    public function add(Form_Field_Interface $field);
    /**
     * @return array
     */
    public function get_fields();
    /**
     * @param array $request
     */
    public function handle_request($request);
    /**
     * @return bool
     */
    public function is_valid();
    /**
     * @return array
     */
    public function get_errors();
}