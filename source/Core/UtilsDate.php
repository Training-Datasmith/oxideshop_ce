<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use DateTime;
use Oxid_Esales\Eshop\Core\Str;
/**
 * Date manipulation utility class
 */
class Utils_Date extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Format date to user defined format.
     *
     * @param string $sDBDateIn         Date to reformat
     * @param bool   $blForceEnglishRet Force to return primary value(default false)
     *
     * @return string
     */
    public function format_db_date($s_db_date_in, $bl_force_english_ret = false)
    {
        // convert english format to output format
        if (!$s_db_date_in) {
            return null;
        }
        $o_str = Str::get_str();
        if ($bl_force_english_ret && $o_str->strstr($s_db_date_in, '-')) {
            return $s_db_date_in;
        }
        if ($this->is_empty_date($s_db_date_in) && $s_db_date_in != '-') {
            return '-';
        }
        if ($s_db_date_in == '-') {
            return '0000-00-00 00:00:00';
        }
        // is it a timestamp ?
        if (is_numeric($s_db_date_in)) {
            // db timestamp : 20030322100409
            $s_new = substr($s_db_date_in, 0, 4) . '-' . substr($s_db_date_in, 4, 2) . '-' . substr($s_db_date_in, 6, 2) . ' ';
            // check if it is a timestamp or wrong data: 20030322
            if (strlen($s_db_date_in) > 8) {
                $s_new .= substr($s_db_date_in, 8, 2) . ':' . substr($s_db_date_in, 10, 2) . ':' . substr($s_db_date_in, 12, 2);
            }
            // convert it to english format
            $s_db_date_in = $s_new;
        }
        // remove time as it is same in english as in german
        $a_data = explode(' ', trim($s_db_date_in));
        // preparing time array
        $s_time = isset($a_data[1]) && $o_str->strstr($a_data[1], ':') ? $a_data[1] : '';
        $a_time = $s_time ? explode(':', $s_time) : [0, 0, 0];
        // preparing date array
        $s_date = $a_data[0] ?? '';
        $a_date = preg_split('/[\/.-]/', $s_date);
        // choosing format..
        if ($s_time) {
            $s_format = $bl_force_english_ret ? 'Y-m-d H:i:s' : \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('fullDateFormat');
        } else {
            $s_format = $bl_force_english_ret ? 'Y-m-d' : \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('simpleDateFormat');
        }
        if (count($a_date) != 3) {
            return date($s_format);
        }
        return $this->process_date($a_time, $a_date, $o_str->strstr($s_date, '.'), $s_format);
    }
    /**
     * Bidirectional converter for date/datetime field
     *
     * @param object $oObject       data field object
     * @param bool   $blToTimeStamp set TRUE to format MySQL compatible value
     * @param bool   $blOnlyDate    set TRUE to format "date" type field
     *
     * @return string
     */
    public function convert_db_date_time($o_object, $bl_to_time_stamp = false, $bl_only_date = false)
    {
        $s_date = $o_object->value;
        // defining time format
        $s_local_date_format = $this->define_and_check_default_date_values($bl_to_time_stamp);
        $s_local_time_format = $this->define_and_check_default_time_values($bl_to_time_stamp);
        // default date/time patterns
        $a_def_date_patterns = $this->default_date_pattern();
        // regexps to validate input
        $a_date_patterns = $this->regexp2validate_date_input();
        $a_time_patterns = $this->regexp2validate_time_input();
        // date/time formatting rules
        $a_d_formats = $this->define_date_formatting_rules();
        $a_t_formats = $this->define_time_formatting_rules();
        // empty date field value ? setting default value
        if (!$s_date) {
            $this->set_default_date_time_value($o_object, $s_local_date_format, $s_local_time_format, $bl_only_date);
            return $o_object->value;
        }
        $bl_def_date_found = false;
        $o_str = Str::get_str();
        // looking for default values that are formatted by MySQL
        foreach (array_keys($a_def_date_patterns) as $s_def_date_pattern) {
            if ($o_str->preg_match($s_def_date_pattern, $s_date)) {
                $bl_def_date_found = true;
                break;
            }
        }
        // default value is set ?
        if ($bl_def_date_found) {
            $this->set_default_formated_value($o_object, $s_date, $s_local_date_format, $s_local_time_format, $bl_only_date);
            return $o_object->value;
        }
        $bl_date_found = false;
        $bl_time_found = false;
        $a_date_matches = [];
        $a_time_matches = [];
        // looking for date field
        foreach ($a_date_patterns as $s_pattern => $s_type) {
            if ($o_str->preg_match($s_pattern, $s_date, $a_date_matches)) {
                $bl_date_found = true;
                // now we know the type of passed date
                $s_date_format = $a_d_formats[$s_local_date_format][0];
                $a_d_fields = $a_d_formats[$s_type][1];
                break;
            }
        }
        // no such date field available ?
        if (!$bl_date_found) {
            return $s_date;
        }
        if ($bl_only_date) {
            $this->set_date($o_object, $s_date_format, $a_d_fields, $a_date_matches);
            return $o_object->value;
        }
        // looking for time field
        foreach ($a_time_patterns as $s_pattern => $s_type) {
            if ($o_str->preg_match($s_pattern, $s_date, $a_time_matches)) {
                $bl_time_found = true;
                // now we know the type of passed time
                $s_time_format = $a_t_formats[$s_local_time_format][0];
                $a_t_fields = $a_t_formats[$s_type][1];
                if ($s_type == 'USA' && isset($a_time_matches[4])) {
                    $i_int_val = (int) $a_time_matches[1];
                    if ($a_time_matches[4] == 'PM') {
                        if ($i_int_val < 13) {
                            $i_int_val += 12;
                        }
                    } elseif ($a_time_matches[4] == 'AM' && $a_time_matches[1] == '12') {
                        $i_int_val = 0;
                    }
                    $a_time_matches[1] = sprintf('%02d', $i_int_val);
                }
                break;
            }
        }
        if (!$bl_time_found) {
            //return $sDate;
            // #871A. trying to keep date as possible correct
            $this->set_date($o_object, $s_date_format, $a_d_fields, $a_date_matches);
            return $o_object->value;
        }
        $this->format_correct_time_value($o_object, $s_date_format, $s_time_format, $a_date_matches, $a_time_matches, $a_t_fields, $a_d_fields);
        // on some cases we get empty value
        if (!$o_object->fldmax_length) {
            return $this->convert_db_date_time($o_object, $bl_to_time_stamp, $bl_only_date);
        }
        return $o_object->value;
    }
    /**
     * Bidirectional converter for timestamp field
     *
     * @param object $oObject       oxField type object that keeps db field info
     * @param bool   $blToTimeStamp if true - converts value to database compatible timestamp value
     *
     * @return string
     */
    public function convert_db_timestamp($o_object, $bl_to_time_stamp = false)
    {
        // on this case usually means that we gonna save value, and value is formatted, not plain
        $s_sql_time_stamp_pattern = '/^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/';
        $s_iso_time_stamp_pattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/';
        $a_matches = [];
        $o_str = Str::get_str();
        // preparing value to save
        if ($bl_to_time_stamp) {
            // reformatting value to ISO
            $this->convert_db_date_time($o_object, $bl_to_time_stamp);
            if ($o_str->preg_match($s_iso_time_stamp_pattern, $o_object->value, $a_matches)) {
                // changing layout
                $o_object->set_value($a_matches[1] . $a_matches[2] . $a_matches[3] . $a_matches[4] . $a_matches[5] . $a_matches[6]);
                $o_object->fldmax_length = strlen((string) $o_object->value);
                return $o_object->value;
            }
        } else if ($o_str->preg_match($s_sql_time_stamp_pattern, $o_object->value, $a_matches)) {
            $i_timestamp = mktime(
                $a_matches[4],
                //h
                $a_matches[5],
                //m
                $a_matches[6],
                //s
                $a_matches[2],
                //M
                $a_matches[3],
                //d
                $a_matches[1]
            );
            //y
            if (!$i_timestamp) {
                $i_timestamp = '0';
            }
            $o_object->set_value(trim(date('Y-m-d H:i:s', $i_timestamp)));
            $o_object->fldmax_length = strlen((string) $o_object->value);
            $this->convert_db_date_time($o_object, $bl_to_time_stamp);
            return $o_object->value;
        }
    }
    /**
     * Bidirectional converter for date field
     *
     * @param object $oObject       oxField type object that keeps db field info
     * @param bool   $blToTimeStamp if true - converts value to database compatible timestamp value
     *
     * @return string
     */
    public function convert_db_date($o_object, $bl_to_time_stamp = false)
    {
        return $this->convert_db_date_time($o_object, $bl_to_time_stamp, true);
    }
    /**
     * sets default formatted value
     *
     * @param object $oObject          date field object
     * @param string $sDate            preferred date
     * @param string $sLocalDateFormat input format
     * @param string $sLocalTimeFormat local format
     * @param bool   $blOnlyDate       marker to format only date field (no time)
     */
    protected function set_default_formated_value($o_object, $s_date, $s_local_date_format, $s_local_time_format, $bl_only_date)
    {
        $a_def_time_patterns = $this->default_time_pattern();
        $a_d_formats = $this->define_date_formatting_rules();
        $a_t_formats = $this->define_time_formatting_rules();
        $o_str = Str::get_str();
        foreach (array_keys($a_def_time_patterns) as $s_def_time_pattern) {
            if ($o_str->preg_match($s_def_time_pattern, $s_date)) {
                $bl_def_time_found = true;
                break;
            }
        }
        // setting and returning default formatted value
        if ($bl_only_date) {
            $o_object->set_value(trim((string) $a_d_formats[$s_local_date_format][2]));
            // . " " . @$aTFormats[$sLocalTimeFormat][2]);
            // increasing(decreasing) field length
            $o_object->fldmax_length = strlen((string) $o_object->value);
            return;
        }
        // setting and returning default formatted value
        // setting value
        $o_object->set_value(trim($a_d_formats[$s_local_date_format][2] . ' ' . $a_t_formats[$s_local_time_format][2]));
        // increasing(decreasing) field length
        $o_object->fldmax_length = strlen((string) $o_object->value);
    }
    /**
     * defines and checks default time values
     *
     * @param bool $blToTimeStamp -
     *
     * @return string
     */
    protected function define_and_check_default_time_values($bl_to_time_stamp)
    {
        // defining time format
        // checking for default values
        $s_local_time_format = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sLocalTimeFormat');
        if (!$s_local_time_format || $bl_to_time_stamp) {
            return 'ISO';
        }
        return $s_local_time_format;
    }
    /**
     * defines and checks default date values
     *
     * @param bool $blToTimeStamp marker how to format
     *
     * @return string
     */
    protected function define_and_check_default_date_values($bl_to_time_stamp)
    {
        // defining time format
        // checking for default values
        $s_local_date_format = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sLocalDateFormat');
        if (!$s_local_date_format || $bl_to_time_stamp) {
            return 'ISO';
        }
        return $s_local_date_format;
    }
    /**
     * sets default date pattern
     *
     * @return array
     */
    protected function default_date_pattern()
    {
        return ['/^0000-00-00/' => 'ISO', "/^00\\.00\\.0000/" => 'EUR', "/^00\\/00\\/0000/" => 'USA'];
    }
    /**
     * sets default time pattern
     *
     * @return array
     */
    protected function default_time_pattern()
    {
        return ['/00:00:00$/' => 'ISO', "/00\\.00\\.00\$/" => 'EUR', '/00:00:00 AM$/' => 'USA'];
    }
    /**
     * regular expressions to validate date input
     *
     * @return array
     */
    protected function regexp2validate_date_input()
    {
        return ['/^([0-9]{4})-([0-9]{2})-([0-9]{2})/' => 'ISO', "/^([0-9]{2})\\.([0-9]{2})\\.([0-9]{4})/" => 'EUR', "/^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})/" => 'USA'];
    }
    /**
     * regular expressions to validate time input
     *
     * @return array
     */
    protected function regexp2validate_time_input()
    {
        return ['/([0-9]{2}):([0-9]{2}):([0-9]{2})$/' => 'ISO', "/([0-9]{2})\\.([0-9]{2})\\.([0-9]{2})\$/" => 'EUR', '/([0-9]{2}):([0-9]{2}):([0-9]{2}) ([AP]{1}[M]{1})$/' => 'USA'];
    }
    /**
     * define date formatting rules
     *
     * @return array
     */
    protected function define_date_formatting_rules()
    {
        return ['ISO' => ['Y-m-d', [2, 3, 1], '0000-00-00'], 'EUR' => ['d.m.Y', [2, 1, 3], '00.00.0000'], 'USA' => ['m/d/Y', [1, 2, 3], '00/00/0000']];
    }
    /**
     * defines time formatting rules
     *
     * @return array
     */
    protected function define_time_formatting_rules()
    {
        return ['ISO' => ['H:i:s', [1, 2, 3], '00:00:00'], 'EUR' => ['H.i.s', [1, 2, 3], '00.00.00'], 'USA' => ['h:i:s A', [1, 2, 3], '00:00:00 AM']];
    }
    /**
     * Sets default date time value
     *
     * @param object $oObject          date field object
     * @param string $sLocalDateFormat input format
     * @param string $sLocalTimeFormat local format
     * @param bool   $blOnlyDate       marker to format only date field (no time)
     */
    protected function set_default_date_time_value($o_object, $s_local_date_format, $s_local_time_format, $bl_only_date)
    {
        $a_d_formats = $this->define_date_formatting_rules();
        $a_t_formats = $this->define_time_formatting_rules();
        $s_return = $a_d_formats[$s_local_date_format][2];
        if (!$bl_only_date) {
            $s_return .= ' ' . $a_t_formats[$s_local_time_format][2];
        }
        if ($o_object instanceof \Oxid_Esales\Eshop\Core\Field) {
            $o_object->set_value(trim((string) $s_return));
        } else {
            $o_object->value = trim((string) $s_return);
        }
        // increasing(decreasing) field lenght
        $o_object->fldmax_length = strlen((string) $o_object->value);
    }
    /**
     * sets date
     *
     * @param object $oObject      date field object
     * @param string $sDateFormat  date format
     * @param array  $aDFields     days
     * @param array  $aDateMatches new date as array (month, year)
     */
    protected function set_date($o_object, $s_date_format, $a_d_fields, $a_date_matches)
    {
        // formatting correct time value
        $i_timestamp = mktime(0, 0, 0, $a_date_matches[$a_d_fields[0]], $a_date_matches[$a_d_fields[1]], $a_date_matches[$a_d_fields[2]]);
        if ($o_object instanceof \Oxid_Esales\Eshop\Core\Field) {
            $o_object->set_value(@date($s_date_format, $i_timestamp));
        } else {
            $o_object->value = @date($s_date_format, $i_timestamp);
        }
        // we should increase (decrease) field lenght
        $o_object->fldmax_length = strlen((string) $o_object->value);
    }
    /**
     * Formatting correct time value
     *
     * @param object $oObject      data field object
     * @param string $sDateFormat  date format
     * @param string $sTimeFormat  time format
     * @param array  $aDateMatches new new date
     * @param array  $aTimeMatches new time
     * @param array  $aTFields     defines the time fields
     * @param array  $aDFields     defines the date fields
     */
    protected function format_correct_time_value($o_object, $s_date_format, $s_time_format, $a_date_matches, $a_time_matches, $a_t_fields, $a_d_fields)
    {
        // formatting correct time value
        $i_timestamp = @mktime((int) $a_time_matches[$a_t_fields[0]], (int) $a_time_matches[$a_t_fields[1]], (int) $a_time_matches[$a_t_fields[2]], (int) $a_date_matches[$a_d_fields[0]], (int) $a_date_matches[$a_d_fields[1]], (int) $a_date_matches[$a_d_fields[2]]);
        if ($o_object instanceof \Oxid_Esales\Eshop\Core\Field) {
            $o_object->set_value(trim(@date($s_date_format . ' ' . $s_time_format, $i_timestamp)));
        } else {
            $o_object->value = trim(@date($s_date_format . ' ' . $s_time_format, $i_timestamp));
        }
        // we should increase (decrease) field lenght
        $o_object->fldmax_length = strlen((string) $o_object->value);
    }
    /**
     * Returns time according shop timezone configuration. Configures in
     * Admin -> Main menu -> Core Settings -> General
     * @see getRequestTime
     * @return int current (modified according timezone) time
     */
    public function get_time()
    {
        return $this->shift_server_time(time());
    }
    /**
     * Returns time wen the request was started according shop timezone configuration. Configures in
     * Admin -> Main menu -> Core Settings -> General
     * REQUEST TIME is faster because it is not an syscall like time
     * @return int current (modified according timezone) time
     */
    public function get_request_time()
    {
        return $this->shift_server_time($_SERVER['REQUEST_TIME']);
    }
    /**
     * Returns the the timestamp formatted as date string for the database
     *
     * @param int $iTimestamp the timestamp to be formatted
     *
     * @return bool|string timestamp formatted as date string for the database, false on error
     */
    public function format_db_timestamp($i_timestamp)
    {
        return date('Y-m-d H:i:s', $i_timestamp);
    }
    /**
     * Returns the the timestamp formatted as date string for the database
     * @param int $roundTo a amount of seconds to be rounded to e.g. 300 for rounding to 5 minutes
     *
     * @return bool|string  the data string formatted for the database (SQL), false on error
     */
    public function get_rounded_request_date_db_formatted($round_to)
    {
        $timestamp = $this->get_request_time();
        //round up x minutes so query cache can work
        $timestamp = ceil($timestamp / $round_to) * $round_to;
        //format date for sql query
        return $this->format_db_timestamp($timestamp);
    }
    /**
     * Returns the the request time formatted as date string for the database
     *
     * @return bool|string
     */
    public function get_request_time_db_formated()
    {
        return $this->format_db_timestamp($this->get_request_time());
    }
    /**
     * Form time
     *
     * @param string $sTime  time to create timestamp.
     * @param string $sTime2 hours, minutes and seconds to update created timestamp.
     *
     * @return int formed (modified according timezone) time
     */
    public function form_time($s_time = 'now', $s_time2 = null)
    {
        $o_date = new DateTime($s_time);
        if ($s_time2) {
            $a_hour_to_check = explode(':', $s_time2);
            $i_hour = $a_hour_to_check[0];
            $i_minutes = $a_hour_to_check[1];
            $i_second = $a_hour_to_check[2];
            $o_date->set_time($i_hour, $i_minutes, $i_second);
        }
        return $this->shift_server_time($o_date->get_timestamp());
    }
    /**
     * Shift time if needed by configured timezone.
     *
     * @param int $iTime
     *
     * @return int
     */
    public function shift_server_time($i_time)
    {
        $i_server_time_shift = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iServerTimeShift');
        if ($i_server_time_shift) {
            return $i_time + (int) $i_server_time_shift * 3600;
        }
        return $i_time;
    }
    /**
     * Returns number of the week according to numeration standards (configurable in admin):
     * %U - week number, starting with the first Sunday as the first day of the first week;
     * %W - week number, starting with the first Monday as the first day of the first week.
     *
     * @param int    $iFirstWeekDay if set formats with %U, otherwise with %W ($myConfig->getConfigParam( 'iFirstWeekDay' ))
     * @param string $sTimestamp    timestamp, default is null (returns current week number);
     * @param string $sFormat       calculation format ( "%U" or "%w"), default is null (returns "%W" or defined in admin ).
     *
     * @return int
     */
    public function get_week_number($i_first_week_day, $s_timestamp = null, $s_format = null)
    {
        if ($s_timestamp == null) {
            $s_timestamp = time();
        }
        if ($s_format == null) {
            $s_format = '%W';
            if ($i_first_week_day) {
                $s_format = '%U';
            }
        }
        return (int) strftime($s_format, $s_timestamp);
    }
    /**
     * Reformats and returns German date string to English.
     *
     * @param string $sDate German format date string
     *
     * @return string
     */
    public function german2English($s_date)
    {
        $a_date = explode('.', $s_date);
        if (count($a_date) > 1) {
            if (count($a_date) == 2) {
                $s_date = $a_date[1] . '-' . $a_date[0];
            } else {
                $s_date = $a_date[2] . '-' . $a_date[1] . '-' . $a_date[0];
            }
        }
        return $s_date;
    }
    /**
     * Checks if date string is empty date field. Empty string or string with
     * all date values equal to 0 is treated as empty.
     *
     * @param array $sDate date or date time string
     *
     * @return bool
     */
    public function is_empty_date($s_date)
    {
        if (!empty($s_date)) {
            $s_date = preg_replace('/[^0-9a-z]/i', '', $s_date);
            if (!is_numeric($s_date) || $s_date != 0) {
                return false;
            }
        }
        return true;
    }
    /**
     * Processes amd formats date / time.
     *
     * @param string $aTime    splitted time ( array( H, m, s ) )
     * @param array  $aDate    splitted date ( array( Y, m, d ) )
     * @param bool   $blGerman true if incoming string is in German format (dotted)
     * @param string $sFormat  date format to produce
     *
     * @return string formatted string
     */
    protected function process_date($a_time, $a_date, $bl_german, $s_format)
    {
        if ($bl_german) {
            return date($s_format, mktime($a_time[0], $a_time[1], $a_time[2], $a_date[1], $a_date[0], $a_date[2]));
        }
        return date($s_format, mktime($a_time[0], $a_time[1], $a_time[2], $a_date[1], $a_date[2], $a_date[0]));
    }
}