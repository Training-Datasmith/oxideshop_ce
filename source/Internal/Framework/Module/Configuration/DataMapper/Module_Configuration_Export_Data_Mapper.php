<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\State\Module_State_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Module_Configuration_Export_Data_Mapper implements Module_Configuration_Export_Data_Mapper_Interface
{
    private readonly array $data_mappers;
    public function __construct(private readonly Module_State_Service_Interface $module_state_service, private readonly Context_Interface $context, Module_Configuration_Export_Data_Mapper_Interface ...$data_mappers)
    {
        $this->data_mappers = $data_mappers;
    }
    public function to_data(Module_Configuration $configuration): array
    {
        $data = ['id' => $configuration->get_id(), 'title' => $this->get_preferred_translation($configuration->get_title()), 'description' => $this->get_preferred_translation($configuration->get_description()), 'version' => $configuration->get_version(), 'author' => $configuration->get_author(), 'url' => $configuration->get_url(), 'email' => $configuration->get_email()];
        if ($this->module_state_service->is_active($configuration->get_id(), $this->context->get_current_shop_id())) {
            $data['activeInShops'] = ['activeInShop' => [Container_Facade::get_parameter('oxid_esales.shop_url')]];
        } else {
            $data['activeInShops'] = ['activeInShop' => []];
        }
        foreach ($this->data_mappers as $data_mapper) {
            $data = array_merge($data, $data_mapper->to_data($configuration));
        }
        return $data;
    }
    private function get_preferred_translation(array $title): string
    {
        if (empty($title)) {
            return '';
        }
        return $title['en'] ?? array_values($title)[0];
    }
}