<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
class Module_Configuration_Data_Mapper implements Module_Configuration_Data_Mapper_Interface
{
    /** @var ModuleConfigurationDataMapperInterface[] */
    private readonly array $data_mappers;
    public function __construct(Module_Configuration_Data_Mapper_Interface ...$data_mappers)
    {
        $this->data_mappers = $data_mappers;
    }
    public function to_data(Module_Configuration $configuration): array
    {
        $data = ['id' => $configuration->get_id(), 'moduleSource' => $configuration->get_module_source(), 'version' => $configuration->get_version(), 'activated' => $configuration->is_activated(), 'title' => $configuration->get_title(), 'description' => $configuration->get_description(), 'lang' => $configuration->get_lang(), 'thumbnail' => $configuration->get_thumbnail(), 'author' => $configuration->get_author(), 'url' => $configuration->get_url(), 'email' => $configuration->get_email()];
        foreach ($this->data_mappers as $data_mapper) {
            $data = array_merge($data, $data_mapper->to_data($configuration));
        }
        return $data;
    }
    public function from_data(Module_Configuration $module_configuration, array $data): Module_Configuration
    {
        $module_configuration->set_id($data['id'])->set_module_source($data['moduleSource'])->set_version($data['version'])->set_activated($data['activated'])->set_title($data['title']);
        if (isset($data['description'])) {
            $module_configuration->set_description($data['description']);
        }
        if (isset($data['lang'])) {
            $module_configuration->set_lang($data['lang']);
        }
        if (isset($data['thumbnail'])) {
            $module_configuration->set_thumbnail($data['thumbnail']);
        }
        if (isset($data['author'])) {
            $module_configuration->set_author($data['author']);
        }
        if (isset($data['url'])) {
            $module_configuration->set_url($data['url']);
        }
        if (isset($data['email'])) {
            $module_configuration->set_email($data['email']);
        }
        foreach ($this->data_mappers as $data_mapper) {
            $module_configuration = $data_mapper->from_data($module_configuration, $data);
        }
        return $module_configuration;
    }
}