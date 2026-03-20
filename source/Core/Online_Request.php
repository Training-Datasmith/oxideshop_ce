<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Online check base request class.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_Request
{
    /**
     * OXID eShop servers cluster id.
     *
     * @var string
     */
    public $cluster_id;
    /**
     * OXID eShop edition.
     *
     * @var string
     */
    public $edition;
    /**
     * Shops version number.
     *
     * @var string
     */
    public $version;
    /**
     * @var string
     */
    public $shop_url;
    /**
     * Web service protocol version.
     *
     * @var string
     */
    public $p_version;
    /**
     * Product ID. Intended for possible partner modules in future.
     *
     * @var string
     */
    public $product_id = 'eShop';
    public function __construct()
    {
        $this->cluster_id = $this->get_cluster_id();
        $this->edition = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_edition()->value;
        $this->version = Shop_Version::get_version();
        $this->shop_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url();
    }
    /**
     * Returns cluster id.
     * Takes cluster id from configuration if set, otherwise generates it.
     *
     * @return string
     */
    private function get_cluster_id()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_base_shop = $o_config->get_base_shop_id();
        $s_cluster_id = $o_config->get_shop_conf_var('sClusterId', $s_base_shop);
        if (!$s_cluster_id) {
            $o_uuid_generator = ox_new(\Oxid_Esales\Eshop\Core\Universally_Unique_Id_Generator::class);
            $s_cluster_id = $o_uuid_generator->generate();
            $o_config->save_shop_conf_var('str', 'sClusterId', $s_cluster_id, $s_base_shop);
        }
        return $s_cluster_id;
    }
}