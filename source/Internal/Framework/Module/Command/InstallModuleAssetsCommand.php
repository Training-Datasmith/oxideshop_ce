<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Command;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service\Module_Files_Installer_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Console\Style\Symfony_Style;
use Symfony\Component\Filesystem\Path;
class Install_Module_Assets_Command extends Command
{
    public function __construct(private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Basic_Context_Interface $context, private readonly Module_Files_Installer_Interface $module_files_installer)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_description('Install assets for all modules (symlink or copy to the shop out directory depending on the platform).');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $shop_configuration = $this->shop_configuration_dao->get($this->context->get_default_shop_id());
        foreach ($shop_configuration->get_module_configurations() as $module_configuration) {
            $this->install_module_asserts($module_configuration);
        }
        $style = new Symfony_Style($input, $output);
        $style->success('Module assets have been installed.');
        return Command::SUCCESS;
    }
    private function install_module_asserts(Module_Configuration $module_configuration): void
    {
        $this->module_files_installer->install(new Oxid_Eshop_Package(Path::join($this->context->get_shop_root_path(), $module_configuration->get_module_source())));
    }
}