<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database;

use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Configuration\Data_Object\Database_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
readonly class Connection_Parameter_Provider implements Connection_Parameter_Provider_Interface
{
    public function __construct(private Basic_Context_Interface $basic_context)
    {
    }
    public function get_parameters(): array
    {
        return (new Database_Configuration($this->basic_context->get_database_url()))->get_connection_parameters();
    }
}