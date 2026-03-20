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
 * Admin actionss manager.
 * Sets list template, list object class ('oxactions') and default sorting
 * field ('oxactions.oxtitle').
 * Admin Menu: Manage Products -> Actions.
 */
class Actions_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'actions_list';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxactions';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxtitle';
    /**
     * Calls parent::render() and returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        // passing display type back to view
        $this->_a_view_data['displaytype'] = Registry::get_request()->get_request_escaped_parameter('displaytype');
        return $this->_s_this_template;
    }
    /**
     * Adds active promotion check
     *
     * @param array  $aWhere  SQL condition array
     * @param string $sqlFull SQL query string
     *
     * @return $sQ
     */
    protected function prepare_where_query($a_where, $sql_full)
    {
        $s_q = parent::prepare_where_query($a_where, $sql_full);
        $s_display_type = (int) Registry::get_request()->get_request_escaped_parameter('displaytype');
        $table_view_name_generator = new Table_View_Name_Generator();
        $s_table = $table_view_name_generator->get_view_name('oxactions');
        // searching for empty oxfolder fields
        if ($s_display_type) {
            $s_now = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
            switch ($s_display_type) {
                case 1:
                    // active
                    $s_q .= " and {$s_table}.oxactivefrom < '{$s_now}' and {$s_table}.oxactiveto > '{$s_now}' ";
                    break;
                case 2:
                    // upcoming
                    $s_q .= " and {$s_table}.oxactivefrom > '{$s_now}' ";
                    break;
                case 3:
                    // expired
                    $s_q .= " and {$s_table}.oxactiveto < '{$s_now}' and {$s_table}.oxactiveto != '0000-00-00 00:00:00' ";
                    break;
            }
        }
        return $s_q;
    }
}