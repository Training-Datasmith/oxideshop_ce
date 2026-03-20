<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Controller;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Shop_Configuration_Not_Found_Exception;
use Psr\Log\Logger_Interface;
class Active_Modules_Data_Provider_Bridge implements Active_Modules_Data_Provider_Bridge_Interface
{
    private array $chain;
    public function __construct(private readonly Active_Modules_Data_Provider_Interface $active_modules_data_provider, private readonly Logger_Interface $logger)
    {
    }
    /**
     * @inheritDoc
     */
    public function get_module_ids(): array
    {
        return $this->active_modules_data_provider->get_module_ids();
    }
    /**
     * @inheritDoc
     */
    public function get_module_paths(): array
    {
        return $this->active_modules_data_provider->get_module_paths();
    }
    /**
     * @return Controller[]
     */
    public function get_controllers(): array
    {
        return $this->active_modules_data_provider->get_controllers();
    }
    public function get_class_extensions(): array
    {
        if (isset($this->chain)) {
            return $this->chain;
        }
        try {
            $this->chain = $this->active_modules_data_provider->get_class_extensions();
        } catch (Shop_Configuration_Not_Found_Exception $exception) {
            $this->chain = [];
            $this->logger->error($exception->get_message(), [$exception]);
        }
        return $this->chain;
    }
}