<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace {
    use Oxid_Esales\Eshop\Core\Dynamic_Image_Generator;
    /** Checks if instance name getter does not exist */
    if (!function_exists('getGeneratorInstanceName')) {
        /**
         * Returns image generator instance name
         */
        function get_generator_instance_name(): string
        {
            return Dynamic_Image_Generator::class;
        }
    }
    /** Checks if GD library version getter does not exist */
    if (!function_exists('getGdVersion')) {
        /**
         * Returns GD library version
         *
         * @return int
         */
        function get_gd_version()
        {
            static $version = null;
            if ($version === null) {
                $version = false;
                if (function_exists('gd_info')) {
                    // extracting GD version from php
                    $info = gd_info();
                    if (isset($info['GD Version'])) {
                        $version = version_compare(preg_replace("/[^0-9\\.]/", '', $info['GD Version']), 1, '>') ? 2 : 1;
                    }
                }
            }
            return $version;
        }
    }
    /** Checks if image utils file loader does not exist */
    if (!function_exists('includeImageUtils')) {
        /**
         * Includes image utils
         */
        function include_image_utils(): void
        {
            include_once __DIR__ . '/utils/oxpicgenerator.php';
        }
    }
}
namespace Oxid_Esales\Eshop_Community\Core {
    use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
    use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
    use Oxid_Esales\Eshop\Core\Registry;
    use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
    use Symfony\Component\Filesystem\Path;
    /**
     * Image generator class
     */
    class Dynamic_Image_Generator
    {
        /**
         * Generator instance
         *
         * @var DynamicImageGenerator
         */
        protected static $_o_instance;
        /**
         * Custom headers
         *
         * @var array
         */
        protected $_a_headers = [];
        /**
         * Allowed image types
         *
         * @var array
         */
        protected $_a_allowed_img_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        /**
         * Image info like size and quality is defined in directory
         * name e.g. 160_160_75, this means width_height_quality
         *
         * @var string
         */
        protected $_s_image_info_sep = '_';
        /**
         * Lockable file handle
         *
         * @var resource
         */
        protected $_h_lock_handle;
        /**
         * Requested image uri
         *
         * @var string
         */
        protected $_s_image_uri;
        /**
         * Map of config parameter to requested image path
         */
        protected array $resolution_config_parameters = ['sIconsize', 'sThumbnailsize', 'sZoomImageSize', 'sDetailImageSize', 'sManufacturerIconsize', 'sManufacturerPicturesize', 'sManufacturerThumbnailsize', 'sManufacturerPromotionsize', 'sCatThumbnailsize', 'sCatIconsize', 'sCatPromotionsize'];
        /**
         * Creates and returns picture generator instance
         *
         * @return DynamicImageGenerator
         */
        public static function get_instance()
        {
            if (self::$_o_instance === null) {
                $instance_name = get_generator_instance_name();
                self::$_o_instance = new $instance_name();
            }
            return self::$_o_instance;
        }
        /**
         * Only used for convenience in UNIT tests by doing so we avoid
         * writing extended classes for testing protected or private methods
         *
         * @param string $method Methods name
         * @param array  $arguments Argument array
         * @return false|mixed
         * @throws SystemComponentException
         */
        public function __call(string $method, array $arguments)
        {
            if (method_exists($this, $method)) {
                return call_user_func_array([&$this, $method], $arguments);
            }
            throw new System_Component_Exception("Function '{$method}' does not exist or is not accessible! (" . static::class . ')' . PHP_EOL);
        }
        /**
         * Returns shops base path
         */
        protected function get_shop_base_path(): string
        {
            return Container_Facade::get_parameter('oxid_esales.shop_source_directory') . DIRECTORY_SEPARATOR;
        }
        /**
         * Returns requested image uri
         *
         * @return string
         */
        protected function get_image_uri()
        {
            if ($this->_s_image_uri === null) {
                $this->_s_image_uri = '';
                $req_path = 'out/pictures/generated';
                $req_img = isset($_SERVER['REQUEST_URI']) ? urldecode((string) $_SERVER['REQUEST_URI']) : '';
                $req_img = str_replace('//', '/', $req_img);
                if (($pos = strpos($req_img, $req_path)) !== false) {
                    $this->_s_image_uri = substr($req_img, $pos);
                }
                $this->_s_image_uri = trim($this->_s_image_uri, '/');
            }
            return $this->_s_image_uri;
        }
        /**
         * Returns requested image name
         */
        protected function get_image_name(): string
        {
            return basename($this->get_image_uri());
        }
        /**
         * Returns path to possible master image
         *
         * @return string
         */
        protected function get_image_master_path(): string|array|false|null
        {
            $uri = $this->get_image_uri();
            $path = false;
            if ($uri && $path = dirname($uri, 2)) {
                return preg_replace("/\\/([^\\/]*)\\/([^\\/]*)\\/([^\\/]*)\$/", '/master/\2/\3/', $path);
            }
            return $path;
        }
        private function parse_media_path_from_url(): string
        {
            $uri = $this->get_image_uri();
            $original_image_directory = preg_replace('~(^|/)generated/~', '$1', dirname($uri, 2));
            return Path::join($this->get_shop_base_path(), $original_image_directory, $this->get_image_name());
        }
        /**
         * Returns image info array
         */
        protected function get_image_info(): array
        {
            if ($uri = $this->get_image_uri()) {
                return explode($this->_s_image_info_sep, basename(dirname($uri)));
            }
            return [0, 0, 0];
        }
        /**
         * Returns full requested image path on file system
         */
        protected function get_image_target(): string
        {
            return $this->get_shop_base_path() . $this->get_image_uri();
        }
        /**
         * Nopic image path
         */
        protected function get_nopic_image_target(): string
        {
            $path = $this->get_shop_base_path() . $this->get_image_uri();
            return str_replace($this->get_image_name(), $this->get_nopic_filename(), $path);
        }
        private function get_nopic_filename(): string
        {
            if (Registry::get_config()->get_config_param('blConvertImagesToWebP')) {
                return 'nopic.webp';
            }
            return 'nopic.jpg';
        }
        /**
         * Returns image type used for image generation and header setting
         *
         * @return string
         */
        protected function get_image_type()
        {
            $file_extension = strtolower(pathinfo($this->get_image_name(), PATHINFO_EXTENSION));
            if (!$this->validate_image_file_extension($file_extension)) {
                return false;
            }
            if ('jpg' == $file_extension) {
                return 'jpeg';
            }
            return $file_extension;
        }
        /**
         * Generates PNG type image and returns its location on file system
         *
         * @param string $source image source
         * @param string $target image target
         * @param int    $width  image width
         * @param int    $height image height
         *
         * @return string
         */
        protected function generate_png($source, $target, $width, $height)
        {
            return resize_png($source, $target, $width, $height, @getimagesize($source), get_gd_version(), null);
        }
        /**
         * Generates JPG type image and returns its location on file system
         *
         * @param string $source  image source
         * @param string $target  image target
         * @param int    $width   image width
         * @param int    $height  image height
         * @param int    $quality new image quality
         *
         * @return string
         */
        protected function generate_jpg($source, $target, $width, $height, $quality)
        {
            return resize_jpeg($source, $target, $width, $height, @getimagesize($source), get_gd_version(), null, $quality);
        }
        /**
         * Generates GIF type image and returns its location on file system
         *
         * @param string $source image source
         * @param string $target image target
         * @param int    $width  image width
         * @param int    $height image height
         *
         * @return string
         */
        protected function generate_gif($source, $target, $width, $height)
        {
            $image_info = @getimagesize($source);
            return resize_gif($source, $target, $width, $height, $image_info[0], $image_info[1], $this->validate_gd_version());
        }
        protected function generate_webp(string $source, string $target, int $width, int $height, int $quality): string
        {
            return resize_webp($source, $target, $width, $height, $quality);
        }
        /**
         * Checks if requested image path is valid. If path is valid
         * but is not created - creates directory structure
         *
         * @param string $path image path name to check
         *
         * @return bool
         */
        protected function is_target_path_valid($path)
        {
            $valid = true;
            $dir = dirname(trim($path));
            // first time folder access?
            if (!is_dir($dir) && $valid = $this->is_valid_path($dir)) {
                // creating missing folders
                return $this->create_folders($dir);
            }
            return $valid;
        }
        /**
         * Checks if valid and creates missing needed folders
         *
         * @param string $dir folder(s) to create
         *
         * @return bool
         */
        protected function create_folders($dir)
        {
            $config = Registry::get_config();
            $pic_folder_path = dirname((string) $config->get_master_picture_dir());
            $done = false;
            if ($pic_folder_path && is_dir($pic_folder_path)) {
                // if its in main path..
                if (Path::is_base_path($pic_folder_path, $dir)) {
                    // folder does not exist yet?
                    if (!$done = file_exists($dir)) {
                        clearstatcache();
                        // in case creation did not succeed, maybe another process allready created folder?
                        $mode = 0755;
                        $done = mkdir($dir, $mode, true) || file_exists($dir);
                    }
                }
            }
            return $done;
        }
        /**
         * Checks if main folder matches requested
         *
         * @param string $path image path name to check
         *
         * @return bool
         */
        protected function is_valid_path($path)
        {
            [$width, $height, $quality] = $this->get_image_info();
            if ($width && $height && $quality) {
                $check_size = "{$width}*{$height}";
                $config = Registry::get_config();
                $db = Database_Provider::get_db();
                $names = [];
                foreach ($this->resolution_config_parameters as $param_name) {
                    $names[] = $db->quote($param_name);
                }
                $names = implode(', ', $names);
                // any name matching path?
                if ($names) {
                    // selecting shop which image quality matches user given
                    $q = "select oxshopid\n                            from oxconfig\n                            where oxvarname = 'sDefaultImageQuality' and\n                            oxvarvalue = :quality";
                    $shop_ids_array = $db->get_all($q, ['quality' => $quality]);
                    // building query:
                    // shop id
                    $shop_ids = implode(', ', array_map(
                        // probably here we can resolve and check shop id to shorten check?
                        fn(array $shop_id) => $db->quote($shop_id['oxshopid']),
                        $shop_ids_array
                    ));
                    // any shop matching quality
                    if ($shop_ids) {
                        // selecting config variables to check
                        $q = "select oxvartype, oxvarvalue from oxconfig\n                           where oxvarname in ( {$names} ) and oxshopid in ( {$shop_ids} ) order by oxshopid";
                        $values = $db->get_all($q);
                        foreach ($values as $value) {
                            $conf_values = (array) $config->decode_value($value['oxvartype'], $value['oxvarvalue']);
                            foreach ($conf_values as $conf_value) {
                                if (strcmp($check_size, (string) $conf_value) == 0) {
                                    return true;
                                }
                            }
                        }
                    }
                }
                return $this->is_size_allowed($check_size);
            }
            return false;
        }
        private function is_size_allowed(string $check_size): bool
        {
            return in_array($check_size, Container_Facade::get_parameter('oxid_esales.theme.media.allowed_image_sizes'), true);
        }
        /**
         * Converts a given source image into a target image
         *
         * @param string $imageSource File path of the source image
         * @param string $imageTarget File path of the image to be generated
         *
         * @throws StandardException If the path of imageTarget and generated image are not the same
         *
         * @return bool|string Return false on failure or file path of the generated image on success
         */
        protected function generate_image($image_source, $image_target)
        {
            $generated_image_path = false;
            [$target_width, $target_height, $target_quality] = $this->get_image_info();
            $file_extension_source = strtolower(pathinfo($image_source, PATHINFO_EXTENSION));
            $file_extension_target = strtolower(pathinfo($image_target, PATHINFO_EXTENSION));
            // Do some validation and return false on failure
            if (!$this->validate_gd_version() || !$this->validate_file_exist($image_source) || !$this->is_target_path_valid($image_target) || !$this->validate_image_file_extension($file_extension_source) || !$this->validate_image_file_extension($file_extension_target) || $file_extension_source !== $file_extension_target) {
                return false;
            }
            if ($this->validate_file_exist($image_target)) {
                [$current_width, $current_height] = $this->get_image_dimensions($image_target);
                if ($current_width == $target_width && $current_height == $target_height) {
                    return $image_target;
                }
            }
            // including generator files
            include_image_utils();
            /**
             * There may be a different process trying to generate this image at the same moment.
             * Get a lock in order not to write at the same file at the same time.
             */
            if ($this->lock($image_target)) {
                // extracting image info - size/quality
                switch ($file_extension_source) {
                    case 'png':
                        $generated_image_path = $this->generate_png($image_source, $image_target, $target_width, $target_height);
                        break;
                    case 'jpeg':
                    case 'jpg':
                        $generated_image_path = $this->generate_jpg($image_source, $image_target, $target_width, $target_height, $target_quality);
                        break;
                    case 'gif':
                        $generated_image_path = $this->generate_gif($image_source, $image_target, $target_width, $target_height);
                        break;
                    case 'webp':
                        $generated_image_path = $this->generate_webp($image_source, $image_target, $target_width, $target_height, $target_quality);
                        break;
                }
                // target must always be unlocked, no matter what the result of the former image generation was.
                $this->unlock($image_target);
            }
            if ($generated_image_path && $generated_image_path != $image_target) {
                throw new Standard_Exception('imageTarget path and generatedImage path differ');
            }
            return $generated_image_path;
        }
        /**
         * Returns lock file name
         *
         * @param string $name original file name
         */
        protected function get_lock_name($name): string
        {
            return "{$name}.lck";
        }
        /**
         * Locks file and returns locking state
         *
         * @param string $source source file which should be locked
         *
         * @return bool
         */
        protected function lock($source)
        {
            $locked = false;
            $lock_name = $this->get_lock_name($source);
            // creating lock file
            $this->_h_lock_handle = @fopen($lock_name, 'w');
            if (is_resource($this->_h_lock_handle)) {
                if (!$locked = flock($this->_h_lock_handle, LOCK_EX)) {
                    // on failure - closing
                    fclose($this->_h_lock_handle);
                    $this->_h_lock_handle = null;
                }
            }
            // in case system does not support file lockings
            if (!$locked) {
                // start a blank file to inform other processes we are dealing with it.
                if (!(file_exists($lock_name) && abs(time() - filectime($lock_name) < 40))) {
                    if ($this->_h_lock_handle = @fopen($lock_name, 'w')) {
                        $locked = true;
                    }
                }
            }
            return $locked;
        }
        /**
         * Deletes lock file
         *
         * @param string $source source file which should be locked
         */
        protected function unlock($source)
        {
            if (is_resource($this->_h_lock_handle)) {
                flock($this->_h_lock_handle, LOCK_UN);
                fclose($this->_h_lock_handle);
                $this->_h_lock_handle = null;
                unlink($this->get_lock_name($source));
            }
        }
        /**
         * Returns the file path of an image as requested by self::_getImageUri().
         * If the requested image does not exist, if will be rendered from the master image.
         * If the master image does not exist, a nopic image in the same directory as the requested image is shown.
         * If the nopic image does not exist, it will be generated in with the same dimensions and quality as the requested
         * image.
         * If the nopic image does not exist, the method returns false.
         *
         * @param bool $absPath absolute requested image path (not url, but real path on file system)
         *
         * @return string|false
         */
        public function get_image_path($abs_path = false)
        {
            if ($abs_path) {
                $this->_s_image_uri = str_replace($this->get_shop_base_path(), '', $abs_path);
            }
            $image_path = false;
            $master_path = $this->get_image_master_path();
            // building base path + extracting image name + extracting master image path
            $master_image_path = $this->get_shop_base_path() . $master_path . $this->get_image_name();
            if (!file_exists($master_image_path)) {
                $master_image_path = $this->parse_media_path_from_url();
            }
            if (Registry::get_config()->get_config_param('blConvertImagesToWebP') && !file_exists($master_image_path)) {
                $this->convert_image_if_original_exists($master_image_path);
            }
            if (file_exists($master_image_path)) {
                $gen_image_path = $this->get_image_target();
            } else {
                // nopic master path
                $master_image_path = $this->get_shop_base_path() . dirname($master_path, 2) . '/' . $this->get_nopic_filename();
                $gen_image_path = $this->get_nopic_image_target();
                // 404 header for nopic
                $this->set_header('HTTP/1.1 404 Not Found');
            }
            // checking if master image is accessible
            if (file_exists($gen_image_path)) {
                $image_path = $gen_image_path;
            } elseif (file_exists($master_image_path)) {
                // generating image
                $image_path = $this->generate_image($master_image_path, $gen_image_path);
            }
            if ($this->validate_file_exist($image_path)) {
                // image Content-Type
                $content_type = mime_content_type($image_path);
                $this->set_header("Content-Type: {$content_type};");
            } else {
                // unable to output any file
                $this->set_header('HTTP/1.1 404 Not Found');
            }
            return $image_path;
        }
        private function convert_image_if_original_exists(string $desired_filename): void
        {
            $path_parts = pathinfo($desired_filename);
            $original_filename = $path_parts['dirname'] . '/' . $path_parts['filename'];
            $source_image = false;
            switch (pathinfo($original_filename, PATHINFO_EXTENSION)) {
                case 'png':
                    $source_image = imagecreatefrompng($original_filename);
                    break;
                case 'jpg':
                case 'jpeg':
                    $source_image = imagecreatefromjpeg($original_filename);
                    break;
                case 'gif':
                    $source_image = imagecreatefromgif($original_filename);
                    break;
            }
            if ($source_image) {
                $quality = Registry::get_config()->get_config_param('sDefaultImageQuality');
                imagewebp($source_image, $desired_filename, $quality);
            }
        }
        /**
         * Creates and outputs requested image. If source file was not found -
         * tries to render related "nopic.jpg". If "nopic.jpg" is not available -
         * sends 404 header to browser
         */
        public function output_image(): void
        {
            // starting output buffering
            ob_start();
            $img_path = $this->get_image_path();
            // cleaning extra output
            ob_clean();
            // outputting headers
            $headers = $this->get_headers();
            foreach ($headers as $header) {
                header($header);
            }
            // sending headers
            ob_end_flush();
            // file is generated?
            if ($img_path) {
                // outputting file
                @readfile($img_path);
            }
        }
        /**
         * @param string $fileExtension Extension to be validated. Validation is case insensitive.
         */
        protected function validate_image_file_extension($file_extension): bool
        {
            return in_array(strtolower($file_extension), $this->_a_allowed_img_types);
        }
        /**
         * Custom header setter
         *
         * @param string $header header
         */
        protected function set_header($header)
        {
            $this->_a_headers[] = $header;
        }
        /**
         * Return headers array
         *
         * @return array
         */
        protected function get_headers()
        {
            return $this->_a_headers;
        }
        /**
         * Return true, if the version of the gd library is correct
         */
        protected function validate_gd_version(): bool
        {
            return get_gd_version() !== false;
        }
        /**
         * Return true, if a given file path exists.
         *
         * @param string $filePath
         */
        protected function validate_file_exist($file_path): bool
        {
            return file_exists($file_path);
        }
        /**
         * Return an array with the dimensions (width x height) of an image file.
         * returns array (0,0), if the dimensions could not be retrieved.
         *
         * @param string $imageFilePath
         *
         * @return array
         */
        protected function get_image_dimensions($image_file_path)
        {
            try {
                [$width, $height] = getimagesize($image_file_path);
                $image_dimensions = [$width, $height];
            } catch (\Exception) {
                $image_dimensions = [0, 0];
            }
            return $image_dimensions;
        }
    }
}