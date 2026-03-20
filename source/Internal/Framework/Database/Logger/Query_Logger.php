<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database\Logger;

use Monolog\Logger;
class Query_Logger extends Logger
{
    public function __construct(string $logger_name, private readonly Query_Log_Filter_Interface $query_log_filter, private readonly Query_Log_Context_Extender_Interface $query_log_context_extender)
    {
        parent::__construct($logger_name);
    }
    public function add_record($level, $message, array $context = []): bool
    {
        return isset($context['sql']) && $this->query_log_filter->should_log_query($context['sql']) && parent::add_record($level, $message, $this->query_log_context_extender->extend($context));
    }
}