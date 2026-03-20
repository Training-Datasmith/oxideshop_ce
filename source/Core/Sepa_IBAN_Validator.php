<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
/**
 * SEPA (Single Euro Payments Area) validation class
 */
class Sepa_Iban_Validator
{
    public const IBAN_ALGORITHM_MOD_VALUE = 97;
    protected $_a_code_lengths = [];
    /**
     * International bank account number validation
     *
     * An IBAN is validated by converting it into an integer and performing a basic mod-97 operation (as described in ISO 7064) on it.
     * If the IBAN is valid, the remainder equals 1.
     *
     * @param string $sIBAN code to check
     *
     * @return bool
     */
    public function is_valid($s_iban)
    {
        $bl_valid = false;
        $s_iban = strtoupper(trim($s_iban));
        if ($this->is_length_valid($s_iban)) {
            return $this->is_algorithm_valid($s_iban);
        }
        return $bl_valid;
    }
    /**
     * Validation of IBAN registry
     *
     * @param array $aCodeLengths
     *
     * @return bool
     */
    public function is_valid_code_lengths($a_code_lengths)
    {
        if ($this->is_not_empty_array($a_code_lengths)) {
            return $this->is_each_code_length_valid($a_code_lengths);
        }
        return false;
    }
    /**
     * Set IBAN Registry
     *
     * @param array $aCodeLengths
     */
    public function set_code_lengths($a_code_lengths): bool
    {
        if ($this->is_valid_code_lengths($a_code_lengths)) {
            $this->_a_code_lengths = $a_code_lengths;
            return true;
        }
        return false;
    }
    /**
     * Get IBAN length by country data
     *
     * @return array
     */
    public function get_code_lengths()
    {
        return $this->_a_code_lengths;
    }
    /**
     * Check if the total IBAN length is correct as per country. If not, the IBAN is invalid.
     *
     * @param string $sIBAN IBAN
     */
    protected function is_length_valid($s_iban): bool
    {
        $i_actual_length = Str::get_str()->strlen($s_iban);
        $i_correct_length = $this->get_length_for_country($s_iban);
        return !is_null($i_correct_length) && $i_actual_length === $i_correct_length;
    }
    /**
     * Gets length for country.
     *
     * @param string $sIBAN IBAN
     */
    protected function get_length_for_country($s_iban)
    {
        $a_iban_registry = $this->get_code_lengths();
        $s_country_code = Str::get_str()->substr($s_iban, 0, 2);
        return $a_iban_registry[$s_country_code] ?? null;
    }
    /**
     * Checks if IBAN is valid according to checksum algorithm
     *
     * @param string $sIBAN IBAN
     */
    protected function is_algorithm_valid($s_iban): bool
    {
        $s_iban = $this->move_initial_characters_to_end($s_iban);
        $s_iban = $this->replace_letters_to_numbers($s_iban);
        return $this->is_iban_checksum_valid($s_iban);
    }
    /**
     * Move the four initial characters to the end of the string.
     *
     * @param string $sIBAN IBAN
     */
    protected function move_initial_characters_to_end($s_iban): string
    {
        $o_str = Str::get_str();
        $s_initial_chars = $o_str->substr($s_iban, 0, 4);
        $s_iban = $o_str->substr($s_iban, 4);
        return $s_iban . $s_initial_chars;
    }
    /**
     * Replace each letter in the string with two digits, thereby expanding the string, where A = 10, B = 11, ..., Z = 35.
     *
     * @param string $sIBAN IBAN
     */
    protected function replace_letters_to_numbers($s_iban): string
    {
        $a_replace_array = ['A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16, 'H' => 17, 'I' => 18, 'J' => 19, 'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23, 'O' => 24, 'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29, 'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34, 'Z' => 35];
        return str_replace(array_keys($a_replace_array), $a_replace_array, $s_iban);
    }
    /**
     * Interpret the string as a decimal integer and compute the remainder of that number on division by 97.
     *
     * @param string $sIBAN IBAN
     */
    protected function is_iban_checksum_valid($s_iban): bool
    {
        return (int) bcmod($s_iban, self::IBAN_ALGORITHM_MOD_VALUE) === 1;
    }
    /**
     * Checks if Code length is non empty array
     *
     * @param array $aCodeLengths Code lengths
     */
    protected function is_not_empty_array($a_code_lengths): bool
    {
        return is_array($a_code_lengths) && !empty($a_code_lengths);
    }
    /**
     * Checks if each code length is valid.
     *
     * @param array $aCodeLengths Code lengths
     *
     * @return bool
     */
    protected function is_each_code_length_valid($a_code_lengths)
    {
        $bl_valid = true;
        foreach ($a_code_lengths as $s_country_abbr => $i_length) {
            if (!$this->is_code_length_key_valid($s_country_abbr) || !$this->is_code_length_value_valid($i_length)) {
                $bl_valid = false;
                break;
            }
        }
        return $bl_valid;
    }
    /**
     * Checks if country code is valid
     *
     * @param string $sCountryAbbr Country abbreviation
     */
    protected function is_code_length_key_valid($s_country_abbr): bool
    {
        return (int) preg_match('/^[A-Z]{2}$/', $s_country_abbr) !== 0;
    }
    /**
     * Checks if value is numeric and does not contain whitespaces
     *
     * @param integer $iLength Length
     */
    protected function is_code_length_value_valid($i_length): bool
    {
        return is_numeric($i_length) && (int) preg_match("/\\./", $i_length) !== 1;
    }
}