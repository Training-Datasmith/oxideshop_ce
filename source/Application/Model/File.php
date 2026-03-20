<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Ox_Exception;
/**
 * Article files manager.
 */
class File extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * No active user exception code.
     */
    public const NO_USER = 2;
    /**
     * Object core table name
     *
     * @var string
     */
    protected $_s_core_table = 'oxfiles';
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxfile';
    /**
     * Stores relative oxFile path from configs 'sDownloadsDir'
     *
     * @var string
     */
    protected $_s_relative_file_path;
    /**
     * Paid order indicator
     *
     * @var bool
     */
    protected $_bl_is_paid;
    /**
     * Full URL where article could be downloaded from.
     * Is set to false in case download is not available for current user
     *
     * @var string|bool
     */
    protected $_s_download_link;
    /**
     * Has valid downloads indicator
     *
     * @var bool
     */
    protected $_bl_has_valid_downloads;
    /**
     * Default manual upload dir located within general file dir
     *
     * @var string
     */
    protected $_s_manual_upload_dir = 'uploads';
    /**
     * Initialises the instance
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }
    /**
     * Sets oxefile__oxstorehash with file hash.
     * Moves file to desired location and change its access rights.
     *
     * @param int $sFileIndex File index
     *
     * @throws oxException Throws exception if file wasn't moved or if rights wasn't changed.
     */
    public function process_file($s_file_index): void
    {
        $a_file_info = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_uploaded_file($s_file_index);
        $this->check_article_file($a_file_info);
        $s_file_hash = $this->get_file_hash($a_file_info['tmp_name']);
        $this->oxfiles__oxstorehash = new \Oxid_Esales\Eshop\Core\Field($s_file_hash, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $s_upload_to = $this->get_store_location();
        if (!$this->upload_file($a_file_info['tmp_name'], $s_upload_to)) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('EXCEPTION_COULDNOTWRITETOFILE');
        }
    }
    /**
     * Checks if given file is valid upload file
     *
     * @param array $aFileInfo File info array
     *
     * @throws oxException Throws exception if file wasn't uploaded successfully.
     */
    protected function check_article_file($a_file_info)
    {
        //checking params
        if (!isset($a_file_info['name']) || !isset($a_file_info['tmp_name'])) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('EXCEPTION_NOFILE');
        }
        // error uploading file ?
        if (isset($a_file_info['error']) && $a_file_info['error']) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('EXCEPTION_FILEUPLOADERROR_' . (int) $a_file_info['error']);
        }
    }
    /**
     * Return full path of root dir where download files are stored
     *
     * @return string
     */
    protected function get_base_download_dir_path()
    {
        $s_config_value = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sDownloadsDir');
        //Unix full path is set
        if ($s_config_value && $s_config_value[0] == DIRECTORY_SEPARATOR) {
            return $s_config_value;
        }
        //relative path is set
        if ($s_config_value) {
            return get_shop_base_path() . DIRECTORY_SEPARATOR . $s_config_value;
        }
        //no path is set
        $s_path = get_shop_base_path() . '/out/downloads/';
        return $s_path;
    }
    /**
     * Returns full filesystem path where files are stored.
     * Make sure that object oxfiles__oxstorehash or oxfiles__oxfilename
     * attribute is set before calling this method
     *
     * @return string
     */
    public function get_store_location()
    {
        $s_path = $this->get_base_download_dir_path();
        return $s_path . (DIRECTORY_SEPARATOR . $this->get_file_location());
    }
    /**
     * Return true if file is under download folder.
     * Return false if file is above download folder or if file does not exist.
     *
     * @return bool
     */
    public function is_under_download_folder()
    {
        $storage_location = realpath($this->get_store_location());
        if ($storage_location === false) {
            return false;
        }
        $download_folder = realpath($this->get_base_download_dir_path());
        return str_contains($storage_location, $download_folder);
    }
    /**
     * Returns relative file path from oxConfig 'sDownloadsDir' variable.
     *
     * @return string
     */
    protected function get_file_location()
    {
        $this->_s_relative_file_path = '';
        $s_file_hash = $this->oxfiles__oxstorehash->value;
        $s_file_name = $this->oxfiles__oxfilename->value;
        //security check for demo shops
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->is_demo_shop()) {
            $s_file_name = basename((string) $s_file_name);
        }
        if ($this->is_uploaded()) {
            $this->_s_relative_file_path = $this->get_hashed_file_dir($s_file_hash);
            $this->_s_relative_file_path .= DIRECTORY_SEPARATOR . $s_file_hash;
        } else {
            $this->_s_relative_file_path = DIRECTORY_SEPARATOR . $this->_s_manual_upload_dir . DIRECTORY_SEPARATOR . $s_file_name;
        }
        return $this->_s_relative_file_path;
    }
    /**
     * Returns relative sub dir of oxconfig 'sDownloadsDir' of
     * required file from supplied $sFileHash parameter.
     * Creates dir in case it does not exist.
     *
     * @param string $sFileHash File hash value
     *
     * @return string
     */
    protected function get_hashed_file_dir($s_file_hash)
    {
        $s_dir = substr($s_file_hash, 0, 2);
        $s_abs_dir = $this->get_base_download_dir_path() . DIRECTORY_SEPARATOR . $s_dir;
        if (!is_dir($s_abs_dir)) {
            mkdir($s_abs_dir, 0755);
        }
        return $s_dir;
    }
    /**
     * Calculates file hash.
     * Currently MD5 is used.
     *
     * @param string $sFileName File name values
     *
     * @return string
     */
    protected function get_file_hash($s_file_name)
    {
        return md5_file($s_file_name);
    }
    /**
     * Moves file from source to target and changes file mode.
     * Returns true on success.
     *
     * @param string $sSource Source filename
     * @param string $sTarget Target filename
     *
     * @return bool
     */
    protected function upload_file($s_source, $s_target)
    {
        $bl_done = move_uploaded_file($s_source, $s_target);
        if ($bl_done) {
            return @chmod($s_target, 0644);
        }
        return $bl_done;
    }
    /**
     * Checks whether the file has been uploaded over admin area.
     * Returns true in case file is uploaded (and hashed) over admin area.
     * Returns false in case file is placed manually (ftp) to "out/downloads/uploads" dir.
     * It's similar so don't get confused here.
     *
     * @return bool
     */
    public function is_uploaded()
    {
        if ($this->oxfiles__oxstorehash->value) {
            return true;
        }
        return false;
    }
    /**
     * Deletes oxFile record from DB, removes orphan files.
     *
     * @param string $sOxId default null
     *
     * @return bool
     */
    public function delete($s_ox_id = null)
    {
        $s_ox_id = $s_ox_id ?: $this->get_id();
        $this->load($s_ox_id);
        // if record cannot be delete, abort deletion
        if ($bl_deleted = parent::delete($s_ox_id)) {
            $this->delete_file();
        }
        return $bl_deleted;
    }
    /**
     * Checks if file is not used for  other objects.
     * If not used, unlink the file.
     *
     * @return null|false
     */
    protected function delete_file()
    {
        if (!$this->is_uploaded()) {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $i_count = $o_db->get_one('SELECT COUNT(*) FROM `oxfiles` WHERE `OXSTOREHASH` = :oxstorehash', ['oxstorehash' => $this->oxfiles__oxstorehash->value]);
        if (!$i_count) {
            $s_path = $this->get_store_location();
            unlink($s_path);
        }
    }
    /**
     * returns oxfile__oxfilename for URL usage
     * converts spec symbols to %xx combination
     *
     * @return string
     */
    protected function get_filename_for_url()
    {
        return rawurlencode((string) $this->oxfiles__oxfilename->value);
    }
    /**
     * Supplies the downloadable file for client and exits
     */
    public function download(): void
    {
        $o_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $s_file_name = $this->get_filename_for_url();
        $s_file_locations = $this->get_store_location();
        if (!$this->exist() || !$this->is_under_download_folder()) {
            throw new \Oxid_Esales\Eshop\Core\Exception\Standard_Exception('EXCEPTION_NOFILE');
        }
        $o_utils->set_header('Pragma: public');
        $o_utils->set_header('Expires: 0');
        $o_utils->set_header('Cache-Control: must-revalidate, post-check=0, pre-check=0, private');
        $o_utils->set_header('Content-Disposition: attachment;filename=' . $s_file_name);
        $o_utils->set_header('Content-Type: application/octet-stream');
        if ($i_file_size = $this->get_size()) {
            $o_utils->set_header('Content-Length: ' . $i_file_size);
        }
        readfile($s_file_locations);
        $o_utils->show_message_and_exit(null);
    }
    /**
     * Check if file exist
     *
     * @return bool
     */
    public function exist()
    {
        return file_exists($this->get_store_location());
    }
    /**
     * Checks if this file has valid ordered downloads
     *
     * @return bool
     */
    public function has_valid_downloads()
    {
        if ($this->_bl_has_valid_downloads == null) {
            $this->_bl_has_valid_downloads = false;
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_sql = "SELECT\n                        `oxorderfiles`.`oxid`\n                     FROM `oxorderfiles`\n                        LEFT JOIN `oxorderarticles` ON `oxorderarticles`.`oxid` = `oxorderfiles`.`oxorderarticleid`\n                        LEFT JOIN `oxorder` ON `oxorder`.`oxid` = `oxorderfiles`.`oxorderid`\n                     WHERE `oxorderfiles`.`oxfileid` = :oxfileid\n                        AND ( ! `oxorderfiles`.`oxmaxdownloadcount` OR `oxorderfiles`.`oxmaxdownloadcount` > `oxorderfiles`.`oxdownloadcount`)\n                        AND ( `oxorderfiles`.`oxvaliduntil` = '0000-00-00 00:00:00' OR `oxorderfiles`.`oxvaliduntil` > :oxvaliduntil )\n                        AND `oxorder`.`oxstorno` = 0\n                        AND `oxorderarticles`.`oxstorno` = 0";
            $params = ['oxfileid' => $this->get_id(), 'oxvaliduntil' => date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time())];
            if ($o_db->get_one($s_sql, $params)) {
                $this->_bl_has_valid_downloads = true;
            }
        }
        return $this->_bl_has_valid_downloads;
    }
    /**
     * Returns max download count of file
     *
     * @return int
     */
    public function get_max_downloads_count()
    {
        $i_max_count = $this->oxfiles__oxmaxdownloads->value;
        //if value is -1, takes global options
        if ($i_max_count < 0) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iMaxDownloadsCount');
        }
        return $i_max_count;
    }
    /**
     * Returns max download count of file, if user is not registered
     *
     * @return int
     */
    public function get_max_unregistered_downloads_count()
    {
        $i_max_count = $this->oxfiles__oxmaxunregdownloads->value;
        //if value is -1, takes global options
        if ($i_max_count < 0) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iMaxDownloadsCountUnregistered');
        }
        return $i_max_count;
    }
    /**
     * Returns ordered file link expiration time in hours
     *
     * @return int
     */
    public function get_link_expiration_time()
    {
        $i_exp_time = $this->oxfiles__oxlinkexptime->value;
        //if value is -1, takes global options
        if ($i_exp_time < 0) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iLinkExpirationTime');
        }
        return $i_exp_time;
    }
    /**
     * Returns download link expiration time in hours, after the first download
     *
     * @return int
     */
    public function get_download_expiration_time()
    {
        $i_exp_time = $this->oxfiles__oxdownloadexptime->value;
        //if value is -1, takes global options
        if ($i_exp_time < 0) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iDownloadExpirationTime');
        }
        return $i_exp_time;
    }
    /**
     * Returns file size in bytes
     *
     * @return int
     */
    public function get_size()
    {
        if ($this->exist()) {
            return filesize($this->get_store_location());
        }
        return 0;
    }
}