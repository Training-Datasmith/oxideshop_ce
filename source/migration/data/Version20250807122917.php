<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250807122917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update state IDs for Canada';
    }

    public function up(Schema $schema): void
    {
        $stateUpdates = [
            'AB' => 'CA-AB',
            'BC' => 'CA-BC',
            'MB' => 'CA-MB',
            'NB' => 'CA-NB',
            'NF' => 'CA-NL',
            'NS' => 'CA-NS',
            'NT' => 'CA-NT',
            'NU' => 'CA-NU',
            'ON' => 'CA-ON',
            'PE' => 'CA-PE',
            'QC' => 'CA-QC',
            'SK' => 'CA-SK',
            'YK' => 'CA-YT',
        ];

        foreach ($stateUpdates as $oldId => $newId) {
            $this->addSql(
                'UPDATE oxstates SET OXID = :newId WHERE OXID = :oldId AND OXCOUNTRYID = :countryId',
                [
                    'newId' => $newId,
                    'oldId' => $oldId,
                    'countryId' => $this->getCanadaCountryId(),
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
    }

    private function getCanadaCountryId(): string
    {
        return $this->connection->fetchOne(
            'SELECT OXID FROM oxcountry WHERE OXISOALPHA3 = :isoCode',
            ['isoCode' => 'CAN']
        );
    }
}
