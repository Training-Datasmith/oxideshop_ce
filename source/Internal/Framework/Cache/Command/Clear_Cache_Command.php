<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Command;

use Oxid_Esales\Eshop_Community\Internal\Framework\Cache\Shop_Cache_Cleaner_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service\Container_Cache_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
class Clear_Cache_Command extends Command
{
    public function __construct(private readonly Container_Cache_Interface $container_cache, private readonly Context_Interface $context, private readonly Shop_Cache_Cleaner_Interface $shop_cache_cleaner)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_description('Clears shop cache');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $this->shop_cache_cleaner->clear_all();
        foreach ($this->context->get_all_shop_ids() as $shop_id) {
            $this->container_cache->invalidate($shop_id);
        }
        $output->writeln('<info>Cleared cache files</info>');
        return 0;
    }
}