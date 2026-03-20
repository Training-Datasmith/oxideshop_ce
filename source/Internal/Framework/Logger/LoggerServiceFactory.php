<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Logger;

use Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Factory\Monolog_Logger_Factory;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Psr\Log\Logger_Interface;
readonly class Logger_Service_Factory
{
    public function __construct(private Context_Interface $context)
    {
    }
    public function get_logger(): Logger_Interface
    {
        return (new Monolog_Logger_Factory('OXID Logger', $this->context->get_log_file_path(), $this->context->get_log_level()))->create();
    }
}