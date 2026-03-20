<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Company VAT identification number validator. Executes added validators on given VATIN.
 */
class Company_Vat_In_Validator
{
    /**
     * @var \OxidEsales\Eshop\Application\Model\Country
     */
    private $_o_country;
    /**
     * Array of validators (checkers)
     */
    private array $_a_checkers = [];
    /**
     * Error message
     *
     * @var string
     */
    private $_s_error = '';
    /**
     * Country setter
     */
    public function set_country(\Oxid_Esales\Eshop\Application\Model\Country $country): void
    {
        $this->_o_country = $country;
    }
    /**
     * Country getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Country
     */
    public function get_country()
    {
        return $this->_o_country;
    }
    /**
     * Error setter
     *
     * @param string $error
     */
    public function set_error($error): void
    {
        $this->_s_error = $error;
    }
    /**
     * Error getter
     *
     * @return string
     */
    public function get_error()
    {
        return $this->_s_error;
    }
    /**
     * Constructor
     */
    public function __construct(\Oxid_Esales\Eshop\Application\Model\Country $country)
    {
        $this->set_country($country);
    }
    /**
     * Adds validator
     */
    public function add_checker(\Oxid_Esales\Eshop\Core\Company_Vat_In_Checker $validator): void
    {
        $this->_a_checkers[] = $validator;
    }
    /**
     * Returns added validators
     */
    public function get_checkers(): array
    {
        return $this->_a_checkers;
    }
    /**
     * Validate company VAT identification number.
     *
     *
     * @return bool
     */
    public function validate(\Oxid_Esales\Eshop\Application\Model\Company_Vat_In $company_vat_number)
    {
        $result = false;
        $validators = $this->get_checkers();
        foreach ($validators as $validator) {
            $result = true;
            if ($validator instanceof \Oxid_Esales\Eshop\Core\Contract\I_Country_Aware) {
                $validator->set_country($this->get_country());
            }
            if (!$validator->validate($company_vat_number)) {
                $result = false;
                $this->set_error($validator->get_error());
                break;
            }
        }
        return $result;
    }
}