<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Decorator for
 */
class Admin_Log_Sql_Decorator
{
    protected $table = 'oxadminlog';
    /**
     * Injects argument to admin log insert sql.
     *
     * @param string $originalSql
     */
    public function prepare_sql_for_logging($original_sql): string
    {
        $user_id = $this->get_user_id();
        return "insert into {$this->table} (oxuserid, oxsql) values ('{$user_id}', " . $this->quote($original_sql) . ')';
    }
    /**
     * Get currently logged in admin user id
     *
     * @return string
     */
    protected function get_user_id()
    {
        $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        if ($user->load_admin_user()) {
            return $user->get_id();
        }
    }
    /**
     * Quotes the string for saving in database field;
     *
     * @param string $str
     *
     * @return string
     */
    protected function quote($str)
    {
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($str);
    }
}