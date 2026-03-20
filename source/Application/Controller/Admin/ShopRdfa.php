<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Admin shop system RDFa manager.
 * Collects shop system settings, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> RDFa.
 */
class Shop_Rdfa extends \Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Configuration
{
    /**
     * Template name
     *
     * @var array
     */
    protected $_s_this_template = 'shop_rdfa';
    /**
     * Predefined customer types
     *
     * @var array
     */
    protected $_a_customers = ['Enduser' => 0, 'Reseller' => 0, 'Business' => 0, 'PublicInstitution' => 0];
    /**
     * Gets list of content pages which could be used for embedding
     * business entity, price specification, and delivery specification data
     *
     * @return \OxidEsales\Eshop\Application\Model\ContentList
     */
    public function get_content_list()
    {
        $o_content_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Content_List::class);
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_table = $table_view_name_generator->get_view_name('oxcontents', $this->_i_edit_lang);
        $o_content_list->select_string("SELECT * \n             FROM {$s_table} \n             WHERE OXACTIVE = 1 AND OXTYPE = 0\n                AND OXLOADID IN ('oxagb', 'oxdeliveryinfo', 'oximpressum', 'oxrightofwithdrawal')\n                AND OXSHOPID = :OXSHOPID\n             ORDER BY OXLOADID ASC", ['OXSHOPID' => Registry::get_request()->get_request_escaped_parameter('oxid')]);
        return $o_content_list;
    }
    /**
     * Handles and returns customer array
     *
     * @return array
     */
    public function get_customers()
    {
        $a_customers_conf = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_conf_var('aRDFaCustomers');
        if (isset($a_customers_conf)) {
            foreach ($this->_a_customers as $s_customer => $i_value) {
                $a_customers[$s_customer] = in_array($s_customer, $a_customers_conf) ? 1 : 0;
            }
        } else {
            $a_customers = [];
        }
        return $a_customers;
    }
}