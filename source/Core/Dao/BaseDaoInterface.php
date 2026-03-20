<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Dao;

/**
 * Data access object interface.
 *
 * @internal Do not make a module extension for this class.
 */
interface Base_Dao_Interface
{
    /**
     * Finds all entities.
     *
     * @return array
     */
    public function find_all();
    /**
     * Deletes the entity with the given id.
     *
     * @param string $id An id of the entity to delete.
     */
    public function delete($id);
    /**
     * Updates or insert the given entity.
     *
     * @param object $object
     */
    public function save($object);
    /**
     * Start a database transaction.
     */
    public function start_transaction();
    /**
     * Commit a database transaction.
     */
    public function commit_transaction();
    /**
     * RollBack a database transaction.
     */
    public function rollback_transaction();
}