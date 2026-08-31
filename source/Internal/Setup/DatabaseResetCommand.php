<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Setup;

use OxidEsales\EshopCommunity\Internal\Framework\Database\Configuration\DataObject\DatabaseConfiguration;
use OxidEsales\EshopCommunity\Internal\Setup\Database\SetupDbConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Database\ShopDbManagerInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Language\DefaultLanguage;
use OxidEsales\EshopCommunity\Internal\Setup\Language\LanguageInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Setup\Parameters\SetupParametersFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseResetCommand extends Command
{
    public function __construct(
        private readonly SetupParametersFactoryInterface $setupParametersFactory,
        private readonly SetupDbConnectionFactoryInterface $databaseConnectionFactory,
        private readonly ShopDbManagerInterface $shopDbManager,
        private readonly LanguageInstallerInterface $languageInstaller
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('oe:database:reset')
            ->setDescription('Drops and recreates the shop database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Reset without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $setupParameters = $this->setupParametersFactory->create(new DefaultLanguage('en'));
        $databaseConfiguration = $setupParameters->getDbConfig();

        $this->dropDatabase($databaseConfiguration);
        $this->shopDbManager->create($databaseConfiguration);
        $this->languageInstaller->install($setupParameters->getLanguage());

        $output->writeln('<info>Database has been reset.</info>');

        return Command::SUCCESS;
    }

    private function dropDatabase(DatabaseConfiguration $databaseConfiguration): void
    {
        $connection = $this->databaseConnectionFactory->getServerConnection($databaseConfiguration);
        $connection->executeStatement(
            sprintf('DROP DATABASE IF EXISTS `%s`', $databaseConfiguration->getName())
        );
        $connection->close();
    }
}
