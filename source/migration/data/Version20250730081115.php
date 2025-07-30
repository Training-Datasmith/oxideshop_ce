<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250730081115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete OXISOALPHA2 column from oxstates table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('oxstates');
        if ($table->hasColumn('OXISOALPHA2')) {
            $table->dropColumn('OXISOALPHA2');
        }

        $table->setComment('States/Provinces/Regions assigned to countries');
    }

    public function down(Schema $schema): void
    {
    }
}
