<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class dealing with regular string handling
 */
class Str_Regular
{
    /**
     * The character encoding.
     *
     * @var string
     */
    protected $_s_encoding = 'ISO8859-15';
    /**
     * Language specific characters (currently german; storen in octal form)
     *
     * @var array
     */
    protected $_a_umls = ["\xe4", "\xf6", "\xfc", "\xc4", "\xd6", "\xdc", "\xdf"];
    /**
     * oxUtilsString::$_aUmls equivalent in entities form
     *
     * @var array
     */
    protected $_a_uml_entities = ['&auml;', '&ouml;', '&uuml;', '&Auml;', '&Ouml;', '&Uuml;', '&szlig;'];
    /**
     * PHP strlen() function wrapper
     *
     * @param string $sStr string to measure its length
     */
    public function strlen($s_str): int
    {
        return strlen($s_str);
    }
    /**
     * PHP substr() function wrapper
     *
     * @param string $sStr    value to truncate
     * @param int    $iStart  start position
     * @param int    $iLength length
     */
    public function substr($s_str, $i_start, $i_length = null): string
    {
        if (is_null($i_length)) {
            return substr($s_str, $i_start);
        }
        return substr($s_str, $i_start, $i_length);
    }
    /**
     * PHP strpos() function wrapper
     *
     * @param string $sHaystack value to search in
     * @param string $sNeedle   value to search for
     * @param int    $iOffset   initial search position
     */
    public function strpos($s_haystack, $s_needle, $i_offset = null): int|false
    {
        $i_pos = false;
        if ($s_haystack && $s_needle) {
            if (is_null($i_offset)) {
                $i_pos = strpos($s_haystack, $s_needle);
            } else {
                $i_pos = strpos($s_haystack, $s_needle, $i_offset);
            }
        }
        return $i_pos;
    }
    /**
     * PHP strstr() function wrapper
     *
     * @param string $sHaystack string searching in
     * @param string $sNeedle   string to search
     */
    public function strstr($s_haystack, $s_needle): string|false
    {
        return strstr($s_haystack, $s_needle);
    }
    /**
     * PHP multi byte compliant strtolower() function wrapper
     *
     * @param string $sString string being lower cased
     */
    public function strtolower($s_string): string
    {
        return strtolower($s_string);
    }
    /**
     * PHP strtolower() function wrapper
     *
     * @param string $sString string being lower cased
     */
    public function strtoupper($s_string): string
    {
        return strtoupper($s_string);
    }
    /**
     * PHP htmlspecialchars() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
     */
    public function htmlspecialchars($s_string, $i_quot_style = ENT_QUOTES): string
    {
        return htmlspecialchars($s_string, $i_quot_style, $this->_s_encoding);
    }
    /**
     * PHP htmlentities() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
     */
    public function htmlentities($s_string, $i_quot_style = ENT_QUOTES): string
    {
        return htmlentities($s_string, $i_quot_style, $this->_s_encoding);
    }
    /**
     * PHP html_entity_decode() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function html_entity_decode($s_string, $i_quot_style = ENT_QUOTES): string
    {
        return html_entity_decode($s_string, $i_quot_style, $this->_s_encoding);
    }
    /**
     * PHP preg_split() function wrapper
     *
     * @param string $sPattern pattern to search for, as a string
     * @param string $sString  input string
     * @param int    $iLimit   (optional) only substrings up to limit are returned
     * @param int    $iFlag    flags
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     *
     * @return string
     */
    public function preg_split($s_pattern, $s_string, $i_limit = -1, $i_flag = 0)
    {
        return preg_split($s_pattern, $s_string, $i_limit, $i_flag);
    }
    /**
     * PHP preg_replace() function wrapper
     *
     * @param mixed  $sPattern pattern to search for, as a string
     * @param mixed  $sString  string to replace
     * @param string $sSubject strings to search and replace
     * @param int    $iLimit   maximum possible replacements
     * @param int    $iCount   number of replacements done
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     *
     * @return string
     */
    public function preg_replace($s_pattern, $s_string, $s_subject, $i_limit = -1, $i_count = null): ?string
    {
        return preg_replace($s_pattern, (string) $s_string, $s_subject, $i_limit, $i_count);
    }
    /**
     * PHP preg_replace() function wrapper
     *
     * @param mixed    $pattern  pattern to search for, as a string
     * @param callable $callback Callback function
     * @param string   $subject  strings to search and replace
     * @param int      $limit    maximum possible replacements
     * @param int      $count    number of replacements done
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     *
     * @return string
     */
    public function preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = null): ?string
    {
        return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }
    /**
     * PHP preg_match() function wrapper
     *
     * @param string $sPattern pattern to search for, as a string
     * @param string $sSubject input string
     * @param array  $aMatches is filled with the results of search
     * @param int    $iFlags   flags
     * @param int    $iOffset  place from which to start the search
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function preg_match($s_pattern, $s_subject, &$a_matches = null, $i_flags = null, $i_offset = null): int|false
    {
        return preg_match($s_pattern, $s_subject, $a_matches, $i_flags, $i_offset);
    }
    /**
     * PHP preg_match_all() function wrapper
     *
     * @param string $sPattern pattern to search for, as a string
     * @param string $sSubject input string
     * @param array  $aMatches is filled with the results of search
     * @param int    $iFlags   flags
     * @param int    $iOffset  place from which to start the search
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function preg_match_all($s_pattern, $s_subject, &$a_matches = null, $i_flags = null, $i_offset = null): int|false
    {
        return preg_match_all($s_pattern, $s_subject, $a_matches, $i_flags, $i_offset);
    }
    /**
     * PHP ucfirst() function wrapper
     *
     * @param string $sSubject input string
     */
    public function ucfirst($s_subject): string
    {
        $s_string = $this->strtoupper($this->substr($s_subject, 0, 1));
        return $s_string . $this->substr($s_subject, 1);
    }
    /**
     * PHP wordwrap() function wrapper
     *
     * @param string $sString input string
     * @param int    $iLength column width
     * @param string $sBreak  line is broken using the optional break parameter
     * @param bool   $blCut   string is always wrapped at the specified width
     */
    public function wordwrap($s_string, $i_length = 75, $s_break = "\n", $bl_cut = null): string
    {
        return wordwrap($s_string, $i_length, $s_break, $bl_cut);
    }
    /**
     * Recodes and returns passed input:
     * if $blToHtmlEntities == true  ä -> &auml;
     * if $blToHtmlEntities == false &auml; -> ä
     *
     * @param string $sInput           text to recode
     * @param bool   $blToHtmlEntities recode direction
     * @param array  $aUmls            language specific characters
     * @param array  $aUmlEntities     language specific characters equivalents in entities form
     *
     * @return string
     */
    public function recode_entities($s_input, $bl_to_html_entities = false, $a_umls = [], $a_uml_entities = []): string|array
    {
        $a_umls = count($a_umls) > 0 ? array_merge($this->_a_umls, $a_umls) : $this->_a_umls;
        $a_uml_entities = count($a_uml_entities) > 0 ? array_merge($this->_a_uml_entities, $a_uml_entities) : $this->_a_uml_entities;
        return $bl_to_html_entities ? str_replace($a_umls, $a_uml_entities, $s_input) : str_replace($a_uml_entities, $a_umls, $s_input);
    }
    /**
     * Checks if string has special chars
     *
     * @param string $sStr string to search in
     */
    public function has_special_chars($s_str): int|false
    {
        return $this->preg_match('/(' . implode('|', $this->_a_umls) . '|(&amp;))/', $s_str);
    }
    /**
     * Replaces special characters with passed char.
     * Special chars are: \n \r \t x95 xa0 ;
     *
     * @param string $sStr      string to cleanup
     * @param mixed  $sCleanChr which character should be used as a replacement (default is empty space)
     *
     * @return string
     */
    public function clean_str($s_str, $s_clean_chr = ' '): ?string
    {
        return $this->preg_replace("/\n|\r|\t|\x95|\xa0|;/", $s_clean_chr, $s_str);
    }
    /**
     * wrapper for json encode, which does not work with non utf8 characters
     *
     * @param mixed $data data to encode
     */
    public function json_encode($data): string
    {
        if (is_array($data)) {
            $ret = '';
            $bl_was_one = false;
            $bl_numerical = true;
            while ($bl_numerical && $key = array_key_first($data)) {
                $bl_numerical = !is_string($key);
            }
            if ($bl_numerical) {
                return '[' . implode(',', array_map($this->json_encode(...), $data)) . ']';
            }
            foreach ($data as $key => $val) {
                if ($bl_was_one) {
                    $ret .= ',';
                } else {
                    $bl_was_one = true;
                }
                $ret .= '"' . addslashes((string) $key) . '":' . $this->json_encode($val);
            }
            return '{' . $ret . '}';
        }
        return '"' . addcslashes((string) $data, "\r\n\t\"\\") . '"';
    }
    /**
     * PHP strip_tags() function wrapper.
     *
     * @param string $sString        the input string
     * @param string $sAllowableTags an optional parameter to specify tags which should not be stripped
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function strip_tags($s_string, $s_allowable_tags = ''): string
    {
        if (stripos($s_allowable_tags, '<style>') === false) {
            // strip style tags with definitions within
            $s_string = $this->preg_replace("'<style[^>]*>.*</style>'siU", '', $s_string);
        }
        return strip_tags((string) $s_string, $s_allowable_tags);
    }
    /**
     * Compares two strings. Case sensitive.
     * For use in sorting with reverse order
     *
     * @param string $sStr1 String to compare
     * @param string $sStr2 String to compare
     *
     * @return int > 0 if str1 is less than str2; < 0 if str1 is greater than str2, and 0 if they are equal.
     */
    public function strrcmp($s_str1, $s_str2): int
    {
        return -strcmp($s_str1, $s_str2);
    }
}