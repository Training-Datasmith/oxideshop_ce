<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Ox_Admin_List;
/**
 * user list "view" class.
 */
class List_User extends \Oxid_Esales\Eshop\Application\Controller\Admin\User_List
{
    /**
     * Viewable list size getter
     *
     * @return int
     */
    public function get_view_list_size()
    {
        return $this->get_user_def_list_size();
    }
    /**
     * Sets SQL query parameters (such as sorting),
     * executes parent method parent::Init().
     */
    public function init(): void
    {
        Ox_Admin_List::init();
    }
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $this->_a_view_data['menustructure'] = $this->get_navigation()->get_dom_xml()->document_element->child_nodes;
        return 'list_user';
    }
}