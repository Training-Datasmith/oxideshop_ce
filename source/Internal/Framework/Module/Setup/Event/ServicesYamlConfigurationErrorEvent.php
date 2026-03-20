<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event;

use Symfony\Contracts\Event_Dispatcher\Event;
/**
 * This event is dispatched when there are not loadable service classes
 * found in a services.yaml file.
 */
class Services_Yaml_Configuration_Error_Event extends Event
{
    public function __construct(private readonly string $error_message, private readonly string $configuration_file_path)
    {
    }
    /**
     * Returns the file that is misconfigured
     */
    public function get_configuration_file_path(): string
    {
        return $this->configuration_file_path;
    }
    public function get_error_message(): string
    {
        return $this->error_message;
    }
}