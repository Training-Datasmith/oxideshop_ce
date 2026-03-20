<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Article file link manager.
 */
class Order_File extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Object core table name
     *
     * @var string
     */
    protected $_s_core_table = 'oxorderfiles';
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxorderfile';
    /**
     * Initialises the instance
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxorderfiles');
    }
    /**
     * reset order files downloadcount and / or expration times
     */
    public function reset(): void
    {
        $o_article_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
        $o_article_file->load($this->oxorderfiles__oxfileid->value);
        if (file_exists($o_article_file->get_store_location())) {
            $this->oxorderfiles__oxdownloadcount = new \Oxid_Esales\Eshop\Core\Field(0);
            $this->oxorderfiles__oxfirstdownload = new \Oxid_Esales\Eshop\Core\Field('0000-00-00 00:00:00');
            $this->oxorderfiles__oxlastdownload = new \Oxid_Esales\Eshop\Core\Field('0000-00-00 00:00:00');
            $i_expiration_time = $this->oxorderfiles__oxlinkexpirationtime->value * 3600;
            $s_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
            $s_date = date('Y-m-d H:i:s', $s_now + $i_expiration_time);
            $this->oxorderfiles__oxvaliduntil = new \Oxid_Esales\Eshop\Core\Field($s_date);
            $this->oxorderfiles__oxresetcount = new \Oxid_Esales\Eshop\Core\Field($this->oxorderfiles__oxresetcount->value + 1);
        }
    }
    /**
     * set order id
     *
     * @param string $sOrderId - order id
     */
    public function set_order_id($s_order_id): void
    {
        $this->oxorderfiles__oxorderid = new \Oxid_Esales\Eshop\Core\Field($s_order_id);
    }
    /**
     * set order article id
     *
     * @param string $sOrderArticleId - order article id
     */
    public function set_order_article_id($s_order_article_id): void
    {
        $this->oxorderfiles__oxorderarticleid = new \Oxid_Esales\Eshop\Core\Field($s_order_article_id);
    }
    /**
     * set shop id
     *
     * @param string $sShopId - shop id
     */
    public function set_shop_id($s_shop_id): void
    {
        $this->oxorderfiles__oxshopid = new \Oxid_Esales\Eshop\Core\Field($s_shop_id);
    }
    /**
     * Set file and download options
     *
     * @param string $sFileName               file name
     * @param string $sFileId                 file id
     * @param int    $iMaxDownloadCounts      max download count
     * @param int    $iExpirationTime         main download time after order in times
     * @param int    $iExpirationDownloadTime download time after first download in hours
     */
    public function set_file($s_file_name, $s_file_id, $i_max_download_counts, $i_expiration_time, $i_expiration_download_time): void
    {
        $s_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $s_date = date('Y-m-d G:i', $s_now + $i_expiration_time * 3600);
        $this->oxorderfiles__oxfileid = new \Oxid_Esales\Eshop\Core\Field($s_file_id);
        $this->oxorderfiles__oxfilename = new \Oxid_Esales\Eshop\Core\Field($s_file_name);
        $this->oxorderfiles__oxmaxdownloadcount = new \Oxid_Esales\Eshop\Core\Field($i_max_download_counts);
        $this->oxorderfiles__oxlinkexpirationtime = new \Oxid_Esales\Eshop\Core\Field($i_expiration_time);
        $this->oxorderfiles__oxdownloadexpirationtime = new \Oxid_Esales\Eshop\Core\Field($i_expiration_download_time);
        $this->oxorderfiles__oxvaliduntil = new \Oxid_Esales\Eshop\Core\Field($s_date);
    }
    /**
     * Returns downloadable file size in bytes.
     *
     * @return int
     */
    public function get_file_size()
    {
        $o_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
        $o_file->load($this->oxorderfiles__oxfileid->value);
        return $o_file->get_size();
    }
    /**
     * returns long name
     *
     * @param string $sFieldName - field name
     *
     * @return string
     */
    protected function get_field_long_name($s_field_name)
    {
        $a_field_names = ['oxorderfiles__oxarticletitle', 'oxorderfiles__oxarticleartnum', 'oxorderfiles__oxordernr', 'oxorderfiles__oxorderdate', 'oxorderfiles__oxispaid', 'oxorderfiles__oxpurchasedonly'];
        if (in_array($s_field_name, $a_field_names)) {
            return $s_field_name;
        }
        return parent::get_field_long_name($s_field_name);
    }
    /**
     * Checks if order file is still available to download
     *
     * @return bool
     */
    public function is_valid()
    {
        if (!(!$this->oxorderfiles__oxmaxdownloadcount->value || $this->oxorderfiles__oxdownloadcount->value < $this->oxorderfiles__oxmaxdownloadcount->value)) {
            return false;
        }
        if (!$this->oxorderfiles__oxlinkexpirationtime->value && !$this->oxorderfiles__oxdownloadxpirationtime->value) {
            return true;
        }
        $s_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $i_timestamp = strtotime((string) $this->oxorderfiles__oxvaliduntil->value);
        if (!$i_timestamp || $i_timestamp > $s_now) {
            return true;
        }
        return false;
    }
    /**
     * returns state payed or not the order
     *
     * @return bool
     */
    public function is_paid()
    {
        return $this->oxorderfiles__oxispaid->value;
    }
    /**
     * returns date ant time
     *
     * @return bool
     */
    public function get_valid_until()
    {
        return substr((string) $this->oxorderfiles__oxvaliduntil->value, 0, 16);
    }
    /**
     * returns date ant time
     *
     * @return bool
     */
    public function get_left_download_count()
    {
        $i_left = $this->oxorderfiles__oxmaxdownloadcount->value - $this->oxorderfiles__oxdownloadcount->value;
        if ($i_left < 0) {
            return 0;
        }
        return $i_left;
    }
    /**
     * Checks if download link is valid, changes count, if first download changes valid until
     *
     * @return bool
     */
    public function process_order_file()
    {
        if ($this->is_valid()) {
            //first download
            if (!$this->oxorderfiles__oxdownloadcount->value) {
                $this->oxorderfiles__oxdownloadcount = new \Oxid_Esales\Eshop\Core\Field(1);
                $i_expiration_time = $this->oxorderfiles__oxdownloadexpirationtime->value * 3600;
                $i_time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
                $this->oxorderfiles__oxvaliduntil = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', $i_time + $i_expiration_time));
                $this->oxorderfiles__oxfirstdownload = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', $i_time));
                $this->oxorderfiles__oxlastdownload = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', $i_time));
            } else {
                $this->oxorderfiles__oxdownloadcount = new \Oxid_Esales\Eshop\Core\Field($this->oxorderfiles__oxdownloadcount->value + 1);
                $i_time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
                $this->oxorderfiles__oxlastdownload = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', $i_time));
            }
            $this->save();
            return $this->oxorderfiles__oxfileid->value;
        }
        return false;
    }
    /**
     * Gets field id.
     *
     * @return mixed
     */
    public function get_file_id()
    {
        return $this->oxorderfiles__oxfileid->value;
    }
}