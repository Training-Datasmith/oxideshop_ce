<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin usergroup list manager.
 * Performs collection and managing (such as filtering or deleting) function.
 * Admin Menu: User Administration -> User Groups.
 */
class User_Group_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxgroups';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxtitle';
    /**
     * Executes parent method parent::render() and returns name of template
     * file "usergroup_list".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        return 'usergroup_list';
    }
}