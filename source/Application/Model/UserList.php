<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * User list manager.
 */
class User_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('oxuser');
    }
    /**
     * Load searched user list with wishlist
     *
     * @param string $sSearchStr Search string
     */
    public function load_wishlist_users($s_search_str): void
    {
        $s_search_str = trim($s_search_str);
        if (!$s_search_str) {
            return;
        }
        $s_select = 'select oxuser.oxid, oxuser.oxfname, oxuser.oxlname from oxuser ';
        $s_select .= 'left join oxuserbaskets on oxuserbaskets.oxuserid = oxuser.oxid ';
        $s_select .= "where oxuserbaskets.oxid is not null and oxuserbaskets.oxtitle = 'wishlist' ";
        $s_select .= 'and oxuserbaskets.oxpublic = 1 ';
        $s_select .= 'and ( oxuser.oxusername = :search or oxuser.oxlname = :search)';
        $s_select .= 'and ( select 1 from oxuserbasketitems where oxuserbasketitems.oxbasketid = oxuserbaskets.oxid limit 1)';
        $this->select_string($s_select, ['search' => "{$s_search_str}"]);
    }
}