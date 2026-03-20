<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Admin pricealarm list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: Customer Info -> pricealarm.
 */
class Price_Alarm_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'pricealarm_list';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxpricealarm';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxuserid';
    /**
     * Modifying SQL query to load additional article and customer data
     *
     * @param object $oListObject list main object
     *
     * @return string
     */
    protected function build_select_string($o_list_object = null)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxarticles', (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sDefaultLang'));
        $s_sql = "select oxpricealarm.*, {$s_view_name}.oxtitle AS articletitle, ";
        $s_sql .= 'oxuser.oxlname as userlname, oxuser.oxfname as userfname ';
        $s_sql .= "from oxpricealarm left join {$s_view_name} on {$s_view_name}.oxid = oxpricealarm.oxartid ";
        return $s_sql . 'left join oxuser on oxuser.oxid = oxpricealarm.oxuserid WHERE 1 ';
    }
    /**
     * Builds and returns array of SQL WHERE conditions
     *
     * @return array
     */
    public function build_where()
    {
        $this->_a_where = parent::build_where();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxpricealarm');
        $s_art_view_name = $table_view_name_generator->get_view_name('oxarticles');
        // updating price fields values for correct search in DB
        if (isset($this->_a_where[$s_view_name . '.oxprice'])) {
            $s_price_param = (float) str_replace(['%', ','], ['', '.'], $this->_a_where[$s_view_name . '.oxprice']);
            $this->_a_where[$s_view_name . '.oxprice'] = '%' . $s_price_param . '%';
        }
        if (isset($this->_a_where[$s_art_view_name . '.oxprice'])) {
            $s_price_param = (float) str_replace(['%', ','], ['', '.'], $this->_a_where[$s_art_view_name . '.oxprice']);
            $this->_a_where[$s_art_view_name . '.oxprice'] = '%' . $s_price_param . '%';
        }
        return $this->_a_where;
    }
}