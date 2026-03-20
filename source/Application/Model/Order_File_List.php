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
class Order_File_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxorderfile';
    /**
     * Returns orders
     *
     * @param string $sUserId - user id
     */
    public function load_user_files($s_user_id): void
    {
        $o_order_file = $this->get_base_object();
        $s_fields = $o_order_file->get_select_fields();
        $o_order_file->add_field_name('oxorderfiles__oxarticletitle');
        $o_order_file->add_field_name('oxorderfiles__oxarticleartnum');
        $o_order_file->add_field_name('oxorderfiles__oxordernr');
        $o_order_file->add_field_name('oxorderfiles__oxorderdate');
        $s_sql = 'SELECT ' . $s_fields . " ,\n                      `oxorderarticles`.`oxtitle` AS `oxorderfiles__oxarticletitle`,\n                      `oxorderarticles`.`oxartnum` AS `oxorderfiles__oxarticleartnum`,\n                      `oxfiles`.`oxpurchasedonly` AS `oxorderfiles__oxpurchasedonly`,\n                      `oxorder`.`oxordernr` AS `oxorderfiles__oxordernr`,\n                      `oxorder`.`oxorderdate` AS `oxorderfiles__oxorderdate`,\n                      IF( `oxorder`.`oxpaid` != '0000-00-00 00:00:00', 1, 0 ) AS `oxorderfiles__oxispaid`\n                    FROM `oxorderfiles`\n                        LEFT JOIN `oxorderarticles` ON `oxorderarticles`.`oxid` = `oxorderfiles`.`oxorderarticleid`\n                        LEFT JOIN `oxfiles` ON `oxfiles`.`oxid` = `oxorderfiles`.`oxfileid`\n                        LEFT JOIN `oxorder` ON `oxorder`.`oxid` = `oxorderfiles`.`oxorderid`\n                    WHERE `oxorder`.`oxuserid` = :oxuserid\n                        AND `oxorderfiles`.`oxshopid` = :oxshopid\n                        AND `oxorder`.`oxstorno` = 0\n                        AND `oxorderarticles`.`oxstorno` = 0\n                    ORDER BY `oxorder`.`oxordernr`";
        $this->select_string($s_sql, ['oxuserid' => $s_user_id, 'oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()]);
    }
    /**
     * Returns oxorderfiles list
     *
     * @param string $sOrderId - order id
     */
    public function load_order_files($s_order_id): void
    {
        $o_order_file = $this->get_base_object();
        $s_fields = $o_order_file->get_select_fields();
        $o_order_file->add_field_name('oxorderfiles__oxarticletitle');
        $o_order_file->add_field_name('oxorderfiles__oxarticleartnum');
        $s_sql = 'SELECT ' . $s_fields . ' ,
                      `oxorderarticles`.`oxtitle` AS `oxorderfiles__oxarticletitle`,
                      `oxorderarticles`.`oxartnum` AS `oxorderfiles__oxarticleartnum`,
                      `oxfiles`.`oxpurchasedonly` AS `oxorderfiles__oxpurchasedonly`
                    FROM `oxorderfiles`
                        LEFT JOIN `oxorderarticles` ON `oxorderarticles`.`oxid` = `oxorderfiles`.`oxorderarticleid`
                        LEFT JOIN `oxfiles` ON `oxfiles`.`oxid` = `oxorderfiles`.`oxfileid`
                    WHERE `oxorderfiles`.`oxorderid` = :oxorderid AND `oxorderfiles`.`oxshopid` = :oxshopid
                        AND `oxorderarticles`.`oxstorno` = 0';
        $this->select_string($s_sql, ['oxorderid' => $s_order_id, 'oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()]);
    }
}