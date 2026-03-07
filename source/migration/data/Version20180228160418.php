<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * change oxconfig::oxvarvalue type to mediumblob for large module lists
 */
class Version20180228160418 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = "ALTER TABLE `oxconfig` CHANGE COLUMN `OXVARVALUE` `OXVARVALUE` text NOT NULL COMMENT 'Variable value' AFTER `OXVARTYPE`;";
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
