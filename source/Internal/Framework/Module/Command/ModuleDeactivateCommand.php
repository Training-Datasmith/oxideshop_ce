<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Command;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service\Module_Activation_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Argument;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
class Module_Deactivate_Command extends Command
{
    public const MESSAGE_MODULE_DEACTIVATED = 'Module - "%s" has been deactivated.';
    public const MESSAGE_MODULE_NOT_FOUND = 'Module - "%s" not found.';
    private const ARGUMENT_MODULE_ID = 'module-id';
    public function __construct(private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Context_Interface $context, private readonly Module_Activation_Service_Interface $module_activation_service)
    {
        parent::__construct();
    }
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->set_description('Deactivates a module.')->add_argument(static::ARGUMENT_MODULE_ID, Input_Argument::REQUIRED, 'Module ID')->set_help('Command deactivates module by defined module ID.');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $module_id = $input->get_argument('module-id');
        if ($this->is_installed($module_id)) {
            $this->deactivate_module($output, $module_id);
        } else {
            $output->write_ln('<error>' . sprintf(static::MESSAGE_MODULE_NOT_FOUND, $module_id) . '</error>');
        }
        return 0;
    }
    protected function deactivate_module(Output_Interface $output, string $module_id)
    {
        $this->module_activation_service->deactivate($module_id, $this->context->get_current_shop_id());
        $output->write_ln('<info>' . sprintf(static::MESSAGE_MODULE_DEACTIVATED, $module_id) . '</info>');
    }
    private function is_installed(string $module_id): bool
    {
        return $this->module_configuration_dao->exists($module_id, $this->context->get_current_shop_id());
    }
}