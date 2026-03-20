<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Command;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service\Module_Installer_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Argument;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Console\Style\Symfony_Style;
use Symfony\Component\Filesystem\Path;
class Module_Install_Command extends Command
{
    private const SUCCESS_MESSAGE = 'Module installed successfully';
    private const ERROR_MESSAGE = 'Error installing module: ';
    public function __construct(private readonly Module_Installer_Interface $module_installer)
    {
        parent::__construct();
    }
    /** @inheritdoc */
    protected function configure(): void
    {
        $this->set_description('Install module assets and configuration')->add_argument('module-path', Input_Argument::REQUIRED, 'Absolute or relative path to module files');
    }
    /**
     * @throws \Throwable
     */
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $style = new Symfony_Style($input, $output);
        try {
            $module_path = $this->get_module_path($input->get_argument('module-path'));
            $this->module_installer->install($this->get_package($module_path));
            $style->success(self::SUCCESS_MESSAGE);
            return Command::SUCCESS;
        } catch (\InvalidArgumentException $exception) {
            $style->error(self::ERROR_MESSAGE . $exception->get_message());
        } catch (\Throwable $throwable) {
            $style->error(self::ERROR_MESSAGE . $throwable->get_message());
            $style->text($throwable->get_trace_as_string());
        }
        return Command::FAILURE;
    }
    private function get_module_path(string $path): string
    {
        return Path::is_relative($path) ? Path::make_absolute($path, getcwd()) : $path;
    }
    private function get_package(string $module_path): Oxid_Eshop_Package
    {
        return new Oxid_Eshop_Package($module_path);
    }
}