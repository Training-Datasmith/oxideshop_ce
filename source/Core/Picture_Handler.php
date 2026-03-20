<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Core\Base;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Symfony\Component\Filesystem\Path;
/**
 * class for pictures processing
 */
class Picture_Handler extends Base
{
    /**
     * Deletes master picture and all images generated from it.
     * If third parameter is false, skips master image delete, only
     * all generated images will be deleted.
     *
     * @param Article $oObject               article object
     * @param int     $iIndex                master picture index
     * @param bool    $blDeleteMasterPicture delete master picture, default is true
     */
    public function delete_article_master_picture($o_object, $i_index, $bl_delete_master_picture = true): void
    {
        $my_config = Registry::get_config();
        $my_utils_pic = Registry::get_utils_pic();
        $o_utils_file = Registry::get_utils_file();
        $bl_generated_images_only = !$bl_delete_master_picture;
        $image_field_name = "oxpic{$i_index}";
        $image_field_value = $o_object->get_field_data($image_field_name);
        $master_image_filename = $image_field_value ? basename((string) $image_field_value) : null;
        if (!$master_image_filename || $master_image_filename === $this->get_nopic_filename()) {
            return;
        }
        $a_pic = ['sField' => $image_field_name, 'sDir' => $o_utils_file->get_image_dir_by_type('M' . $i_index, $bl_generated_images_only), 'sFileName' => $master_image_filename];
        $s_abs_dyn_image_dir = $my_config->get_picture_dir(false);
        $bl_deleted = $my_utils_pic->safe_picture_delete($a_pic['sFileName'], $s_abs_dyn_image_dir . $a_pic['sDir'], 'oxarticles', $a_pic['sField']);
        if ($bl_deleted) {
            $this->delete_zoom_picture($o_object, $i_index);
            $a_del_pics = [];
            if ($i_index == 1) {
                // deleting generated main icon picture if custom main icon
                // file name not equal with generated from master picture
                if ($this->get_main_icon_name($master_image_filename) != basename((string) $o_object->oxarticles__oxicon->value)) {
                    $a_del_pics[] = ['sField' => 'oxpic1', 'sDir' => $o_utils_file->get_image_dir_by_type('ICO', $bl_generated_images_only), 'sFileName' => $this->get_main_icon_name($master_image_filename)];
                }
                // deleting generated thumbnail picture if custom thumbnail
                // file name not equal with generated from master picture
                if ($this->get_thumb_name($master_image_filename) != basename((string) $o_object->oxarticles__oxthumb->value)) {
                    $a_del_pics[] = ['sField' => 'oxpic1', 'sDir' => $o_utils_file->get_image_dir_by_type('TH', $bl_generated_images_only), 'sFileName' => $this->get_thumb_name($master_image_filename)];
                }
            }
            foreach ($a_del_pics as $a_pic) {
                $my_utils_pic->safe_picture_delete($a_pic['sFileName'], $s_abs_dyn_image_dir . $a_pic['sDir'], 'oxarticles', $a_pic['sField']);
            }
        }
        //deleting custom zoom pic (compatibility mode)
        if ($o_object->{'oxarticles__oxzoom' . $i_index}->value) {
            if (basename((string) $o_object->{'oxarticles__oxzoom' . $i_index}->value) !== $this->get_nopic_filename()) {
                // deleting old zoom picture
                $this->delete_zoom_picture($o_object, $i_index);
            }
        }
    }
    /**
     * Deletes custom main icon, which name is specified in oxicon field.
     *
     * @param Article $oObject article object
     */
    public function delete_main_icon($o_object): void
    {
        if ($s_main_icon = $o_object->oxarticles__oxicon->value) {
            $s_path = Registry::get_config()->get_picture_dir(false) . Registry::get_utils_file()->get_image_dir_by_type('ICO');
            Registry::get_utils_pic()->safe_picture_delete($s_main_icon, $s_path, 'oxarticles', 'oxicon');
        }
    }
    /**
     * Deletes custom thumbnail, which name is specified in oxthumb field.
     *
     * @param Article $oObject article object
     */
    public function delete_thumbnail($o_object): void
    {
        if ($s_thumb = $o_object->oxarticles__oxthumb->value) {
            // deleting article main icon and thumb picture
            $s_path = Registry::get_config()->get_picture_dir(false) . Registry::get_utils_file()->get_image_dir_by_type('TH');
            Registry::get_utils_pic()->safe_picture_delete($s_thumb, $s_path, 'oxarticles', 'oxthumb');
        }
    }
    /**
     * Deletes custom zoom picture, which name is specified in oxzoom field.
     *
     * @param Article $oObject article object
     * @param int     $iIndex  zoom picture index
     */
    public function delete_zoom_picture($o_object, $i_index): void
    {
        // checking if oxzoom field exists
        $o_db_handler = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        $i_zoom_pic_count = (int) Registry::get_config()->get_config_param('iZoomPicCount');
        if ($i_index > $i_zoom_pic_count || !$o_db_handler->field_exists('oxzoom' . $i_index, 'oxarticles')) {
            if ($s_zoom_pic_name = $this->get_zoom_name($o_object->{'oxarticles__oxpic' . $i_index}->value, $i_index)) {
                $s_field_to_check = 'oxpic' . $i_index;
            } else {
                return;
            }
        } else {
            $s_zoom_pic_name = basename((string) $o_object->{'oxarticles__oxzoom' . $i_index}->value);
            $s_field_to_check = 'oxzoom' . $i_index;
        }
        if ($s_zoom_pic_name && $s_zoom_pic_name != $this->get_nopic_filename()) {
            // deleting zoom picture
            $s_path = Registry::get_config()->get_picture_dir(false) . Registry::get_utils_file()->get_image_dir_by_type('Z' . $i_index);
            Registry::get_utils_pic()->safe_picture_delete($s_zoom_pic_name, $s_path, 'oxarticles', $s_field_to_check);
        }
    }
    /**
     * Returns article picture icon name for selected article picture
     *
     * @param string $sFilename file name
     *
     * @return string
     */
    public function get_icon_name($s_filename)
    {
        return $s_filename;
    }
    /**
     * Returns article main icon name generated from master picture
     *
     * @param string $sMasterImageFile master image file name
     *
     * @return string
     */
    public function get_main_icon_name($s_master_image_file)
    {
        return $this->get_base_master_image_file_name($s_master_image_file);
    }
    /**
     * Returns thumb image name generated from master picture
     *
     * @param string $sMasterImageFile master image file name
     *
     * @return string
     */
    public function get_thumb_name($s_master_image_file)
    {
        return basename($s_master_image_file);
    }
    /**
     * Returns zoom image name generated from master picture
     *
     * @param string $sMasterImageFile master image file name
     * @param string $iIndex           master image index
     *
     * @return string
     */
    public function get_zoom_name($s_master_image_file, $i_index)
    {
        return basename($s_master_image_file);
    }
    /**
     * Gets master image file name and removes suffics (e.g. _p1) from file end.
     *
     * @param string $sMasterImageFile master image file name
     */
    protected function get_base_master_image_file_name($s_master_image_file)
    {
        return basename($s_master_image_file);
    }
    /**
     * Returns image sizes from provided config array
     *
     * @param mixed  $aImgSizes array or string of sizes in format x*y
     * @param string $sIndex    index in array
     *
     * @return array
     */
    public function get_image_size($a_img_sizes, $s_index = null)
    {
        $a_size = [];
        if (isset($s_index) && is_array($a_img_sizes) && isset($a_img_sizes[$s_index])) {
            $a_size = explode('*', (string) $a_img_sizes[$s_index]);
        } elseif (is_string($a_img_sizes)) {
            $a_size = explode('*', $a_img_sizes);
        }
        if (2 == count($a_size)) {
            $x = (int) $a_size[0];
            $y = (int) $a_size[1];
            if ($x && $y) {
                return $a_size;
            }
        }
        return null;
    }
    /**
     * Returns dir/url info for given image file
     *
     * @param string $sFilePath path to file
     * @param string $sFile     filename in pictures dir
     * @param bool   $blAdmin   is admin mode ?
     * @param bool   $blSSL     is ssl ?
     * @param int    $iLang     language id
     * @param int    $iShopId   shop id
     *
     * @return array
     */
    protected function get_picture_info($s_file_path, $s_file, $bl_admin = false, $bl_ssl = null, $i_lang = null, $i_shop_id = null)
    {
        // custom server as image storage?
        if ($s_alt_url = $this->get_alt_image_url($s_file_path, $s_file)) {
            return ['path' => false, 'url' => $s_alt_url];
        }
        $o_config = Registry::get_config();
        $s_path = $o_config->get_picture_path($s_file_path . $s_file, $bl_admin, $i_lang, $i_shop_id);
        if (!$s_path) {
            return ['path' => false, 'url' => false];
        }
        $s_dir_prefix = $o_config->get_out_dir();
        $s_url_prefix = $o_config->get_out_url($bl_ssl, $bl_admin, $o_config->get_config_param('blNativeImages'));
        return ['path' => $s_path, 'url' => str_replace($s_dir_prefix, $s_url_prefix, $s_path)];
    }
    public function get_alt_image_url($file_path, $file)
    {
        $alt_url = Container_Facade::get_parameter('oxid_esales.alternative_image_url') ?: null;
        if ($alt_url && !is_null($file)) {
            return Path::join($alt_url, $file_path, $file);
        }
        return $alt_url;
    }
    /**
     * Returns requested picture url. If image is not available - returns false
     *
     * @param string $sPath    path from pictures/master/
     * @param string $sFile    picture file name
     * @param string $sSize    picture sizes (x, y)
     * @param string $sIndex   picture index [optional]
     * @param string|false $sAltPath alternative picture path [optional]
     * @param bool   $bSsl     Whether to force SSL
     *
     * @return string|bool
     */
    public function get_pic_url($s_path, $s_file, $s_size, $s_index = null, $s_alt_path = false, $b_ssl = null)
    {
        $s_url = null;
        if ($s_path && $s_file && $a_size = $this->get_image_size($s_size, $s_index)) {
            $a_pic_info = $this->get_picture_info('master/' . ($s_alt_path ?: $s_path), $s_file, $this->is_admin(), $b_ssl);
            if ($a_pic_info['url'] && $a_size[0] && $a_size[1]) {
                $s_dir_name = "{$a_size[0]}_{$a_size[1]}_" . Registry::get_config()->get_config_param('sDefaultImageQuality');
                $s_url = str_replace('/master/' . ($s_alt_path ?: $s_path), "/generated/{$s_path}{$s_dir_name}/", $a_pic_info['url']);
            }
        }
        // Add webp extension if automatic conversion is enabled
        if ($s_url !== null && Registry::get_config()->get_config_param('blConvertImagesToWebP', false) && pathinfo($s_url, PATHINFO_EXTENSION) != 'webp') {
            $s_url .= '.webp';
        }
        return $s_url;
    }
    /**
     * Returns requested product picture url. If image is not available - returns url to nopic.jpg
     *
     * @param string $sPath  path from pictures/master/
     * @param string $sFile  picture file name
     * @param string $sSize  picture sizes (x, y)
     * @param string $sIndex picture index [optional]
     * @param bool   $bSsl   Whether to force SSL
     *
     * @return string|bool
     */
    public function get_product_pic_url($s_path, $s_file, $s_size, $s_index = null, $b_ssl = null)
    {
        $s_url = null;
        if (!$s_file || !$s_url = $this->get_pic_url($s_path, $s_file, $s_size, $s_index, false, $b_ssl)) {
            return $this->get_pic_url($s_path, $this->get_nopic_filename(), $s_size, $s_index, '/', $b_ssl);
        }
        return $s_url;
    }
    private function get_nopic_filename(): string
    {
        if (Registry::get_config()->get_config_param('blConvertImagesToWebP')) {
            return 'nopic.webp';
        }
        return 'nopic.jpg';
    }
}