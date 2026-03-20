<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Admin shop list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: Main Menu -> Core Settings.
 */
class Shop_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /** New Shop indicator. */
    public const NEW_SHOP_ID = '-1';
    /**
     * Forces main frame update is set TRUE
     *
     * @var bool
     */
    protected $_bl_update_main = false;
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxname';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxshop';
    /**
     * Navigation frame reload marker
     *
     * @var bool
     */
    protected $_bl_update_nav;
    /**
     * Executes parent method parent::render() and returns name of template
     * file "shop_list".
     *
     * @return string
     */
    public function render()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != self::NEW_SHOP_ID) {
            // load object
            $o_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
            if (!$o_shop->load($sox_id)) {
                $sox_id = $my_config->get_base_shop_id();
                $o_shop->load($sox_id);
            }
            $this->_a_view_data['editshop'] = $o_shop;
        }
        // default page number 1
        $this->_a_view_data['default_edit'] = 'shop_main';
        $this->_a_view_data['updatemain'] = $this->_bl_update_main;
        $this->update_navigation();
        if (isset($this->_a_view_data['updatenav']) && $this->_a_view_data['updatenav']) {
            //skipping requirements checking when reloading nav frame
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('navReload', true);
        }
        //making sure we really change shops on low level
        if ($sox_id && $sox_id != self::NEW_SHOP_ID) {
            $my_config->set_shop_id($sox_id);
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('currentadminshop', $sox_id);
        }
        return 'shop_list';
    }
    /**
     * Sets SQL WHERE condition. Returns array of conditions.
     *
     * @return array
     */
    public function build_where()
    {
        // we override this to add our shop if we are not malladmin
        $this->_a_where = parent::build_where();
        if (!\Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('malladmin')) {
            // we only allow to see our shop
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $this->_a_where[$table_view_name_generator->get_view_name('oxshops') . '.oxid'] = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('actshop');
        }
        return $this->_a_where;
    }
    /**
     * Set to view data if update navigation menu.
     */
    protected function update_navigation()
    {
    }
}