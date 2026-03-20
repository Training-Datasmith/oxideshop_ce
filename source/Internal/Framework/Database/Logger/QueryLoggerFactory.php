<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database\Logger;

use Monolog\Handler\Stream_Handler;
use Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Factory\Logger_Factory_Interface;
use Psr\Log\Logger_Interface;
readonly class Query_Logger_Factory implements Logger_Factory_Interface
{
    public function __construct(private Query_Log_Filter_Interface $query_log_filter, private Query_Log_Context_Extender_Interface $query_log_context_extender, private Stream_Handler $stream_handler, private string $logger_name)
    {
    }
    public function create(): Logger_Interface
    {
        return (new Query_Logger($this->logger_name, $this->query_log_filter, $this->query_log_context_extender))->push_handler($this->stream_handler);
    }
}