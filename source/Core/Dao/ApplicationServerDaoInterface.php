<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Dao;

/**
 * Application server data access manager.
 *
 * @internal Do not make a module extension for this class.
 */
interface Application_Server_Dao_Interface extends \Oxid_Esales\Eshop\Core\Dao\Base_Dao_Interface
{
    /**
     * Finds an application server by given id, null if none is found.
     *
     * @param string $id An id of the entity to find.
     *
     * @return \OxidEsales\Eshop\Core\DataObject\ApplicationServer|null
     */
    public function find_app_server($id);
}