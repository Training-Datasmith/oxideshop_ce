<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database\Logger;

interface Query_Log_Filter_Interface
{
    public function should_log_query(string $query): bool;
}