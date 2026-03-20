<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

/**
 * @deprecated will be removed in v7.0
 */
interface Meta_Data_Schemata_Provider_Interface
{
    public function get_meta_data_schemata(): array;
    public function get_meta_data_schema_for_version(string $meta_data_version): array;
    public function get_flipped_meta_data_schema_for_version(string $meta_data_version): array;
}