<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Media_Url_Generator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao\Product_Media_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
class Article_Pictures extends Admin_Details_Controller
{
    private readonly string $detail_image_size;
    private readonly string $zoom_image_size;
    private readonly Media_Url_Generator_Interface $media_url_generator;
    private readonly Product_Media_Dao_Interface $product_media_dao;
    public function __construct()
    {
        $this->media_url_generator = Container_Facade::get(Media_Url_Generator_Interface::class);
        $this->product_media_dao = Container_Facade::get(Product_Media_Dao_Interface::class);
        $this->detail_image_size = Container_Facade::get_parameter('oxid_esales.theme.admin.media.image_grid_size');
        $this->zoom_image_size = Container_Facade::get_parameter('oxid_esales.theme.admin.media.image_zoom_size');
        parent::__construct();
    }
    public function render()
    {
        parent::render();
        $product_id = Id::from_string($this->get_edit_object_id());
        $icon = $this->product_media_dao->get_by_role($product_id, Product_Media_Role::from(Product_Media_Role::ICON));
        $thumbnail = $this->product_media_dao->get_by_role($product_id, Product_Media_Role::from(Product_Media_Role::THUMBNAIL));
        $this->_a_view_data['productImages'] = ['icon' => $icon ? $this->build_image_data($icon) : null, 'thumbnail' => $thumbnail ? $this->build_image_data($thumbnail) : null, 'detailImages' => $this->product_media_dao->get_all_by_role($product_id, Product_Media_Role::from(Product_Media_Role::DETAIL))->map(fn(Product_Media $media): array => $this->build_image_data($media))->to_array()];
        return 'article_pictures';
    }
    private function build_image_data(Product_Media $media): array
    {
        return ['id' => (string) $media->get_id(), 'productId' => (string) $media->get_product_id(), 'url' => $this->media_url_generator->generate_sized_image_url($media->get_media(), $this->detail_image_size), 'zoomUrl' => $this->media_url_generator->generate_sized_image_url($media->get_media(), $this->zoom_image_size), 'position' => $media->get_position(), 'active' => $media->is_active()];
    }
}