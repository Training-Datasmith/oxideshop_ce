<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class controls article assignment to attributes
 */
class Shop_Default_Category_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxcategories', 1, 1, 0],
        ['oxdesc', 'oxcategories', 1, 1, 0],
        ['oxid', 'oxcategories', 0, 0, 0],
        ['oxid', 'oxcategories', 0, 0, 1],
    ]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_cat = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $o_cat->set_language(Registry::get_request()->get_request_escaped_parameter('editlanguage'));
        $s_categories_table = $o_cat->get_view_name();
        return " from {$s_categories_table} where " . $o_cat->get_sql_active_snippet();
    }
    /**
     * Removing article from corssselling list
     */
    public function unassign_cat(): void
    {
        $s_shop_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $o_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        if ($o_shop->load($s_shop_id)) {
            $o_shop->oxshops__oxdefcat = new \Oxid_Esales\Eshop\Core\Field('');
            $o_shop->save();
        }
    }
    /**
     * Adding article to corssselling list
     */
    public function assign_cat(): void
    {
        $s_chosen_cat = Registry::get_request()->get_request_escaped_parameter('oxcatid');
        $s_shop_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $o_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        if ($o_shop->load($s_shop_id)) {
            $o_shop->oxshops__oxdefcat = new \Oxid_Esales\Eshop\Core\Field($s_chosen_cat);
            $o_shop->save();
        }
    }
}