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
 * Admin Contents manager.
 * Collects Content base information (Description), there is ability to filter
 * them by Description or delete them.
 * Admin Menu: Customerinformations -> Content.
 */
class Content_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxcontent';
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxcontentlist';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'content_list';
    /**
     * Executes parent method parent::render() and returns current class template
     * name.
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $s_folder = \Oxid_Esales\Eshop\Core\Registry::get_request()->get_request_escaped_parameter('folder');
        $s_folder = $s_folder ?: -1;
        $this->_a_view_data['folder'] = $s_folder;
        $this->_a_view_data['afolder'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aCMSfolder');
        return $this->_s_this_template;
    }
    /**
     * Adding folder check and empty folder field check.
     *
     * @param array  $aWhere  SQL condition array
     * @param string $sqlFull SQL query string
     *
     * @return string
     */
    protected function prepare_where_query($a_where, $sql_full)
    {
        $s_q = parent::prepare_where_query($a_where, $sql_full);
        $s_folder = Registry::get_request()->get_request_escaped_parameter('folder');
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxcontents');
        //searchong for empty oxfolder fields
        if ($s_folder == 'CMSFOLDER_NONE' || $s_folder == 'CMSFOLDER_NONE_RR') {
            $s_q .= " and {$s_view_name}.oxfolder = '' ";
        } elseif ($s_folder && $s_folder != '-1') {
            $s_folder = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($s_folder);
            $s_q .= " and {$s_view_name}.oxfolder = {$s_folder}";
        }
        return $s_q;
    }
}