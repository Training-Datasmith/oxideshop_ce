<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin user list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: User Administration -> Users.
 */
class User_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxuser';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxusername';
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxuserlist';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'user_list';
    /**
     * Executes parent::render(), sets blacklist and preventdelete flag
     */
    public function render()
    {
        foreach ($this->get_item_list() as $item_id => $user) {
            /** @var \OxidEsales\Eshop\Application\Model\User $user */
            if ($user->in_group('oxidblacklist') || $user->in_group('oxidblocked')) {
                $user->blacklist = '1';
            }
            $user->bl_prevent_delete = false;
            if (!$this->allow_admin_edit($item_id)) {
                $user->bl_prevent_delete = true;
            }
        }
        return parent::render();
    }
    /**
     * Admin user is allowed to be deleted only by mall admin
     */
    public function delete_entry()
    {
        if ($this->allow_admin_edit($this->get_edit_object_id())) {
            $this->_o_list = null;
            return parent::delete_entry();
        }
    }
    /**
     * Prepares SQL where query according SQL condition array and attaches it to SQL end.
     * For each search value if german umlauts exist, adds them
     * and replaced by spec. char to query
     *
     * @param array  $whereQuery SQL condition array
     * @param string $fullQuery  SQL query string
     *
     * @return string
     */
    public function prepare_where_query($where_query, $full_query)
    {
        $name_where = null;
        if (isset($where_query['oxuser.oxlname']) && $name = $where_query['oxuser.oxlname']) {
            // check if this is search string (contains % sign at begining and end of string)
            $is_search_value = $this->is_search_value($name);
            $name = $this->process_filter($name);
            $name_where['oxuser.oxfname'] = $name_where['oxuser.oxlname'] = $name;
            unset($where_query['oxuser.oxlname']);
        }
        $query = parent::prepare_where_query($where_query, $full_query);
        if ($name_where) {
            $values = explode(' ', $name);
            $query .= ' and (';
            $query_bool_action = '';
            $utils_string = \Oxid_Esales\Eshop\Core\Registry::get_utils_string();
            foreach ($name_where as $field_name => $field_value) {
                //for each search field using AND action
                foreach ($values as $value) {
                    $query .= " {$query_bool_action} {$field_name} ";
                    //for search in same field for different values using AND
                    $query_bool_action = ' or ';
                    $query .= $this->build_filter($value, $is_search_value);
                    // trying to search spec chars in search value
                    // if found, add cleaned search value to search sql
                    $uml = $utils_string->prepare_str_for_search($value);
                    if ($uml) {
                        $query .= " or {$field_name} ";
                        $query .= $this->build_filter($uml, $is_search_value);
                    }
                }
            }
            // end for AND action
            $query .= ' ) ';
        }
        return $query;
    }
}