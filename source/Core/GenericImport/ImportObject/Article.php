<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

/**
 * Import object for Articles.
 */
class Article extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxarticles';
    /** @var string Shop object name. */
    protected $shop_object_name = 'oxArticle';
    /**
     * Imports article. Returns import status.
     *
     * @param array $data DB row array.
     *
     * @return string $oxid Id on success, bool FALSE on failure.
     */
    public function import($data)
    {
        if (isset($data['OXID'])) {
            $this->check_id_field($data['OXID']);
        }
        return parent::import($data);
    }
    /**
     * Issued before saving an object.
     * Can modify $data array before saving.
     * Set default value of OXSTOCKFLAG to 1 according to eShop admin functionality.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $shopObject        shop object
     * @param array                                  $data              data to prepare
     * @param bool                                   $allowCustomShopId if allow custom shop id
     *
     * @return array
     */
    protected function pre_assign_object($shop_object, $data, $allow_custom_shop_id)
    {
        if (!isset($data['OXSTOCKFLAG'])) {
            if (!$data['OXID'] || !$shop_object->exists($data['OXID'])) {
                $data['OXSTOCKFLAG'] = 1;
            }
        }
        return parent::pre_assign_object($shop_object, $data, $allow_custom_shop_id);
    }
    /**
     * Post saving hook. can finish transactions if needed or ajust related data.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $shopObject Shop object.
     * @param array                                       $data       Data to save.
     *
     * @return mixed data to return
     */
    protected function post_save_object($shop_object, $data)
    {
        $article_id = $shop_object->get_id();
        $shop_object->on_change(null, $article_id, $article_id);
        return $article_id;
    }
    /**
     * Creates shop object.
     *
     * @return \OxidEsales\Eshop\Core\Model\BaseModel
     */
    protected function create_shop_object()
    {
        /** @var \OxidEsales\Eshop\Application\Model\Article $shopObject */
        $shop_object = parent::create_shop_object();
        $shop_object->set_no_variant_loading(true);
        return $shop_object;
    }
}