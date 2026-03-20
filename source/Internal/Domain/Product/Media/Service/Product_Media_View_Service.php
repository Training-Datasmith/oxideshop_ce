<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Path;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media_Type;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Media_Url_Generator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao\Product_Media_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_View;
use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Dao\Shop_Configuration_Setting_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Config\Dao\Theme_Setting_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Filesystem\Path;
readonly class Product_Media_View_Service implements Product_Media_View_Service_Interface
{
    public function __construct(private Product_Media_Dao_Interface $product_media_dao, private Media_Url_Generator_Interface $media_url_generator, private Shop_Configuration_Setting_Dao_Interface $shop_configuration_setting_dao, private Theme_Setting_Dao_Interface $theme_setting_dao, private Shop_Adapter_Interface $shop_adapter, private Context_Interface $context)
    {
    }
    public function get_by_role(Id $product_id, Product_Media_Role $role): Product_Media_View
    {
        $product_media = $this->get_media_with_fallback($product_id, $role);
        return $product_media ? $this->create_media_view_with_all_sizes($product_media) : $this->create_fallback_media_view();
    }
    public function get_by_position(Id $product_id, int $position): Product_Media_View
    {
        $product_media = $this->product_media_dao->get_active_by_position($product_id, $position);
        if (!$product_media) {
            return $this->create_fallback_media_view();
        }
        return $this->create_media_view_with_all_sizes($product_media);
    }
    /** @return array<string, ProductMediaView> */
    public function get_all_by_role(Id $product_id, Product_Media_Role $role): array
    {
        $product_media_collection = $this->product_media_dao->get_all_active_by_role($product_id, $role);
        $media_views = [];
        foreach ($product_media_collection as $product_media) {
            $media_views[(string) $product_media->get_media()->get_id()] = $this->create_media_view_with_all_sizes($product_media);
        }
        return $media_views;
    }
    private function get_media_with_fallback(Id $product_id, Product_Media_Role $role): ?Product_Media
    {
        $product_media = $this->product_media_dao->get_active_by_role($product_id, $role);
        return $product_media ?: $this->product_media_dao->get_first_active($product_id);
    }
    private function create_media_view_with_all_sizes(Product_Media $product_media): Product_Media_View
    {
        return $this->create_media_view($product_media->get_media(), false);
    }
    private function create_fallback_media_view(): Product_Media_View
    {
        return $this->create_media_view($this->create_fallback_media(), true);
    }
    private function create_media_view(Media $media, bool $is_fallback): Product_Media_View
    {
        $detail_size = $this->get_configured_size('sDetailImageSize');
        $icon_size = $this->get_configured_size('sIconsize');
        $zoom_size = $this->get_configured_size('sZoomImageSize');
        $thumbnail_size = $this->get_configured_size('sThumbnailsize');
        return new Product_Media_View(detailUrl: $this->media_url_generator->generate_sized_image_url($media, $detail_size), iconUrl: $this->media_url_generator->generate_sized_image_url($media, $icon_size), zoomUrl: $this->media_url_generator->generate_sized_image_url($media, $zoom_size), thumbnailUrl: $this->media_url_generator->generate_sized_image_url($media, $thumbnail_size), isFallback: $is_fallback);
    }
    private function create_fallback_media(): Media
    {
        $fallback_filename = $this->get_fallback_filename();
        $mime_type = str_ends_with($fallback_filename, '.webp') ? 'image/webp' : 'image/jpeg';
        return new Media(Id::generate(), new Media_Path(Path::join('out', 'pictures', 'media', $fallback_filename)), new Media_Type($mime_type));
    }
    private function get_fallback_filename(): string
    {
        $convert_to_web_p = $this->shop_configuration_setting_dao->get('blConvertImagesToWebP', $this->context->get_current_shop_id());
        return $convert_to_web_p->get_value() ? 'nopic.webp' : 'nopic.jpg';
    }
    private function get_configured_size(string $size_config_key): string
    {
        try {
            $setting = $this->theme_setting_dao->get($size_config_key, $this->context->get_current_shop_id(), $this->shop_adapter->get_active_theme_id());
        } catch (Entry_Does_Not_Exist_Dao_Exception) {
            $setting = $this->shop_configuration_setting_dao->get($size_config_key, $this->context->get_current_shop_id());
        }
        return (string) $setting->get_value();
    }
}