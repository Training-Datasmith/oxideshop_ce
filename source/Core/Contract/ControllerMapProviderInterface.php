<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * The implementation of this class determines the controllers which should be allowed to be called directly via
 * HTTP GET/POST Parameters, inside form actions or with oxid_include_widget.
 * Those controllers are specified e.g. inside a form action with a controller key which is mapped to its class.
 */
interface Controller_Map_Provider_Interface
{
    /**
     * Get all controller keys and their assigned classes
     *
     * @return array
     */
    public function get_controller_map();
}