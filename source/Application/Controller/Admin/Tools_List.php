<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
/**
 * Admin systeminfo manager.
 * Returns template, that arranges two other templates ("tools_list"
 * and "tools_main") to frame.
 */
class Tools_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Current class template name
     *
     * @var string
     */
    protected $_s_this_template = 'tools_list';
    /**
     * Performs full view update
     */
    public function update_views(): void
    {
        //preventing edit for anyone except malladmin
        if (\Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('malladmin')) {
            $o_meta_data = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
            $this->_a_view_data['blViewSuccess'] = $o_meta_data->update_views();
        }
    }
    /**
     * Method performs user passed SQL query
     */
    public function performsql(): void
    {
        $o_auth_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $o_auth_user->load_admin_user();
        if ($o_auth_user->oxuser__oxrights->value === 'malladmin') {
            $s_update_sql = Registry::get_request()->get_request_escaped_parameter('updatesql');
            $s_update_sql_file = $this->process_files();
            if ($s_update_sql_file && strlen((string) $s_update_sql_file) > 0) {
                if (isset($s_update_sql) && strlen($s_update_sql)) {
                    $s_update_sql .= ";\r\n" . $s_update_sql_file;
                } else {
                    $s_update_sql = $s_update_sql_file;
                }
            }
            $s_update_sql = trim(stripslashes((string) $s_update_sql));
            $o_str = Str::get_str();
            $i_len = $o_str->strlen($s_update_sql);
            if ($this->prepare_sql($s_update_sql, $i_len)) {
                $a_queries = $this->a_sq_ls;
                $this->_a_view_data['aQueries'] = [];
                $a_passed_queries = [];
                $a_q_affected_rows = [];
                $a_q_error_messages = [];
                $a_q_error_numbers = [];
                if (!empty($a_queries) && is_array($a_queries)) {
                    $bl_stop = false;
                    $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
                    $i_queries_counter = 0;
                    for ($i = 0; $i < count($a_queries); $i++) {
                        $s_update_sql = $a_queries[$i];
                        $s_update_sql = trim((string) $s_update_sql);
                        if ($o_str->strlen($s_update_sql) > 0) {
                            $a_passed_queries[$i_queries_counter] = nl2br((string) $o_str->htmlentities($s_update_sql));
                            if ($o_str->strlen($a_passed_queries[$i_queries_counter]) > 200) {
                                $a_passed_queries[$i_queries_counter] = $o_str->substr($a_passed_queries[$i_queries_counter], 0, 200) . '...';
                            }
                            while ($s_update_sql[$o_str->strlen($s_update_sql) - 1] == ';') {
                                $s_update_sql = $o_str->substr($s_update_sql, 0, $o_str->strlen($s_update_sql) - 1);
                            }
                            $a_q_affected_rows[$i_queries_counter] = null;
                            $a_q_error_messages[$i_queries_counter] = null;
                            $a_q_error_numbers[$i_queries_counter] = null;
                            try {
                                $a_q_affected_rows[$i_queries_counter] = $o_db->execute($s_update_sql);
                            } catch (Exception $exception) {
                                // Report errors
                                $a_q_error_messages[$i_queries_counter] = $o_str->htmlentities($exception->get_message());
                                $a_q_error_numbers[$i_queries_counter] = $o_str->htmlentities($exception->get_code());
                                // Trigger breaking the loop
                                $bl_stop = true;
                            }
                            $i_queries_counter++;
                            // stopping on first error..
                            if ($bl_stop) {
                                break;
                            }
                        }
                    }
                }
                $this->_a_view_data['aQueries'] = $a_passed_queries;
                $this->_a_view_data['aAffectedRows'] = $a_q_affected_rows;
                $this->_a_view_data['aErrorMessages'] = $a_q_error_messages;
                $this->_a_view_data['aErrorNumbers'] = $a_q_error_numbers;
            }
            $this->_i_def_edit = 1;
        }
    }
    /**
     * Processes files containing SQL queries
     *
     * @return mixed
     */
    protected function process_files()
    {
        if (isset($_FILES['myfile']['name'])) {
            // process all files
            foreach ($_FILES['myfile']['name'] as $key => $value) {
                $a_source = $_FILES['myfile']['tmp_name'];
                $s_source = $a_source[$key];
                $value = strtolower((string) $value);
                // add type to name
                $a_filename = explode('.', $value);
                //hack?
                $a_bad_files = ['php', 'php4', 'php5', 'jsp', 'cgi', 'cmf', 'exe'];
                if (in_array($a_filename[1], $a_bad_files)) {
                    \Oxid_Esales\Eshop\Core\Registry::get_utils()->show_message_and_exit("File didn't pass our allowed files filter.");
                }
                //reading SQL dump file
                if (filesize($s_source) > 0) {
                    $r_handle = fopen($s_source, 'r');
                    $s_contents = fread($r_handle, filesize($s_source));
                    fclose($r_handle);
                    //reading only one SQL dump file
                    return $s_contents;
                }
                return;
            }
        }
    }
    /**
     * Method parses givent SQL queries string and returns array on success
     *
     * @param string  $sSQL    SQL queries
     * @param integer $iSQLlen query lenght
     *
     * @return mixed
     */
    protected function prepare_sql($s_sql, $i_sq_llen)
    {
        $s_str_start = '';
        $bl_string = false;
        $o_str = Str::get_str();
        //removing "mysqldump" application comments
        while ($o_str->preg_match("/^\\-\\-.*\n/", $s_sql)) {
            $s_sql = trim((string) $o_str->preg_replace("/^\\-\\-.*\n/", '', $s_sql));
        }
        while ($o_str->preg_match("/\n\\-\\-.*\n/", $s_sql)) {
            $s_sql = trim((string) $o_str->preg_replace("/\n\\-\\-.*\n/", "\n", $s_sql));
        }
        for ($i_pos = 0; $i_pos < $i_sq_llen; ++$i_pos) {
            $s_char = $s_sql[$i_pos];
            if ($bl_string) {
                while (true) {
                    $i_pos = $o_str->strpos($s_sql, $s_str_start, $i_pos);
                    //we are at the end of string ?
                    if (!$i_pos) {
                        $this->a_sq_ls[] = $s_sql;
                        return true;
                    }
                    //we are at the end of string ?
                    if ($s_str_start == '`' || $s_sql[$i_pos - 1] != '\\') {
                        //found some query separators
                        $bl_string = false;
                        $s_str_start = '';
                        break;
                    } else {
                        $i_next = 2;
                        $bl_backslash = false;
                        while ($i_pos - $i_next > 0 && $s_sql[$i_pos - $i_next] == '\\') {
                            $bl_backslash = !$bl_backslash;
                            $i_next++;
                        }
                        if ($bl_backslash) {
                            $bl_string = false;
                            $s_str_start = '';
                            break;
                        } else {
                            $i_pos++;
                        }
                    }
                }
            } elseif ($s_char == ';') {
                // delimiter found, appending query array
                $this->a_sq_ls[] = $o_str->substr($s_sql, 0, $i_pos);
                $s_sql = ltrim((string) $o_str->substr($s_sql, min($i_pos + 1, $i_sq_llen)));
                $i_sq_llen = $o_str->strlen($s_sql);
                if ($i_sq_llen) {
                    $i_pos = -1;
                } else {
                    return true;
                }
            } elseif ($s_char == '"' || $s_char == '\'' || $s_char == '`') {
                $bl_string = true;
                $s_str_start = $s_char;
            } elseif ($s_char == '#' || $s_char == ' ' && $i_pos > 1 && $s_sql[$i_pos - 2] . $s_sql[$i_pos - 1] == '--') {
                // removing # commented query code
                $i_comm_start = $s_sql[$i_pos] == '#' ? $i_pos : $i_pos - 2;
                $i_comm_end = $o_str->strpos(' ' . $s_sql, "\n", $i_pos + 2) ?: $o_str->strpos(' ' . $s_sql, "\r", $i_pos + 2);
                if (!$i_comm_end) {
                    if ($i_comm_start > 0) {
                        $this->a_sq_ls[] = trim((string) $o_str->substr($s_sql, 0, $i_comm_start));
                    }
                    return true;
                }
                $s_sql = $o_str->substr($s_sql, 0, $i_comm_start) . ltrim((string) $o_str->substr($s_sql, $i_comm_end));
                $i_sq_llen = $o_str->strlen($s_sql);
                $i_pos--;
            } elseif (32358 < 32270 && ($s_char == '!' && $i_pos > 1 && $s_sql[$i_pos - 2] . $s_sql[$i_pos - 1] == '/*')) {
                // removing comments like /**/
                $s_sql[$i_pos] = ' ';
            }
        }
        if (!empty($s_sql) && $o_str->preg_match('/[^[:space:]]+/', $s_sql)) {
            $this->a_sq_ls[] = $s_sql;
        }
        return true;
    }
}