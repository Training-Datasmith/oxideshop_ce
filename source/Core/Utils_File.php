<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Exception\Exception_To_Display;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Bridge\Master_Image_Handler_Bridge_Interface;
use Symfony\Component\Filesystem\Path;
class Utils_File extends \Oxid_Esales\Eshop\Core\Base
{
    public const PROMO_PICTURE_DIR = 'promo';
    protected $_a_type_to_path = ['TC' => 'master/category/thumb', 'CICO' => 'master/category/icon', 'PICO' => 'master/category/promo_icon', 'MTHU' => 'master/manufacturer/thumb', 'MPIC' => 'master/manufacturer/picture', 'MICO' => 'master/manufacturer/icon', 'MPICO' => 'master/manufacturer/promo_icon', 'VICO' => 'master/vendor/icon', 'PROMO' => self::PROMO_PICTURE_DIR, 'ICO' => 'master/product/icon', 'TH' => 'master/product/thumb', 'M1' => 'master/product/1', 'M2' => 'master/product/2', 'M3' => 'master/product/3', 'M4' => 'master/product/4', 'M5' => 'master/product/5', 'M6' => 'master/product/6', 'M7' => 'master/product/7', 'M8' => 'master/product/8', 'M9' => 'master/product/9', 'M10' => 'master/product/10', 'M11' => 'master/product/11', 'M12' => 'master/product/12', 'P1' => '1', 'P2' => '2', 'P3' => '3', 'P4' => '4', 'P5' => '5', 'P6' => '6', 'P7' => '7', 'P8' => '8', 'P9' => '9', 'P10' => '10', 'P11' => '11', 'P12' => '12', 'Z1' => 'z1', 'Z2' => 'z2', 'Z3' => 'z3', 'Z4' => 'z4', 'Z5' => 'z5', 'Z6' => 'z6', 'Z7' => 'z7', 'Z8' => 'z8', 'Z9' => 'z9', 'Z10' => 'z10', 'Z11' => 'z11', 'Z12' => 'z12', 'WP' => 'master/wrapping', 'FL' => 'media'];
    /**
     * Denied file types
     *
     * @var array
     */
    protected $_a_bad_files = ['php', 'php3', 'php4', 'php5', 'phps', 'php6', 'jsp', 'cgi', 'cmf', 'exe', 'phtml', 'pht', 'phar'];
    /**
     * Allowed to upload files in demo mode ( "white list")
     *
     * @var array
     */
    protected $_a_allowed_files = ['gif', 'jpg', 'jpeg', 'png', 'webp', 'pdf'];
    /**
     * Counts how many new files added.
     *
     * @var integer
     */
    protected $_i_new_files_counter = 0;
    public function get_new_files_counter()
    {
        return $this->_i_new_files_counter;
    }
    protected function set_new_files_counter($i_new_files_counter)
    {
        $this->_i_new_files_counter = (int) $i_new_files_counter;
    }
    public function normalize_dir($s_dir)
    {
        if (isset($s_dir) && $s_dir != '' && !str_ends_with((string) $s_dir, '/')) {
            $s_dir .= '/';
        }
        return $s_dir;
    }
    public function copy_dir($s_source_dir, $s_target_dir): void
    {
        $o_str = Str::get_str();
        $handle = opendir($s_source_dir);
        while (false !== $file = readdir($handle)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($s_source_dir . '/' . $file)) {
                    $s_new_source_dir = $s_source_dir . '/' . $file;
                    $s_new_target_dir = $s_target_dir . '/' . $file;
                    if (strcasecmp($file, 'CVS') && strcasecmp($file, '.svn')) {
                        @mkdir($s_new_target_dir, 0777);
                        $this->copy_dir($s_new_source_dir, $s_new_target_dir);
                    }
                } else {
                    $s_source_file = $s_source_dir . '/' . $file;
                    $s_target_file = $s_target_dir . '/' . $file;
                    if (!$o_str->strstr($s_source_dir, 'dyn_images') || $file == 'nopic.jpg' || $file == 'nopic_ico.jpg') {
                        @copy($s_source_file, $s_target_file);
                    }
                }
            }
        }
        closedir($handle);
    }
    public function delete_dir($s_source_dir)
    {
        if (is_dir($s_source_dir)) {
            if ($o_dir = dir($s_source_dir)) {
                while (false !== $s_file = $o_dir->read()) {
                    if ($s_file == '.') {
                        continue;
                    }
                    if ($s_file == '..') {
                        continue;
                    }
                    if (!$this->delete_dir($o_dir->path . DIRECTORY_SEPARATOR . $s_file)) {
                        $o_dir->close();
                        return false;
                    }
                }
                $o_dir->close();
                return rmdir($s_source_dir);
            }
        } elseif (file_exists($s_source_dir)) {
            return unlink($s_source_dir);
        }
    }
    public function read_remote_file_as_string($s_path)
    {
        $s_ret = '';
        $h_file = @fopen($s_path, 'r');
        if ($h_file) {
            socket_set_timeout($h_file, 2);
            while (!feof($h_file)) {
                $s_line = fgets($h_file, 4096);
                $s_ret .= $s_line;
            }
            fclose($h_file);
        }
        return $s_ret;
    }
    /**
     * Prepares image file name
     *
     * @param object $sValue     uploadable file name
     * @param string $sType      image type
     * @param object $blDemo     if true = whecks if file type is defined in \OxidEsales\Eshop\Core\UtilsFile::_aAllowedFiles
     * @param string $sImagePath final image file location
     * @param bool   $blUnique   if TRUE - generates unique file name
     *
     * @return string
     */
    protected function prepare_image_name($s_value, $s_type, $bl_demo, $s_image_path, $bl_unique = true)
    {
        if ($s_value) {
            // add type to name
            $a_filename = explode('.', $s_value);
            $s_file_type = trim($a_filename[count($a_filename) - 1]);
            // unallowed files ?
            if (in_array($s_file_type, $this->_a_bad_files) || $bl_demo && !in_array($s_file_type, $this->_a_allowed_files)) {
                Registry::get_utils()->show_message_and_exit("File didn't pass our allowed files filter.");
            }
            // removing file type
            if (count($a_filename) > 0) {
                unset($a_filename[count($a_filename) - 1]);
            }
            $s_f_name = '';
            if (isset($a_filename[0])) {
                $s_f_name = Str::get_str()->preg_replace('/[^a-zA-Z0-9()_\.-]/', '', implode('.', $a_filename));
            }
            $s_value = $this->get_unique_file_name($s_image_path, "{$s_f_name}", $s_file_type, '', $bl_unique);
        }
        return $s_value;
    }
    /**
     * Returns image storage path
     *
     * @param string $sType image type
     *
     * @return string
     */
    protected function get_image_path($s_type)
    {
        $s_folder = array_key_exists($s_type, $this->_a_type_to_path) ? $this->_a_type_to_path[$s_type] : '0';
        return $this->normalize_dir(Registry::get_config()->get_picture_dir(false)) . "{$s_folder}/";
    }
    /**
     * Uploaded file processor (filters, etc), sets configuration parameters to
     * passed object and returns it.
     *
     * @param object $oObject          object, that parameters are modified according to passed files
     * @param array  $aFiles           name of files to process
     * @param bool   $blUseMasterImage use master image as source for processing
     * @param bool   $blUnique         TRUE - forces new file creation with unique name
     *
     * @return object
     */
    public function process_files($o_object = null, $a_files = [], $bl_use_master_image = false, $bl_unique = true)
    {
        $a_files = $a_files ?: $_FILES;
        if (isset($a_files['myfile']['name'])) {
            $o_config = Registry::get_config();
            // A. protection for demoshops - strictly defining allowed file extensions
            $bl_demo = (bool) $o_config->is_demo_shop();
            // folder where images will be processed
            $s_tmp_folder = Container_Facade::get_parameter('oxid_esales.build_directory');
            $i_new_files_counter = 0;
            $a_source = $a_files['myfile']['tmp_name'];
            $a_error = $a_files['myfile']['error'] ?? [];
            $o_ex = ox_new(Exception_To_Display::class);
            // process all files
            foreach ($a_files['myfile']['name'] as $s_key => $s_value) {
                $s_source = $a_source[$s_key];
                $i_error = $a_error[$s_key] ?? null;
                $a_filetype = explode('@', (string) $s_key);
                $s_key = $a_filetype[1] ?? null;
                $s_type = $a_filetype[0];
                $s_value = strtolower((string) $s_value);
                $s_image_path = $this->get_image_path($s_type);
                // Should translate error to user if file was uploaded
                if (UPLOAD_ERR_OK !== $i_error && UPLOAD_ERR_NO_FILE !== $i_error) {
                    $s_errors_description = $this->translate_error($i_error);
                    $o_ex->set_message($s_errors_description);
                    Registry::get_utils_view()->add_error_to_display($o_ex, false);
                }
                // checking file type and building final file name
                if ($s_source && $s_value = $this->prepare_image_name($s_value, $s_type, $bl_demo, $s_image_path, $bl_unique)) {
                    // moving to tmp folder for processing as safe mode or spec. open_basedir setup
                    // usually does not allow file modification in php's temp folder
                    $s_process_path = $s_tmp_folder . basename((string) $s_source);
                    if ($s_process_path) {
                        $destination = Path::join("{$s_image_path}{$s_value}");
                        $bl_moved = $bl_use_master_image ? $this->copy_master_image($s_source, $destination) : $this->upload_master_image($s_source, $destination);
                        if ($bl_moved) {
                            // New image successfully add.
                            $i_new_files_counter++;
                            // assign the name
                            if ($o_object && isset($o_object->{$s_key})) {
                                $o_object->{$s_key}->set_value($s_value);
                            }
                        }
                    }
                }
            }
            $this->set_new_files_counter($i_new_files_counter);
        }
        return $o_object;
    }
    /**
     * Checks if passed file exists and may be opened for reading. Returns true
     * on success.
     *
     * @param string $sFile Name of file to check
     *
     * @return bool
     */
    public function check_file($s_file)
    {
        $a_check_cache = Registry::get_session()->get_variable('checkcache');
        if (isset($a_check_cache[$s_file])) {
            return $a_check_cache[$s_file];
        }
        $bl_ret = true;
        if (!is_readable($s_file)) {
            $bl_ret = $this->url_validate($s_file);
        }
        $a_check_cache[$s_file] = $bl_ret;
        Registry::get_session()->set_variable('checkcache', $a_check_cache);
        return $bl_ret;
    }
    /**
     * Checks if given URL is accessible (HTTP-Code: 200)
     *
     * @param string $url
     *
     * @return boolean
     */
    public function url_validate($url)
    {
        return $this->is_url_schema_valid($url) && $this->is_url_accessible($url);
    }
    /**
     * Process uploaded files. Returns unique file name, on fail false
     *
     * @param string $filename form file item name
     * @param string $uploadPath RELATIVE (to container parameter oxid_shop_source_directory)
     * path for uploaded file to be copied
     *
     * @return string
     * @throws StandardException if file is not valid
     */
    public function process_file($filename, $upload_path)
    {
        $file_info = $_FILES[$filename];
        $absolute_upload_path = Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), $upload_path);
        if (!isset($file_info['name']) || !isset($file_info['tmp_name'])) {
            throw ox_new(Standard_Exception::class, 'EXCEPTION_NOFILE');
        }
        if (!Str::get_str()->preg_match('/^[\-_a-z0-9\.]+$/i', $file_info['name'])) {
            throw ox_new(Standard_Exception::class, 'EXCEPTION_FILENAMEINVALIDCHARS');
        }
        if (isset($file_info['error']) && $file_info['error']) {
            throw ox_new(Standard_Exception::class, 'EXCEPTION_FILEUPLOADERROR_' . (int) $file_info['error']);
        }
        $path_info = pathinfo((string) $file_info['name']);
        $extension = $path_info['extension'];
        $filename = $path_info['filename'];
        $allowed_upload_types = Container_Facade::get_parameter('oxid_esales.allowed_uploaded_types');
        $allowed_upload_types = array_map(strtolower(...), $allowed_upload_types);
        if (!\in_array(strtolower($extension), $allowed_upload_types, true)) {
            throw ox_new(Standard_Exception::class, 'EXCEPTION_NOTALLOWEDTYPE');
        }
        $filename = $this->get_unique_file_name($absolute_upload_path, $filename, $extension);
        $destination = Path::join($absolute_upload_path, $filename);
        if ($this->upload_master_image($file_info['tmp_name'], $destination)) {
            return $filename;
        }
        return false;
    }
    /**
     * @param string $directory
     * @param string $filename
     * @param string $extension
     * @param string $suffix
     * @param bool $unique
     * @return string
     */
    protected function get_unique_file_name($directory, $filename, $extension, $suffix = '', $unique = true)
    {
        if (!$unique) {
            return "{$filename}{$suffix}.{$extension}";
        }
        $directory = $this->normalize_dir($directory);
        $file_counter = 0;
        $temporary_name = $filename;
        $string_handler = Str::get_str();
        $master_image_handler = Container_Facade::get(Master_Image_Handler_Bridge_Interface::class);
        while ($master_image_handler->exists($this->make_path_relative_to_shop_source(Path::join($directory, "{$filename}{$suffix}.{$extension}")))) {
            $file_counter++;
            //removing "(any digit)" from file name end
            $temporary_name = $string_handler->preg_replace("/\\({$file_counter}\\)/", '', $temporary_name);
            $filename = "{$temporary_name}({$file_counter})";
        }
        return "{$filename}{$suffix}.{$extension}";
    }
    /**
     * Returns image storage path
     *
     * @param string $sType       image type
     * @param bool   $blGenerated generated image dir.
     *
     * @return string
     */
    public function get_image_dir_by_type($s_type, $bl_generated = false)
    {
        $s_folder = array_key_exists($s_type, $this->_a_type_to_path) ? $this->_a_type_to_path[$s_type] : '0';
        $s_dir = $this->normalize_dir($s_folder);
        if ($bl_generated === true) {
            return str_replace('master/', 'generated/', $s_dir);
        }
        return $s_dir;
    }
    /**
     * Translate php file upload errors to user readable format.
     *
     * @param integer $iError php file upload error number
     *
     * @return string
     */
    public function translate_error($i_error)
    {
        // Translate only if translation exist
        if ($i_error > 0 && $i_error < 9 && 5 !== $i_error) {
            return 'EXCEPTION_FILEUPLOADERROR_' . (int) $i_error;
        }
        return '';
    }
    private function is_url_schema_valid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) === false ? false : true;
    }
    private function is_url_accessible(string $url): bool
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        if ($result !== false) {
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($status_code === 200) {
                return true;
            }
        }
        return false;
    }
    private function copy_master_image(string $source, string $destination): bool
    {
        $copied = false;
        try {
            Container_Facade::get(Master_Image_Handler_Bridge_Interface::class)->copy($source, $this->make_path_relative_to_shop_source($destination));
            $copied = true;
        } catch (\Throwable $exception) {
            $this->add_error_message_to_display($exception->get_message());
        }
        return $copied;
    }
    private function upload_master_image(string $source, string $destination): bool
    {
        $uploaded = false;
        try {
            Container_Facade::get(Master_Image_Handler_Bridge_Interface::class)->upload($source, $this->make_path_relative_to_shop_source($destination));
            $uploaded = true;
        } catch (\Throwable $exception) {
            $this->add_error_message_to_display($exception->get_message());
        }
        return $uploaded;
    }
    private function add_error_message_to_display(string $message): void
    {
        $exception = ox_new(Exception_To_Display::class);
        $exception->set_message($message);
        Registry::get_utils_view()->add_error_to_display($exception, false);
    }
    private function make_path_relative_to_shop_source(string $path): string
    {
        return Path::make_relative($path, Container_Facade::get_parameter('oxid_esales.shop_source_directory'));
    }
}