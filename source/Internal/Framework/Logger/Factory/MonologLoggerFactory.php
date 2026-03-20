<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Factory;

use Monolog\Formatter\Line_Formatter;
use Monolog\Handler\Stream_Handler;
use Monolog\Logger;
use Psr\Log\Logger_Interface;
readonly class Monolog_Logger_Factory implements Logger_Factory_Interface
{
    public function __construct(private string $logger_name, private string $log_file_path, private string $log_level)
    {
    }
    public function create(): Logger_Interface
    {
        $handler = $this->get_handler();
        $logger = new Logger($this->logger_name);
        $logger->push_handler($handler);
        return $logger;
    }
    private function get_handler(): Stream_Handler
    {
        $handler = new Stream_Handler($this->log_file_path, $this->log_level);
        $formatter = new Line_Formatter();
        $formatter->include_stacktraces();
        $handler->set_formatter($formatter);
        return $handler;
    }
}