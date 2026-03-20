<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Model\Address;
use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception;
use Oxid_Esales\Eshop\Core\Exception\Input_Exception;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use Oxid_Esales\Eshop\Core\Exception\User_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Bridge_Interface;
/**
 * Class for validating input.
 */
class Input_Validator extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Invalid account number error code for template.
     */
    public const INVALID_ACCOUNT_NUMBER = -5;
    /**
     * Invalid bank number error code for template.
     */
    public const INVALID_BANK_CODE = -4;
    /**
     * Input validation errors.
     *
     * @var array
     */
    protected $_a_input_validation_errors = [];
    protected $_o_company_vat_in_validator;
    /**
     * Required fields for debit cards.
     *
     * @var array
     */
    protected $_a_required_dc_fields = ['lsbankname', 'lsktonr', 'lsktoinhaber'];
    /**
     * Validates basket amount.
     *
     * @param float $amount Amount of article.
     *
     * @throws ArticleInputException If amount is not numeric or smaller 0.
     *
     * @return float
     */
    public function validate_basket_amount($amount)
    {
        $amount = str_replace(',', '.', $amount);
        if (!is_numeric($amount) || $amount < 0) {
            /**
             * @var \OxidEsales\Eshop\Core\Exception\ArticleInputException $exception
             */
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Article_Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_INVALIDAMOUNT'));
            throw $exception;
        }
        if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blAllowUnevenAmounts')) {
            return round($amount);
        }
        //negative amounts are not allowed
        //$dAmount = abs($dAmount);
        return $amount;
    }
    /**
     * Checks if user name does not break logic:
     *  - if user wants to UPDATE his login name, performing check if
     *    user entered correct password
     *  - additionally checking for user name duplicates. This is usually
     *    needed when creating new users.
     * On any error exception is thrown.
     *
     * @param User   $user       Active user.
     * @param string $login      User preferred login name.
     * @param array  $invAddress User information.
     *
     * @return string login name
     */
    public function check_login($user, $login, $inv_address)
    {
        $login = $inv_address['oxuser__oxusername'] ?? $login;
        if (isset($user->oxuser__oxpassword->value) && $user->oxuser__oxpassword->value && $login != $user->oxuser__oxusername->value) {
            $new_password = isset($inv_address['oxuser__oxpassword']) && $inv_address['oxuser__oxpassword'] ? $inv_address['oxuser__oxpassword'] : Registry::get_request()->get_request_escaped_parameter('user_password');
            if (!$new_password) {
                $message = Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_NOTALLFIELDS');
                $exception = ox_new(Input_Exception::class, $message);
                $this->add_validation_error('oxuser__oxpassword', $exception);
                return $login;
            }
            if (!$user->is_same_password($new_password)) {
                $message = Registry::get_lang()->translate_string('ERROR_MESSAGE_PASSWORD_DO_NOT_MATCH');
                $exception = ox_new(User_Exception::class, $message);
                $this->add_validation_error('oxuser__oxpassword', $exception);
                return $login;
            }
        }
        if ($user->check_if_email_exists($login)) {
            $message = Registry::get_lang()->translate_string('ERROR_MESSAGE_USER_USEREXISTS');
            $exception = ox_new(User_Exception::class, $message);
            $this->add_validation_error('oxuser__oxusername', $exception);
            return $login;
        }
        return $login;
    }
    /**
     * Checks if email (used as login) is not empty and is
     * valid.
     *
     * @param User   $user  Active user.
     * @param string $email User email/login.
     */
    public function check_email($user, $email)
    {
        // missing email address (user login name) ?
        if (empty($email)) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_NOTALLFIELDS'));
            return $this->add_validation_error('oxuser__oxusername', $exception);
        }
        $email_validator = Container_Facade::get(Email_Validator_Service_Bridge_Interface::class);
        if (!$email_validator->is_email_valid($email)) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_NOVALIDEMAIL'));
            return $this->add_validation_error('oxuser__oxusername', $exception);
        }
    }
    /**
     * Checking if user password is fine. In case of error
     * exception is thrown
     *
     * @param User   $user                      Active user.
     * @param string $newPassword               New user password.
     * @param string $confirmationPassword      Retyped user password.
     * @param bool   $shouldCheckPasswordLength Option to check password length.
     *
     * @return Exception\StandardException|null
     */
    public function check_password($user, $new_password, $confirmation_password, $should_check_password_length = false)
    {
        //  no password at all
        if ($should_check_password_length && Str::get_str()->strlen($new_password) == 0) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_EMPTYPASS'));
            return $this->add_validation_error('oxuser__oxpassword', $exception);
        }
        if ($should_check_password_length && Str::get_str()->strlen($new_password) < $this->get_password_length()) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_PASSWORD_TOO_SHORT'));
            return $this->add_validation_error('oxuser__oxpassword', $exception);
        }
        //  passwords do not match ?
        if ($new_password != $confirmation_password) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\User_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_PASSWORD_DO_NOT_MATCH'));
            return $this->add_validation_error('oxuser__oxpassword', $exception);
        }
    }
    /**
     * Min length of password.
     *
     * @return int
     */
    public function get_password_length()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iPasswordLength') ?: 6;
    }
    /**
     * Checking if all required fields were filled. In case of error
     * exception is thrown
     *
     * @param User  $user            Active user.
     * @param array $billingAddress  Billing address.
     * @param array $deliveryAddress Delivery address.
     */
    public function check_required_fields($user, $billing_address, $delivery_address): void
    {
        /** @var \OxidEsales\Eshop\Application\Model\RequiredAddressFields $requiredAddressFields */
        $required_address_fields = ox_new(\Oxid_Esales\Eshop\Application\Model\Required_Address_Fields::class);
        /** @var \OxidEsales\Eshop\Application\Model\RequiredFieldsValidator $fieldsValidator */
        $fields_validator = ox_new(\Oxid_Esales\Eshop\Application\Model\Required_Fields_Validator::class);
        /** @var User $user */
        $user = ox_new(User::class);
        $billing_address = $this->set_fields($user, $billing_address);
        $fields_validator->set_required_fields($required_address_fields->get_billing_fields());
        $fields_validator->validate_fields($billing_address);
        $invalid_fields = $fields_validator->get_invalid_fields();
        if (!empty($delivery_address)) {
            /** @var \OxidEsales\Eshop\Application\Model\Address $deliveryAddress */
            $delivery_address = $this->set_fields(ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class), $delivery_address);
            $fields_validator->set_required_fields($required_address_fields->get_delivery_fields());
            $fields_validator->validate_fields($delivery_address);
            $invalid_fields = array_merge($invalid_fields, $fields_validator->get_invalid_fields());
        }
        foreach ($invalid_fields as $s_field) {
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_NOTALLFIELDS'));
            $this->add_validation_error($s_field, $exception);
        }
    }
    /**
     * Creates oxAddress object from given array.
     *
     * @param User|Address $object
     * @param array        $fields
     *
     * @return User|Address
     */
    private function set_fields($object, $fields)
    {
        $fields = is_array($fields) ? $fields : [];
        foreach ($fields as $s_key => $s_value) {
            $object->{$s_key} = ox_new('oxField', $s_value);
        }
        return $object;
    }
    /**
     * Checks if user defined countries (billing and delivery) are active.
     *
     * @param User  $user            Active user.
     * @param array $invAddress      Billing address info.
     * @param array $deliveryAddress Delivery address info.
     */
    public function check_countries($user, $inv_address, $delivery_address): void
    {
        $billing_country = $inv_address['oxuser__oxcountryid'] ?? null;
        $delivery_country = $delivery_address['oxaddress__oxcountryid'] ?? null;
        if ($billing_country || $delivery_country) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            if ($billing_country == $delivery_country || !$billing_country && $delivery_country || $billing_country && !$delivery_country) {
                $billing_country = $billing_country ?: $delivery_country;
                $query = 'select oxactive from oxcountry where oxid = :oxbillingid';
                $params = ['oxbillingid' => $billing_country];
            } else {
                $query = 'select ( select oxactive from oxcountry where oxid = :oxbillingid ) and
                              ( select oxactive from oxcountry where oxid = :oxdeliveryid ) ';
                $params = ['oxbillingid' => $billing_country, 'oxdeliveryid' => $delivery_country];
            }
            if (!$database->get_one($query, $params)) {
                $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\User_Exception::class);
                $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('ERROR_MESSAGE_INPUT_NOTALLFIELDS'));
                $this->add_validation_error('oxuser__oxcountryid', $exception);
            }
        }
    }
    /**
     * Checks if user passed VAT id is valid. Exception is thrown
     * if id is not valid.
     *
     * @param User  $user       Active user.
     * @param array $invAddress User input array.
     */
    public function check_vat_id($user, $inv_address)
    {
        if ($this->has_required_parameters_for_vat_in_check($inv_address)) {
            $country = $this->get_country($inv_address['oxuser__oxcountryid']);
            if ($country && $country->is_in_eu()) {
                $vat_in_validator = $this->get_company_vat_in_validator($country);
                /** @var \OxidEsales\Eshop\Application\Model\CompanyVatIn $oVatIn */
                $o_vat_in = ox_new('oxCompanyVatIn', $inv_address['oxuser__oxustid']);
                if (!$vat_in_validator->validate($o_vat_in)) {
                    /** @var \OxidEsales\Eshop\Core\Exception\InputException $exception */
                    $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
                    $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('VAT_MESSAGE_' . $vat_in_validator->get_error()));
                    return $this->add_validation_error('oxuser__oxustid', $exception);
                }
            }
        } elseif (isset($inv_address['oxuser__oxustid']) && $inv_address['oxuser__oxustid'] && !$inv_address['oxuser__oxcompany']) {
            /** @var \OxidEsales\Eshop\Core\Exception\InputException $exception */
            $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Input_Exception::class);
            $exception->set_message(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('VAT_MESSAGE_COMPANY_MISSING'));
            return $this->add_validation_error('oxuser__oxcompany', $exception);
        }
    }
    /**
     * Load and return Country object.
     *
     * @param string $countryId
     *
     * @return \OxidEsales\Eshop\Application\Model\Country
     */
    protected function get_country($country_id)
    {
        $country = ox_new(\Oxid_Esales\Eshop\Application\Model\Country::class);
        $country->load($country_id);
        return $country;
    }
    /**
     * Returns error array if input validation for current field and rule reported an error
     *
     * @return array
     */
    public function get_field_validation_errors()
    {
        return $this->_a_input_validation_errors;
    }
    /**
     * Returns first user input validation error.
     *
     * @return StandardException
     */
    public function get_first_validation_error()
    {
        $a_err = reset($this->_a_input_validation_errors);
        if (is_array($a_err)) {
            return reset($a_err);
        }
    }
    /**
     * Validates payment input data debit note.
     *
     * @param string $paymentId    The payment id of current payment.
     * @param array  $dynamicValue Values of payment.
     *
     * @return bool
     */
    public function validate_payment_input_data($payment_id, &$dynamic_value)
    {
        if ($payment_id !== 'oxiddebitnote') {
            return true;
        }
        if ($this->is_all_bank_information_set($this->_a_required_dc_fields, $dynamic_value)) {
            return $this->validate_debit_note($dynamic_value);
        }
        return false;
    }
    /**
     * Used to collect user validation errors. This method is called from all of
     * the input checking functionality to report found error.
     *
     * @param string            $fieldName
     * @param StandardException $error
     *
     * @return StandardException
     */
    public function add_validation_error($field_name, $error)
    {
        return $this->_a_input_validation_errors[$field_name][] = $error;
    }
    /**
     * Validates debit note.
     *
     * @param array $debitInformation Debit information
     *
     * @return bool|int
     */
    protected function validate_debit_note($debit_information)
    {
        $debit_information = $this->clean_debit_information($debit_information);
        $bank_code = $debit_information['lsblz'];
        $account_number = $debit_information['lsktonr'];
        $sepa_validator = ox_new(Sepa_Validator::class);
        if ($sepa_validator->is_valid_bic($bank_code)) {
            $validate_result = true;
            if (!$sepa_validator->is_valid_iban($account_number)) {
                $validate_result = self::INVALID_ACCOUNT_NUMBER;
            }
        } else {
            $validate_result = self::INVALID_BANK_CODE;
            if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blSkipDebitOldBankInfo')) {
                $validate_result = $this->validate_old_debit_info($debit_information);
            }
        }
        return $validate_result;
    }
    /**
     * Validates old debit info.
     *
     * @param array $debitInfo Debit info
     *
     * @return bool|int
     */
    protected function validate_old_debit_info($debit_info)
    {
        $string_helper = Str::get_str();
        $debit_info = $this->fix_account_number($debit_info);
        $validation_result = true;
        if (!$string_helper->preg_match("/^\\d{5,8}\$/", $debit_info['lsblz'])) {
            // Bank code is invalid
            $validation_result = self::INVALID_BANK_CODE;
        }
        if (true === $validation_result && !$string_helper->preg_match("/^\\d{10,12}\$/", $debit_info['lsktonr'])) {
            // Account number is invalid
            return self::INVALID_ACCOUNT_NUMBER;
        }
        return $validation_result;
    }
    /**
     * If account number is shorter than 10, add zeros in front of number.
     *
     * @param array $debitInfo Debit info.
     *
     * @return array
     */
    protected function fix_account_number($debit_info)
    {
        $o_str = Str::get_str();
        if ($o_str->strlen($debit_info['lsktonr']) < 10) {
            $s_new_num = str_repeat('0', 10 - $o_str->strlen($debit_info['lsktonr'])) . $debit_info['lsktonr'];
            $debit_info['lsktonr'] = $s_new_num;
        }
        return $debit_info;
    }
    /**
     * Checks if all bank information is set.
     *
     * @param array $requiredFields  fields must be set.
     * @param array $bankInformation actual information.
     *
     * @return bool
     */
    protected function is_all_bank_information_set($required_fields, $bank_information)
    {
        $is_set = true;
        foreach ($required_fields as $field_name) {
            if (!isset($bank_information[$field_name]) || !trim($bank_information[$field_name])) {
                $is_set = false;
                break;
            }
        }
        return $is_set;
    }
    /**
     * Clean up spaces.
     *
     * @param array $debitInformation Debit information.
     *
     * @return mixed
     */
    protected function clean_debit_information($debit_information)
    {
        $debit_information['lsblz'] = str_replace(' ', '', $debit_information['lsblz']);
        $debit_information['lsktonr'] = str_replace(' ', '', $debit_information['lsktonr']);
        return $debit_information;
    }
    /**
     * Check if all need parameters entered.
     *
     * @param array $invAddress Address.
     *
     * @return bool
     */
    protected function has_required_parameters_for_vat_in_check($inv_address)
    {
        return isset($inv_address['oxuser__oxustid']) && $inv_address['oxuser__oxustid'] && isset($inv_address['oxuser__oxcountryid']) && $inv_address['oxuser__oxcountryid'] && isset($inv_address['oxuser__oxcompany']) && $inv_address['oxuser__oxcompany'];
    }
    /**
     * VAT IN validator setter.
     *
     * @param \OxidEsales\Eshop\Core\CompanyVatInValidator $companyVatInValidator validator
     */
    public function set_company_vat_in_validator($company_vat_in_validator): void
    {
        $this->_o_company_vat_in_validator = $company_vat_in_validator;
    }
    /**
     * Return VAT IN validator.
     *
     * @param \OxidEsales\Eshop\Application\Model\Country $country Country according which VAT id should be checked.
     *
     * @return \OxidEsales\Eshop\Core\CompanyVatInValidator
     */
    public function get_company_vat_in_validator($country)
    {
        if (is_null($this->_o_company_vat_in_validator)) {
            /** @var \OxidEsales\Eshop\Core\CompanyVatInValidator $vatInValidator */
            $vat_in_validator = ox_new('oxCompanyVatInValidator', $country);
            /** @var \OxidEsales\Eshop\Core\CompanyVatInCountryChecker $validator */
            $validator = ox_new(\Oxid_Esales\Eshop\Core\Company_Vat_In_Country_Checker::class);
            $vat_in_validator->add_checker($validator);
            /** @var \OxidEsales\Eshop\Core\OnlineVatIdCheck $onlineValidator */
            if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVatIdCheckDisabled')) {
                $online_validator = ox_new(\Oxid_Esales\Eshop\Core\Online_Vat_Id_Check::class);
                $vat_in_validator->add_checker($online_validator);
            }
            $this->set_company_vat_in_validator($vat_in_validator);
        }
        return $this->_o_company_vat_in_validator;
    }
}