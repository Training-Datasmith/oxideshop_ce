<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Command;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Configuration_Not_Found_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service\Module_Installer_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Path_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Argument;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Console\Style\Symfony_Style;
class Module_Uninstall_Command extends Command
{
    private const SUCCESS_MESSAGE = 'Module uninstalled successfully';
    private const ERROR_MESSAGE = 'Error uninstalling module: ';
    public function __construct(private readonly Module_Installer_Interface $module_installer, private readonly Module_Path_Resolver_Interface $module_path_resolver, private readonly Context_Interface $context)
    {
        parent::__construct();
    }
    /** @inheritdoc */
    protected function configure(): void
    {
        $this->set_description('Uninstall module assets and configuration')->add_argument('module-id', Input_Argument::REQUIRED, 'Module ID (see metadata.php)');
    }
    /**
     * @throws \Throwable
     */
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $style = new Symfony_Style($input, $output);
        try {
            $module_path = $this->get_module_path($input->get_argument('module-id'));
            $this->module_installer->uninstall($this->get_package($module_path));
            $style->success(self::SUCCESS_MESSAGE);
            return Command::SUCCESS;
        } catch (Module_Configuration_Not_Found_Exception $exception) {
            $style->error(self::ERROR_MESSAGE . $exception->get_message());
        } catch (\Throwable $throwable) {
            $style->error(self::ERROR_MESSAGE . $throwable->get_message());
            $style->text($throwable->get_trace_as_string());
        }
        return Command::FAILURE;
    }
    private function get_module_path(string $module_id): string
    {
        return $this->module_path_resolver->get_full_module_path_from_configuration($module_id, $this->context->get_default_shop_id());
    }
    private function get_package(string $module_path): Oxid_Eshop_Package
    {
        return new Oxid_Eshop_Package($module_path);
    }
}