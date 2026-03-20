<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Command;

use Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Shop_Cache_Cleaner_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Argument;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
class Theme_Activate_Command extends Command
{
    private const MESSAGE_THEME_IS_ACTIVE = 'Theme - "%s" is already active.';
    private const MESSAGE_THEME_ACTIVATED = 'Theme - "%s" was activated.';
    private const MESSAGE_THEME_NOT_FOUND = 'Theme - "%s" not found.';
    public function __construct(private readonly Shop_Adapter_Interface $shop_adapter, private readonly Shop_Cache_Cleaner_Interface $shop_cache_cleaner)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_description('Activates a theme.')->add_argument('theme-id', Input_Argument::REQUIRED, 'Theme ID')->set_help('Command activates theme by defined theme ID.');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $theme_id = $input->get_argument('theme-id');
        if (!$this->shop_adapter->theme_exists($theme_id)) {
            $output->write_ln('<error>' . sprintf(self::MESSAGE_THEME_NOT_FOUND, $theme_id) . '</error>');
            return Command::INVALID;
        }
        if ($this->shop_adapter->get_active_theme_id() === $theme_id) {
            $output->writeln('<comment>' . sprintf(self::MESSAGE_THEME_IS_ACTIVE, $theme_id) . '</comment>');
            return Command::SUCCESS;
        }
        $this->shop_adapter->activate_theme($theme_id);
        $this->shop_cache_cleaner->clear_all();
        $output->write_ln('<info>' . sprintf(self::MESSAGE_THEME_ACTIVATED, $theme_id) . '</info>');
        return Command::SUCCESS;
    }
}