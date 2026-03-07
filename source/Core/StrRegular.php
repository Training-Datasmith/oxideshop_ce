<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * Class dealing with regular string handling
 */
class StrRegular
{
    /**
     * The character encoding.
     *
     * @var string
     */
    protected $_sEncoding = 'ISO8859-15';

    /**
     * Language specific characters (currently german; storen in octal form)
     *
     * @var array
     */
    protected $_aUmls = ["\344", "\366", "\374", "\304", "\326", "\334", "\337"];

    /**
     * oxUtilsString::$_aUmls equivalent in entities form
     *
     * @var array
     */
    protected $_aUmlEntities = ['&auml;', '&ouml;', '&uuml;', '&Auml;', '&Ouml;', '&Uuml;', '&szlig;'];

    /**
     * PHP strlen() function wrapper
     *
     * @param string $sStr string to measure its length
     */
    public function strlen($sStr): int
    {
        return strlen($sStr);
    }

    /**
     * PHP substr() function wrapper
     *
     * @param string $sStr    value to truncate
     * @param int    $iStart  start position
     * @param int    $iLength length
     */
    public function substr($sStr, $iStart, $iLength = null): string
    {
        if (is_null($iLength)) {
            return substr($sStr, $iStart);
        }
        return substr($sStr, $iStart, $iLength);
    }

    /**
     * PHP strpos() function wrapper
     *
     * @param string $sHaystack value to search in
     * @param string $sNeedle   value to search for
     * @param int    $iOffset   initial search position
     */
    public function strpos($sHaystack, $sNeedle, $iOffset = null): int|false
    {
        $iPos = false;
        if ($sHaystack && $sNeedle) {
            if (is_null($iOffset)) {
                $iPos = strpos($sHaystack, $sNeedle);
            } else {
                $iPos = strpos($sHaystack, $sNeedle, $iOffset);
            }
        }

        return $iPos;
    }

    /**
     * PHP strstr() function wrapper
     *
     * @param string $sHaystack string searching in
     * @param string $sNeedle   string to search
     */
    public function strstr($sHaystack, $sNeedle): string|false
    {
        return strstr($sHaystack, $sNeedle);
    }

    /**
     * PHP multi byte compliant strtolower() function wrapper
     *
     * @param string $sString string being lower cased
     */
    public function strtolower($sString): string
    {
        return strtolower($sString);
    }

    /**
     * PHP strtolower() function wrapper
     *
     * @param string $sString string being lower cased
     */
    public function strtoupper($sString): string
    {
        return strtoupper($sString);
    }

    /**
     * PHP htmlspecialchars() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
     */
    public function htmlspecialchars($sString, $iQuotStyle = ENT_QUOTES): string
    {
        return htmlspecialchars($sString, $iQuotStyle, $this->_sEncoding);
    }

    /**
     * PHP htmlentities() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
     */
    public function htmlentities($sString, $iQuotStyle = ENT_QUOTES): string
    {
        return htmlentities($sString, $iQuotStyle, $this->_sEncoding);
    }

    /**
     * PHP html_entity_decode() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
     *
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function html_entity_decode($sString, $iQuotStyle = ENT_QUOTES): string
    {
        return html_entity_decode($sString, $iQuotStyle, $this->_sEncoding);
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
    public function preg_split($sPattern, $sString, $iLimit = -1, $iFlag = 0)
    {
        return preg_split($sPattern, $sString, $iLimit, $iFlag);
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
    public function preg_replace($sPattern, $sString, $sSubject, $iLimit = -1, $iCount = null): ?string
    {
        return preg_replace($sPattern, (string) $sString, $sSubject, $iLimit, $iCount);
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
    public function preg_match($sPattern, $sSubject, &$aMatches = null, $iFlags = null, $iOffset = null): int|false
    {
        return preg_match($sPattern, $sSubject, $aMatches, $iFlags, $iOffset);
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
    public function preg_match_all($sPattern, $sSubject, &$aMatches = null, $iFlags = null, $iOffset = null): int|false
    {
        return preg_match_all($sPattern, $sSubject, $aMatches, $iFlags, $iOffset);
    }

    /**
     * PHP ucfirst() function wrapper
     *
     * @param string $sSubject input string
     */
    public function ucfirst($sSubject): string
    {
        $sString = $this->strtoupper($this->substr($sSubject, 0, 1));

        return $sString . $this->substr($sSubject, 1);
    }

    /**
     * PHP wordwrap() function wrapper
     *
     * @param string $sString input string
     * @param int    $iLength column width
     * @param string $sBreak  line is broken using the optional break parameter
     * @param bool   $blCut   string is always wrapped at the specified width
     */
    public function wordwrap($sString, $iLength = 75, $sBreak = "\n", $blCut = null): string
    {
        return wordwrap($sString, $iLength, $sBreak, $blCut);
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
    public function recodeEntities($sInput, $blToHtmlEntities = false, $aUmls = [], $aUmlEntities = []): string|array
    {
        $aUmls = (count($aUmls) > 0) ? array_merge($this->_aUmls, $aUmls) : $this->_aUmls;
        $aUmlEntities = (count($aUmlEntities) > 0)
            ? array_merge($this->_aUmlEntities, $aUmlEntities)
            : $this->_aUmlEntities;

        return $blToHtmlEntities
            ? str_replace($aUmls, $aUmlEntities, $sInput)
            : str_replace($aUmlEntities, $aUmls, $sInput);
    }

    /**
     * Checks if string has special chars
     *
     * @param string $sStr string to search in
     *
     * @return bool
     */
    public function hasSpecialChars($sStr)
    {
        return $this->preg_match('/(' . implode('|', $this->_aUmls) . '|(&amp;))/', $sStr);
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
    public function cleanStr($sStr, $sCleanChr = ' ')
    {
        return $this->preg_replace("/\n|\r|\t|\x95|\xa0|;/", $sCleanChr, $sStr);
    }

    /**
     * wrapper for json encode, which does not work with non utf8 characters
     *
     * @param mixed $data data to encode
     */
    public function jsonEncode($data): string
    {
        if (is_array($data)) {
            $ret = '';
            $blWasOne = false;
            $blNumerical = true;
            while ($blNumerical && $key = array_key_first($data)) {
                $blNumerical = !is_string($key);
            }
            if ($blNumerical) {
                return '[' . implode(',', array_map($this->jsonEncode(...), $data)) . ']';
            }
            foreach ($data as $key => $val) {
                if ($blWasOne) {
                    $ret .= ',';
                } else {
                    $blWasOne = true;
                }
                $ret .= '"' . addslashes((string) $key) . '":' . $this->jsonEncode($val);
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
    public function strip_tags($sString, $sAllowableTags = ''): string
    {
        if (stripos($sAllowableTags, '<style>') === false) {
            // strip style tags with definitions within
            $sString = $this->preg_replace("'<style[^>]*>.*</style>'siU", '', $sString);
        }

        return strip_tags($sString, $sAllowableTags);
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
    public function strrcmp($sStr1, $sStr2): int
    {
        return -strcmp($sStr1, $sStr2);
    }
}
