<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Company VAT identification number (VATIN) checker
 */
abstract class Company_Vat_In_Checker
{
    /**
     * Error message
     *
     * @var string
     */
    protected $_s_error = '';
    /**
     * Error message setter
     *
     * @param string $error
     */
    public function set_error($error): void
    {
        $this->_s_error = $error;
    }
    /**
     * Error message getter
     *
     * @return string
     */
    public function get_error()
    {
        return $this->_s_error;
    }
    /**
     * Validates company VAT identification number
     *
     *
     * @return mixed
     */
    abstract public function validate(\Oxid_Esales\Eshop\Application\Model\Company_Vat_In $vat_in);
}