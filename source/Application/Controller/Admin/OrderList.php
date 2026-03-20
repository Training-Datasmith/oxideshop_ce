<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin order list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: Orders -> Display Orders.
 */
class Order_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxorder';
    /**
     * Enable/disable sorting by DESC (SQL) (defaultfalse - disable).
     *
     * @var bool
     */
    protected $_bl_desc = true;
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxorderdate';
    /**
     * Executes parent method parent::render() and returns name of template
     * file "order_list".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $folders = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aOrderfolder');
        $folder = Registry::get_request()->get_request_escaped_parameter('folder');
        // first display new orders
        if (!$folder && is_array($folders)) {
            $names = array_keys($folders);
            $folder = $names[0];
        }
        $search = ['oxorderarticles' => 'ARTID', 'oxpayments' => 'PAYMENT'];
        $search_query = Registry::get_request()->get_request_escaped_parameter('addsearch');
        $search_field = Registry::get_request()->get_request_escaped_parameter('addsearchfld');
        $this->_a_view_data['folder'] = $folder ?: -1;
        $this->_a_view_data['addsearchfld'] = $search_field ?: -1;
        $this->_a_view_data['asearch'] = $search;
        $this->_a_view_data['addsearch'] = $search_query;
        $this->_a_view_data['afolder'] = $folders;
        return 'order_list';
    }
    /**
     * Cancels order and its order articles
     * Calls init() to reload list items after cancellation.
     */
    public function cancel_order(): void
    {
        $order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($order->load($this->get_edit_object_id())) {
            $order->cancel_order();
        }
        $this->reset_content_cache();
        $this->init();
    }
    /**
     * Returns sorting fields array
     *
     * @return array
     */
    public function get_list_sorting()
    {
        $sorting = parent::get_list_sorting();
        if (isset($sorting['oxorder']['oxbilllname'])) {
            $this->_bl_desc = false;
        }
        return $sorting;
    }
    /**
     * Adding folder check
     *
     * @param array  $whereQuery SQL condition array
     * @param string $fullQuery  SQL query string
     *
     * @return string
     */
    protected function prepare_where_query($where_query, $full_query)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $query = parent::prepare_where_query($where_query, $full_query);
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $folders = $config->get_config_param('aOrderfolder');
        $folder = Registry::get_request()->get_request_escaped_parameter('folder');
        // Searching for empty oxfolder fields
        if ($folder && $folder != '-1') {
            $query .= ' and ( oxorder.oxfolder = ' . $database->quote($folder) . ' )';
        } elseif (!$folder && is_array($folders)) {
            $folder_names = array_keys($folders);
            $query .= ' and ( oxorder.oxfolder = ' . $database->quote($folder_names[0]) . ' )';
        }
        return $query;
    }
    /**
     * Builds and returns SQL query string. Adds additional order check.
     *
     * @param object $listObject list main object
     *
     * @return string
     */
    protected function build_select_string($list_object = null)
    {
        $query = parent::build_select_string($list_object);
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $search_query = Registry::get_request()->get_request_escaped_parameter('addsearch');
        $search_query = trim((string) $search_query);
        $search_field = Registry::get_request()->get_request_escaped_parameter('addsearchfld');
        if ($search_query) {
            $query_part = match ($search_field) {
                'oxorderarticles' => 'oxorder left join oxorderarticles on oxorderarticles.oxorderid=oxorder.oxid where ( oxorderarticles.oxartnum like ' . $database->quote("%{$search_query}%") . ' or oxorderarticles.oxtitle like ' . $database->quote("%{$search_query}%") . ' ) and ',
                'oxpayments' => 'oxorder left join oxpayments on oxpayments.oxid=oxorder.oxpaymenttype where oxpayments.oxdesc like ' . $database->quote("%{$search_query}%") . ' and ',
                default => 'oxorder where oxorder.oxpaid like ' . $database->quote("%{$search_query}%") . ' and ',
            };
            $query = str_replace('oxorder where', $query_part, $query);
        }
        return $query;
    }
}