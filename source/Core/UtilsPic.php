<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Exception\Exception_To_Display;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Bridge\Master_Image_Handler_Bridge_Interface;
use Symfony\Component\Filesystem\Path;
/**
 * Including pictures generator functions file
 */
require_once __DIR__ . '/utils/oxpicgenerator.php';
class Utils_Pic extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Image types 'enum'
     *
     * @var array
     */
    protected $_a_image_types = ['GIF' => IMAGETYPE_GIF, 'JPG' => IMAGETYPE_JPEG, 'PNG' => IMAGETYPE_PNG, 'JPEG' => IMAGETYPE_JPEG];
    /**
     * Resizes image to desired width and height, returns true on success.
     *
     * @param string $sSrc           Source of image file
     * @param string $sTarget        Target to write resized image file
     * @param mixed  $iDesiredWidth  Width of resized image
     * @param mixed  $iDesiredHeight Height of resized image
     *
     * @return bool
     */
    public function resize_image($s_src, $s_target, $i_desired_width, $i_desired_height)
    {
        if (file_exists($s_src) && $a_image_info = @getimagesize($s_src)) {
            $my_config = Registry::get_config();
            [$i_width, $i_height] = calc_image_size($i_desired_width, $i_desired_height, $a_image_info[0], $a_image_info[1]);
            return $this->resize($a_image_info, $s_src, null, $s_target, $i_width, $i_height, get_gd_version(), $my_config->get_config_param('blDisableTouch'), $my_config->get_config_param('sDefaultImageQuality'));
        }
        return false;
    }
    /**
     * deletes the given picutre and checks before if the picture is deletable
     *
     * @param string $sPicName        Name of picture file
     * @param string $sAbsDynImageDir the absolute image diectory, where to delete the given image ($myConfig->getPictureDir(false))
     * @param string $sTable          in which table
     * @param string $sField          table field value
     *
     * @return bool
     */
    public function safe_picture_delete($s_pic_name, $s_abs_dyn_image_dir, $s_table, $s_field)
    {
        if ($this->is_pic_deletable($s_pic_name, $s_table, $s_field)) {
            return $this->delete_picture($s_pic_name, $s_abs_dyn_image_dir);
        }
        return false;
    }
    /**
     * @param $filename
     * @param $masterImagePath
     * @return bool
     */
    protected function delete_picture($filename, $master_image_path)
    {
        if ($this->is_placeholder_image($filename) || Registry::get_config()->is_demo_shop()) {
            return false;
        }
        $removed = $this->remove_master_file(Path::join($master_image_path, $filename));
        if (!Container_Facade::get_parameter('oxid_esales.alternative_image_url')) {
            $generated_image_path = str_replace('/master/', '/generated/', $master_image_path);
            $files = glob(Path::join($generated_image_path, '*', $filename));
            if (\is_array($files)) {
                foreach ($files as $file) {
                    $removed = unlink($file);
                }
            }
        }
        return $removed;
    }
    /**
     * Checks if current picture file is used in more than one table entry, returns
     * true if one, false if more than one.
     *
     * @param string $filename Name of picture file
     * @param string $tabl   in which table
     * @param string $field   table field value
     *
     * @return bool
     */
    protected function is_pic_deletable($filename, $tabl, $field)
    {
        if (!$filename || $this->is_placeholder_image($filename)) {
            return false;
        }
        $usage_count = $this->fetch_is_image_deletable($filename, $tabl, $field);
        return $usage_count <= 1;
    }
    /**
     * Fetch the information, if the given image is deletable from the database.
     *
     * @param string $sPicName Name of image file.
     * @param string $sTable   The table in which we search for the image.
     * @param string $sField   The value of the table field.
     *
     * @return mixed
     */
    protected function fetch_is_image_deletable($s_pic_name, $s_table, $s_field)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        $query = "SELECT count(*) FROM {$s_table} WHERE {$s_field} = :picturename group by {$s_field} ";
        return $master_db->get_one($query, ['picturename' => (string) $s_pic_name]);
    }
    /**
     * Deletes picture if new is uploaded or changed
     *
     * @param object $oObject         in whitch obejct search for old values
     * @param string $sPicTable       pictures table
     * @param string $sPicField       where picture are stored
     * @param string $sPicType        how does it call in $_FILE array
     * @param string $sPicDir         directory of pic
     * @param array  $aParams         new input text array
     * @param string $sAbsDynImageDir the absolute image diectory, where to delete the given image ($myConfig->getPictureDir(false))
     */
    public function overwrite_pic($o_object, $s_pic_table, $s_pic_field, $s_pic_type, $s_pic_dir, $a_params, $s_abs_dyn_image_dir)
    {
        $s_pic = $s_pic_table . '__' . $s_pic_field;
        if (isset($o_object->{$s_pic}) && ($_FILES['myfile']['size'][$s_pic_type . '@' . $s_pic] > 0 || $a_params[$s_pic] != $o_object->{$s_pic}->value)) {
            $s_img_dir = $s_abs_dyn_image_dir . Registry::get_utils_file()->get_image_dir_by_type($s_pic_type);
            return $this->safe_picture_delete($o_object->{$s_pic}->value, $s_img_dir, $s_pic_table, $s_pic_field);
        }
        return false;
    }
    /**
     * Resizes and saves GIF image. This method was separated due to GIF transparency problems.
     *
     * @param string $sSrc            image file
     * @param string $sTarget         destination file
     * @param int    $iNewWidth       new width
     * @param int    $iNewHeight      new height
     * @param int    $iOriginalWidth  original width
     * @param int    $iOriginalHeigth original height
     * @param int    $iGDVer          GD packet version @deprecated
     * @param bool   $blDisableTouch  false if "touch()" should be called
     *
     * @return bool
     */
    protected function resize_gif($s_src, $s_target, $i_new_width, $i_new_height, $i_original_width, $i_original_heigth, $i_gd_ver, $bl_disable_touch)
    {
        return resize_gif($s_src, $s_target, $i_new_width, $i_new_height, $i_original_width, $i_original_heigth, $i_gd_ver);
    }
    /**
     * type dependant image resizing
     *
     * @param array  $aImageInfo        Contains information on image's type / width / height
     * @param string $sSrc              source image
     * @param string $hDestinationImage Destination Image
     * @param string $sTarget           Resized Image target
     * @param int    $iNewWidth         Resized Image's width
     * @param int    $iNewHeight        Resized Image's height
     * @param mixed  $iGdVer            used GDVersion, if null or false returns false @deprecated
     * @param bool   $blDisableTouch    false if "touch()" should be called for gif resizing
     * @param string $iDefQuality       quality for "imagejpeg" function
     *
     * @return bool
     */
    protected function resize($a_image_info, $s_src, $h_destination_image, $s_target, $i_new_width, $i_new_height, $i_gd_ver, $bl_disable_touch, $i_def_quality)
    {
        start_profile('PICTURE_RESIZE');
        $bl_success = false;
        switch ($a_image_info[2]) {
            //Image type
            case $this->_a_image_types['GIF']:
                //php does not process gifs until 7th July 2004 (see lzh licensing)
                if (function_exists('imagegif')) {
                    $bl_success = resize_gif($s_src, $s_target, $i_new_width, $i_new_height, $a_image_info[0], $a_image_info[1], $i_gd_ver);
                }
                break;
            case $this->_a_image_types['JPEG']:
            case $this->_a_image_types['JPG']:
                $bl_success = resize_jpeg($s_src, $s_target, $i_new_width, $i_new_height, $a_image_info, $i_gd_ver, $h_destination_image, $i_def_quality);
                break;
            case $this->_a_image_types['PNG']:
                $bl_success = resize_png($s_src, $s_target, $i_new_width, $i_new_height, $a_image_info, $i_gd_ver, $h_destination_image);
                break;
        }
        if ($bl_success && !$bl_disable_touch) {
            @touch($s_target);
        }
        stop_profile('PICTURE_RESIZE');
        return $bl_success;
    }
    /**
     * create and copy the resized image
     *
     * @param string $sDestinationImage file + path of destination
     * @param string $sSourceImage      file + path of source
     * @param int    $iNewWidth         new width of the image
     * @param int    $iNewHeight        new height of the image
     * @param array  $aImageInfo        additional info
     * @param string $sTarget           target file path
     * @param int    $iGdVer            used gd version @deprecated
     * @param bool   $blDisableTouch    wether Touch() should be called or not
     */
    protected function copy_altered_image($s_destination_image, $s_source_image, $i_new_width, $i_new_height, $a_image_info, $s_target, $i_gd_ver, $bl_disable_touch)
    {
        $bl_success = copy_altered_image($s_destination_image, $s_source_image, $i_new_width, $i_new_height, $a_image_info, $s_target, $i_gd_ver);
        if (!$bl_disable_touch && $bl_success) {
            @touch($s_target);
        }
        return $bl_success;
    }
    private function is_placeholder_image(string $filename): bool
    {
        return str_contains($filename, 'nopic.jpg') || str_contains($filename, 'nopic_ico.jpg');
    }
    private function remove_master_file(string $filepath): bool
    {
        $removed = false;
        try {
            $filepath = $this->make_path_relative_to_shop_source($filepath);
            if (Container_Facade::get(Master_Image_Handler_Bridge_Interface::class)->exists($filepath)) {
                Container_Facade::get(Master_Image_Handler_Bridge_Interface::class)->remove($filepath);
                $removed = true;
            }
        } catch (\Throwable $exception) {
            $ex = ox_new(Exception_To_Display::class);
            $ex->set_message($exception->get_message());
            Registry::get_utils_view()->add_error_to_display($ex, false);
        }
        return $removed;
    }
    private function make_path_relative_to_shop_source(string $path): string
    {
        return Path::make_relative($path, Container_Facade::get_parameter('oxid_esales.shop_source_directory'));
    }
}