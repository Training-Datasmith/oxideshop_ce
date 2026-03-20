<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
/**
 * Directory reader.
 * Performs reading of file list of one shop directory
 */
class File_Collector
{
    /**
     * base directory
     *
     * @var string
     */
    protected $_s_base_directory;
    /**
     * array of collected files
     *
     * @var array
     */
    protected $_a_files;
    /**
     * Setter for working directory
     *
     * @param string $sDir Directory
     */
    public function set_base_directory($s_dir): void
    {
        if (!empty($s_dir)) {
            $this->_s_base_directory = $s_dir;
        }
    }
    /**
     * get collection files
     *
     * @return mixed
     */
    public function get_files()
    {
        return $this->_a_files;
    }
    /**
     * Add one file to collection if it exists
     *
     * @param string $sFile file name to add to collection
     *
     * @throws Exception
     */
    public function add_file(?string $s_file): bool
    {
        if (empty($s_file)) {
            throw new Exception('Parameter $sFile is empty!');
        }
        if (empty($this->_s_base_directory)) {
            throw new Exception('Base directory is not set, please use setter setBaseDirectory!');
        }
        if (is_file($this->_s_base_directory . $s_file)) {
            $this->_a_files[] = $s_file;
            return true;
        }
        return false;
    }
    /**
     * browse all folders and sub-folders after files which have given extensions
     *
     * @param string  $sFolder     which is explored
     * @param array   $aExtensions list of extensions to scan - if empty all files are taken
     * @param boolean $blRecursive should directories be checked in recursive manner
     *
     * @throws exception
     */
    public function add_directory_files(?string $s_folder, $a_extensions = [], $bl_recursive = false): void
    {
        if (empty($s_folder)) {
            throw new Exception('Parameter $sFolder is empty!');
        }
        if (empty($this->_s_base_directory)) {
            throw new Exception('Base directory is not set, please use setter setBaseDirectory!');
        }
        $a_current_list = [];
        if (!is_dir($this->_s_base_directory . $s_folder)) {
            return;
        }
        $handle = opendir($this->_s_base_directory . $s_folder);
        while ($s_file = readdir($handle)) {
            if ($s_file != '.' && $s_file != '..') {
                if (is_dir($this->_s_base_directory . $s_folder . $s_file)) {
                    if ($bl_recursive) {
                        $a_result_list = $this->add_directory_files($s_folder . $s_file . '/', $a_extensions, $bl_recursive);
                        if (is_array($a_result_list)) {
                            $a_current_list = array_merge($a_current_list, $a_result_list);
                        }
                    }
                } else {
                    $s_ext = substr(strrchr($s_file, '.'), 1);
                    if (!empty($a_extensions) && is_array($a_extensions) && in_array($s_ext, $a_extensions) || empty($a_extensions)) {
                        $this->add_file($s_folder . $s_file);
                    }
                }
            }
        }
        closedir($handle);
    }
}