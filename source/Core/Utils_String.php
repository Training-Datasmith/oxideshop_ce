<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Str;
/**
 * String manipulation class
 */
class Utils_String
{
    /**
     * Prepares passed string for CSV format
     *
     * @param string $sInField String to prepare
     */
    public function prepare_csv_field(string $s_in_field): string
    {
        $o_str = Str::get_str();
        if ($o_str->strstr($s_in_field, '"')) {
            return '"' . str_replace('"', '""', $s_in_field) . '"';
        }
        if ($o_str->strstr($s_in_field, ';')) {
            return '"' . $s_in_field . '"';
        }
        return $s_in_field;
    }
    /**
     * shortens a string to a size $iLenght, multiple spaces are removed
     * and leading and ending whitespaces are removed. If string ends with "," then
     * "," is removed from string end
     *
     * @param string $sString input string
     * @param int    $iLength maximum length of result string , -1 -> no truncation
     *
     * @return string a string of maximum length $iLength without multiple spaces and commas
     */
    public function minimize_truncate_string($s_string, $i_length)
    {
        //leading and ending whitespaces
        $s_string = trim($s_string);
        $o_str = Str::get_str();
        //multiple whitespaces
        $s_string = $o_str->preg_replace("/[ \t\n\r]+/", ' ', $s_string);
        if ($o_str->strlen($s_string) > $i_length && $i_length != -1) {
            $s_string = $o_str->substr($s_string, 0, $i_length);
        }
        return $o_str->preg_replace('/,+$/', '', $s_string);
    }
    /**
     * Prepares and returns string for search engines.
     *
     * @param string $sSearchStr String to prepare for search engines
     *
     * @return string
     */
    public function prepare_str_for_search($s_search_str)
    {
        $o_str = Str::get_str();
        if ($o_str->has_special_chars($s_search_str)) {
            return $o_str->recode_entities($s_search_str, true, ['&amp;'], ['&']);
        }
        return '';
    }
}