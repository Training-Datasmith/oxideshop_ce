<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Dao\Shop_Configuration_Setting_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Filesystem\Path;
class Media_Url_Generator implements Media_Url_Generator_Interface
{
    private readonly string $generated_base_url;
    private readonly string $pictures_root;
    private readonly int $image_quality;
    public function __construct(Context_Interface $context, Shop_Configuration_Setting_Dao_Interface $shop_configuration_setting_dao, string $alternative_image_url = '')
    {
        $this->generated_base_url = $alternative_image_url ? Path::join($alternative_image_url, 'generated') : Path::join($context->get_shop_base_url(), 'out', 'pictures', 'generated');
        $this->pictures_root = Path::join(Path::make_relative($context->get_out_path(), $context->get_source_path()), 'pictures');
        $this->image_quality = (int) $shop_configuration_setting_dao->get('sDefaultImageQuality', $context->get_current_shop_id())->get_value();
    }
    public function generate_sized_image_url(Media $media, string $size): string
    {
        $relative_media_path = Path::make_relative((string) $media->get_media_path(), $this->pictures_root);
        return Path::join($this->generated_base_url, dirname($relative_media_path), $this->build_size_path($size), rawurlencode(basename($relative_media_path)));
    }
    private function build_size_path(string $size): string
    {
        [$width, $height] = explode('*', $size);
        return "{$width}_{$height}_{$this->image_quality}";
    }
}