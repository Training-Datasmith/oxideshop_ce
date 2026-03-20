<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
// checks if GD library version getter does not exist
if (!function_exists('getGdVersion')) {
    /**
     * Returns GD library version
     *
     * @return int
     */
    function get_gd_version()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iUseGDVersion');
    }
}
// checks if image creation function does not exist
if (!function_exists('copyAlteredImage')) {
    /**
     * Creates and copies the resized image
     *
     * @param string $sDestinationImage file + path of destination
     * @param string $sSourceImage      file + path of source
     * @param int    $iNewWidth         new width of the image
     * @param int    $iNewHeight        new height of the image
     * @param array  $aImageInfo        additional info
     * @param string $sTarget           target file path @deprecated
     * @param int    $iGdVer            used gd version @deprecated
     */
    function copy_altered_image($s_destination_image, $s_source_image, $i_new_width, $i_new_height, array $a_image_info, $s_target = null, $i_gd_ver = null): bool
    {
        return imagecopyresampled($s_destination_image, $s_source_image, 0, 0, 0, 0, $i_new_width, $i_new_height, $a_image_info[0], $a_image_info[1]);
    }
}
// checks if image size calculator does nor exist
if (!function_exists('calcImageSize')) {
    /**
     * Calculates proportional new image size
     *
     * @param int $iDesiredWidth  expected image width
     * @param int $iDesiredHeight expected image height
     * @param int $iPrefWidth     original image width
     * @param int $iPrefHeight    original image height
     */
    function calc_image_size($i_desired_width, $i_desired_height, $i_pref_width, $i_pref_height): array
    {
        // #1837/1177M - do not resize smaller pictures
        if ($i_desired_width < $i_pref_width || $i_desired_height < $i_pref_height) {
            if ($i_pref_width >= $i_pref_height * (float) ($i_desired_width / $i_desired_height)) {
                $i_new_height = round($i_pref_height * (float) ($i_desired_width / $i_pref_width), 0);
                $i_new_width = $i_desired_width;
            } else {
                $i_new_height = $i_desired_height;
                $i_new_width = round($i_pref_width * (float) ($i_desired_height / $i_pref_height), 0);
            }
        } else {
            $i_new_width = $i_pref_width;
            $i_new_height = $i_pref_height;
        }
        return [$i_new_width, $i_new_height];
    }
}
if (!function_exists('checkSizeAndCopy')) {
    /**
     * Checks if preferred image dimensions size matches defined in config;
     * in case it matches - copies original image to new location, returns
     * copying state - TRUE/FALSe else - returns array with new dimensions
     * array( $iNewWidth, $iNewHeight );
     *
     * @param string $sSrc        image source file name
     * @param string $sTarget     target location
     * @param int    $iWidth      preferred width
     * @param int    $iHeight     preferred height
     * @param int    $iOrigWidth  original width
     * @param int    $iOrigHeight preferred height
     */
    function check_size_and_copy($s_src, $s_target, $i_width, $i_height, $i_orig_width, $i_orig_height): bool|array
    {
        [$i_new_width, $i_new_height] = calc_image_size($i_width, $i_height, $i_orig_width, $i_orig_height);
        if ($i_new_width == $i_orig_width && $i_new_height == $i_orig_height) {
            return copy($s_src, $s_target);
        }
        return [$i_new_width, $i_new_height];
    }
}
// checks if GIF resizer does not exist
if (!function_exists('resizeGif')) {
    /**
     * Creates resized GIF image. Returns path of new file if creation
     * succeed. On error returns FALSE
     *
     * @param string $sSrc            GIF source
     * @param string $sTarget         new image location
     * @param int    $iWidth          new width
     * @param int    $iHeight         new height
     * @param int    $iOriginalWidth  original width
     * @param int    $iOriginalHeight original height
     * @param int    $iGDVer          GD library version @deprecated
     *
     * @return string|false
     */
    function resize_gif($s_src, $s_target, $i_width, $i_height, $i_original_width, $i_original_height, $i_gd_ver)
    {
        $a_result = check_size_and_copy($s_src, $s_target, $i_width, $i_height, $i_original_width, $i_original_height);
        if (is_array($a_result)) {
            [$i_new_width, $i_new_height] = $a_result;
            $h_destination_image = imagecreatetruecolor($i_new_width, $i_new_height);
            $h_source_image = imagecreatefromgif($s_src);
            $i_fill_color = imagecolorresolve($h_destination_image, 255, 255, 255);
            imagefill($h_destination_image, 0, 0, $i_fill_color);
            imagecolortransparent($h_destination_image, $i_fill_color);
            imagecopyresampled($h_destination_image, $h_source_image, 0, 0, 0, 0, $i_new_width, $i_new_height, $i_original_width, $i_original_height);
            imagegif($h_destination_image, $s_target);
        }
        return $s_target;
    }
}
// checks if PNG resizer does not exist
if (!function_exists('resizePng')) {
    /**
     * Creates resized PNG image. Returns path of new file if creation
     * succeded. On error returns FALSE
     *
     * @param string   $sSrc              JPG source
     * @param string   $sTarget           new image location
     * @param int      $iWidth            new width
     * @param int      $iHeight           new height
     * @param int      $aImageInfo        original width
     * @param int      $iGdVer            GD library version @deprecated
     * @param resource $hDestinationImage destination image handle
     *
     * @return string|false
     */
    function resize_png($s_src, $s_target, $i_width, $i_height, array $a_image_info, $i_gd_ver, $h_destination_image)
    {
        $a_result = check_size_and_copy($s_src, $s_target, $i_width, $i_height, $a_image_info[0], $a_image_info[1]);
        if (is_array($a_result)) {
            [$i_new_width, $i_new_height] = $a_result;
            if ($h_destination_image === null) {
                $h_destination_image = imagecreatetruecolor($i_new_width, $i_new_height);
            }
            $h_source_image = imagecreatefrompng($s_src);
            if (!imageistruecolor($h_source_image)) {
                $h_destination_image = imagecreate($i_new_width, $i_new_height);
                // fix for transparent images sets image to transparent
                $img_white = imagecolorallocate($h_destination_image, 255, 255, 255);
                imagefill($h_destination_image, 0, 0, $img_white);
                imagecolortransparent($h_destination_image, $img_white);
                //end of fix
            } else {
                imagealphablending($h_destination_image, false);
                imagesavealpha($h_destination_image, true);
            }
            if (copy_altered_image($h_destination_image, $h_source_image, $i_new_width, $i_new_height, $a_image_info, $s_target, $i_gd_ver)) {
                imagepng($h_destination_image, $s_target);
            }
        }
        return $s_target;
    }
}
// checks if JPG resizer does not exist
if (!function_exists('resizeJpeg')) {
    /**
     * Creates resized JPG image. Returns path of new file if creation
     * succeed. On error returns FALSE
     *
     * @param string   $sSrc              JPG source
     * @param string   $sTarget           new image location
     * @param int      $iWidth            new width
     * @param int      $iHeight           new height
     * @param int      $aImageInfo        original width
     * @param int      $iGdVer            GD library version @deprecated
     * @param resource $hDestinationImage destination image handle
     * @param int      $iDefQuality       new image quality
     *
     * @return string|false
     */
    function resize_jpeg($s_src, $s_target, $i_width, $i_height, array $a_image_info, $i_gd_ver, $h_destination_image, $i_def_quality)
    {
        $a_result = check_size_and_copy($s_src, $s_target, $i_width, $i_height, $a_image_info[0], $a_image_info[1]);
        if (is_array($a_result)) {
            [$i_new_width, $i_new_height] = $a_result;
            if ($h_destination_image === null) {
                $h_destination_image = imagecreatetruecolor($i_new_width, $i_new_height);
            }
            $h_source_image = imagecreatefromstring(file_get_contents($s_src));
            if (copy_altered_image($h_destination_image, $h_source_image, $i_new_width, $i_new_height, $a_image_info, $s_target, $i_gd_ver)) {
                imagejpeg($h_destination_image, $s_target, $i_def_quality);
            }
        }
        return $s_target;
    }
}
// checks if WebP resizer doesn't exist
if (!function_exists('resizeWebp')) {
    function resize_webp(string $source, string $target, int $width, int $height, int $quality): string
    {
        [$orig_width, $orig_height] = @getimagesize($source);
        $result = check_size_and_copy($source, $target, $width, $height, $orig_width, $orig_height);
        if (is_array($result)) {
            [$new_width, $new_height] = $result;
            $destination_image = imagecreatetruecolor($new_width, $new_height);
            $source_image = imagecreatefromwebp($source);
            imagealphablending($destination_image, false);
            imagesavealpha($destination_image, true);
            if (copy_altered_image($destination_image, $source_image, $new_width, $new_height, [$orig_width, $orig_height])) {
                imagewebp($destination_image, $target, $quality);
            }
        }
        return $target;
    }
}