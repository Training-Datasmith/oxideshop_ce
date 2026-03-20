<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Exception;
/**
 * Counter class
 */
class Counter
{
    /**
     * Return the next counter value for a given type of counter
     *
     * @param string $ident Identifies the type of counter. E.g. 'oxOrder'
     *
     * @throws Exception
     *
     * @return int Next counter value
     */
    public function get_next($ident): int
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        /** Current counter retrieval needs to be encapsulated in transaction */
        $database->start_transaction();
        try {
            /** Block row for reading until the counter is updated */
            $query = 'SELECT `oxcount` FROM `oxcounters` WHERE `oxident` = :oxident FOR UPDATE';
            $current_counter = (int) $database->get_one($query, ['oxident' => $ident]);
            $next_counter = $current_counter + 1;
            /** Insert or increment the the counter */
            $query = 'INSERT INTO `oxcounters` (`oxident`, `oxcount`) VALUES (:oxident, 1) ON DUPLICATE KEY UPDATE `oxcount` = `oxcount` + 1';
            $database->execute($query, ['oxident' => $ident]);
            $database->commit_transaction();
        } catch (Exception $exception) {
            $database->rollback_transaction();
            throw $exception;
        }
        return $next_counter;
    }
    /**
     * Update the counter value for a given type of counter, but only when it is greater than the current value
     *
     * @param string  $ident Identifies the type of counter. E.g. 'oxOrder'
     * @param integer $count New counter value
     *
     * @throws Exception
     *
     * @return int Number of affected rows
     */
    public function update($ident, $count)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        /** Current counter retrieval needs to be encapsulated in transaction */
        $database->start_transaction();
        try {
            /** Block row for reading until the counter is updated */
            $query = 'SELECT `oxcount` FROM `oxcounters` WHERE `oxident` = :oxident FOR UPDATE';
            $database->get_one($query, ['oxident' => $ident]);
            /** Insert or update the counter, if the value to be updated is greater, than the current value */
            $query = 'INSERT INTO `oxcounters` (`oxident`, `oxcount`) VALUES (:oxident, :oxcount) ON DUPLICATE KEY UPDATE `oxcount` = IF(:oxcount > oxcount, :oxcount, oxcount)';
            $result = $database->execute($query, ['oxident' => $ident, 'oxcount' => $count]);
            $database->commit_transaction();
        } catch (Exception $exception) {
            $database->rollback_transaction();
            throw $exception;
        }
        return $result;
    }
}