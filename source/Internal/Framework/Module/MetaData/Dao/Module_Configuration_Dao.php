<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

// phpcs:disable
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Data_Mapper\Meta_Data_To_Module_Configuration_Data_Mapper_Interface;
// phpcs:enable
class Module_Configuration_Dao implements Module_Configuration_Dao_Interface
{
    private string $metadata_file_name = 'metadata.php';
    public function __construct(private readonly Meta_Data_Provider_Interface $metadata_provider, private readonly Meta_Data_To_Module_Configuration_Data_Mapper_Interface $metadata_mapper)
    {
    }
    /**
     * @throws \OxidEsales\EshopCommunity\Internal\Framework\Module\MetaData\Exception\InvalidMetaDataException
     */
    public function get(string $module_path): Module_Configuration
    {
        $metadata = $this->metadata_provider->get_data($this->get_metadata_file_path($module_path));
        return $this->metadata_mapper->from_data($metadata);
    }
    private function get_metadata_file_path(string $module_full_path): string
    {
        return $module_full_path . DIRECTORY_SEPARATOR . $this->metadata_file_name;
    }
}