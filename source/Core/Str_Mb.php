<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class dealing with multibyte strings
 */
class Str_Mb
{
    /**
     * The character encoding.
     *
     * @var string
     */
    protected $_s_encoding = 'UTF-8';
    /**
     * Language specific characters (currently german; storen in octal form)
     *
     * @var array
     */
    protected $_a_umls = ["ä", "ö", "ü", "Ä", "Ö", "Ü", "ß"];
    /**
     * oxUtilsString::$_aUmls equivalent in entities form
     *
     * @var array
     */
    protected $_a_uml_entities = ['&auml;', '&ouml;', '&uuml;', '&Auml;', '&Ouml;', '&Uuml;', '&szlig;'];
    /**
     * PHP  multi byte compliant strlen() function wrapper
     *
     * @param string $sStr string to measure its length
     */
    public function strlen($s_str): int
    {
        return mb_strlen($s_str ?? '', $this->_s_encoding);
    }
    /**
     * PHP multi byte compliant substr() function wrapper
     *
     * @param string $sStr    value to truncate
     * @param int    $iStart  start position
     * @param int    $iLength length
     */
    public function substr($s_str, $i_start, $i_length = null): string
    {
        $i_length = is_null($i_length) ? $this->strlen($s_str) : $i_length;
        return mb_substr($s_str, $i_start, $i_length, $this->_s_encoding);
    }
    /**
     * PHP multi byte compliant strpos() function wrapper
     *
     * @param string $sHaystack value to search in
     * @param string $sNeedle   value to search for
     * @param int    $iOffset   initial search position
     */
    public function strpos($s_haystack, $s_needle, $i_offset = null): int|false
    {
        $i_pos = false;
        if ($s_haystack && $s_needle) {
            $i_offset = is_null($i_offset) ? 0 : $i_offset;
            $i_pos = mb_strpos($s_haystack, $s_needle, $i_offset, $this->_s_encoding);
        }
        return $i_pos;
    }
    /**
     * PHP multi byte compliant strstr() function wrapper
     *
     * @param string $sHaystack value to search in
     * @param string $sNeedle   value to search for
     *
     * @return string
     */
    public function strstr($s_haystack, $s_needle): false|string
    {
        // additional check according to bug in PHP 5.2.0 version
        if (!$s_haystack) {
            return false;
        }
        return mb_strstr($s_haystack, $s_needle, false, $this->_s_encoding);
    }
    /**
     * PHP multi byte compliant strtolower() function wrapper
     *
     * @param string $sString string being lower cased
     */
    public function strtolower($s_string): string
    {
        return mb_strtolower($s_string, $this->_s_encoding);
    }
    /**
     * PHP multi byte compliant strtoupper() function wrapper
     *
     * @param string $sString string being lower cased
     */
    public function strtoupper($s_string): string
    {
        return mb_strtoupper($s_string, $this->_s_encoding);
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
    // @codingStandardsIgnoreStart
    /**
     * PHP html_entity_decode() function wrapper
     *
     * @param string $sString    string being converted
     * @param int    $iQuotStyle quoting rule
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
     * @param int    $iLimit   (optional) only sub strings up to limit are returned
     * @param int    $iFlag    flags
     *
     * @return string
     */
    public function preg_split(string $s_pattern, $s_string, $i_limit = -1, $i_flag = 0)
    {
        return preg_split($s_pattern . 'u', $s_string, $i_limit, $i_flag);
    }
    /**
     * PHP preg_replace() function wrapper
     *
     * @param mixed  $aPattern pattern to search for, as a string
     * @param mixed  $sString  string to replace
     * @param string $sSubject strings to search and replace
     * @param int    $iLimit   maximum possible replacements
     * @param int    $iCount   number of replacements done
     *
     * @return string
     */
    public function preg_replace($a_pattern, $s_string, $s_subject, $i_limit = -1, $i_count = null): ?string
    {
        if (is_array($a_pattern)) {
            foreach ($a_pattern as &$s_pattern) {
                $s_pattern = $s_pattern . 'u';
            }
        } else {
            $a_pattern = $a_pattern . 'u';
        }
        return preg_replace($a_pattern, (string) $s_string, $s_subject, $i_limit, $i_count);
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
     * @return string
     */
    public function preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = null): ?string
    {
        if (is_array($pattern)) {
            foreach ($pattern as &$item) {
                $item = $item . 'u';
            }
        } else {
            $pattern = $pattern . 'u';
        }
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
     */
    public function preg_match(string $s_pattern, $s_subject, &$a_matches = null, $i_flags = 0, $i_offset = 0): int|false
    {
        return preg_match($s_pattern . 'u', $s_subject, $a_matches, $i_flags, $i_offset);
    }
    /**
     * PHP preg_match_all() function wrapper
     *
     * @param string $sPattern pattern to search for, as a string
     * @param string $sSubject input string
     * @param array  $aMatches is filled with the results of search
     * @param int    $iFlags   flags
     * @param int    $iOffset  place from which to start the search
     */
    public function preg_match_all(string $s_pattern, $s_subject, &$a_matches = null, $i_flags = null, $i_offset = null): int|false
    {
        return preg_match_all($s_pattern . 'u', $s_subject, $a_matches, $i_flags, $i_offset);
    }
    // @codingStandardsIgnoreEnd
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
     *
     * @return string
     */
    public function wordwrap($s_string, $i_length = 75, string $s_break = "\n", $bl_cut = null)
    {
        if (!$bl_cut) {
            $s_regexp = "/^(.{1,{$i_length}}\r?(\\s|\$|\n)|.{1,{$i_length}}[^\r\\s\n]*\r?(\n|\\s|\$))/u";
        } else {
            $s_regexp = "/^([^\\s]{{$i_length}}|.{1,{$i_length}}\\s)/u";
        }
        $i_str_len = mb_strlen($s_string, $this->_s_encoding);
        $i_wraps = floor($i_str_len / $i_length);
        $i = $i_wraps;
        $s_return = '';
        $a_matches = [];
        while ($i > 0) {
            $i_wraps = floor(mb_strlen($s_string, $this->_s_encoding) / $i_length);
            $i = $i_wraps;
            if (preg_match($s_regexp, $s_string, $a_matches)) {
                $s_str = $a_matches[0];
                $s_return .= preg_replace('/\s$/s', '', $s_str) . $s_break;
                $s_string = $this->substr($s_string, mb_strlen($s_str, $this->_s_encoding));
            } else {
                break;
            }
            $i--;
        }
        $s_return = preg_replace("/{$s_break}\$/", '', $s_return);
        if ($s_string) {
            $s_return .= $s_break . $s_string;
        }
        return $s_return;
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
     * Special chars are: \n \r \t \xc2\x95 \xc2\xa0 ;
     *
     * @param string $sStr      string to cleanup
     * @param string $sCleanChr which character should be used as a replacement (default is empty space)
     *
     * @return string
     */
    public function clean_str($s_str, $s_clean_chr = ' '): ?string
    {
        return $this->preg_replace("/\n|\r|\t|| |;/", $s_clean_chr, $s_str);
    }
    /**
     * wrapper for json encode, which does not work with non utf8 characters
     *
     * @param mixed $data data to encode
     *
     * @return string
     */
    public function json_encode($data)
    {
        return json_encode($data);
    }
    // @codingStandardsIgnoreStart
    /**
     * PHP strip_tags() function wrapper.
     *
     * @param string $sString        the input string
     * @param string $sAllowableTags an optional parameter to specify tags which should not be stripped
     */
    public function strip_tags($s_string, $s_allowable_tags = ''): string
    {
        if (stripos($s_allowable_tags, '<style>') === false) {
            // strip style tags with definitions within
            $s_string = $this->preg_replace("'<style[^>]*>.*</style>'siU", '', $s_string);
        }
        return strip_tags((string) $s_string, $s_allowable_tags);
    }
    // @codingStandardsIgnoreEnd
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