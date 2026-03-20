<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class used for counting users depending on given attributes.
 */
class User_Counter
{
    /**
     * Returns count of admins (mall and subshops). Only counts active admins.
     */
    public function get_admin_count(): int
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_query = "SELECT COUNT(1) FROM oxuser WHERE oxrights != 'user'";
        return (int) $o_db->get_one($s_query);
    }
    /**
     * Returns count of admins (mall and subshops). Only counts active admins.
     */
    public function get_active_admin_count(): int
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_query = "SELECT COUNT(1) FROM oxuser WHERE oxrights != 'user' AND oxactive = 1 ";
        return (int) $o_db->get_one($s_query);
    }
}