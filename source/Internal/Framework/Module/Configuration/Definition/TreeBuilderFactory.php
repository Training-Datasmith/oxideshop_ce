<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Definition;

// phpcs:disable
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration\Class_Extensions_Data_Mapper;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration\Controllers_Data_Mapper;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration\Events_Data_Mapper;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration\Module_Settings_Data_Mapper;
use Symfony\Component\Config\Definition\Builder\Tree_Builder;
use Symfony\Component\Config\Definition\Node_Interface;
// phpcs:enable
class Tree_Builder_Factory implements Tree_Builder_Factory_Interface
{
    public function create(): Node_Interface
    {
        $tree_builder = new Tree_Builder('moduleConfiguration');
        $root_node = $tree_builder->get_root_node();
        $root_node->children()->scalar_node('id')->is_required()->cannot_be_empty()->end()->scalar_node('moduleSource')->is_required()->cannot_be_empty()->end()->scalar_node('version')->end()->scalar_node('activated')->end()->array_node('title')->scalar_prototype()->end()->end()->array_node('description')->scalar_prototype()->end()->end()->scalar_node('lang')->end()->scalar_node('thumbnail')->end()->scalar_node('author')->end()->scalar_node('url')->end()->scalar_node('email')->end()->array_node(Class_Extensions_Data_Mapper::MAPPING_KEY)->normalize_keys(false)->scalar_prototype()->end()->end()->array_node(Controllers_Data_Mapper::MAPPING_KEY)->normalize_keys(false)->scalar_prototype()->end()->end()->array_node(Events_Data_Mapper::MAPPING_KEY)->normalize_keys(false)->scalar_prototype()->end()->end()->array_node(Module_Settings_Data_Mapper::MAPPING_KEY)->normalize_keys(false)->array_prototype()->children()->scalar_node('group')->end()->scalar_node('name')->end()->scalar_node('type')->end()->variable_node('value')->end()->scalar_node('position')->end()->array_node('constraints')->scalar_prototype()->end()->end()->end()->end()->end()->end();
        return $tree_builder->build_tree();
    }
}