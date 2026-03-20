<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Template_Extension_Chain;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\Array_Storage_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\File_Storage_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
class Template_Extension_Chain_Dao implements Template_Extension_Chain_Dao_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context, private readonly File_Storage_Factory_Interface $file_storage_factory)
    {
    }
    public function get_chain(int $shop_id): Module_Template_Extension_Chain
    {
        return new Module_Template_Extension_Chain($this->storage_exists($shop_id) ? $this->get_storage($shop_id)->get() : []);
    }
    private function storage_exists(int $shop_id): bool
    {
        return file_exists($this->get_storage_file_path($shop_id));
    }
    private function get_storage(int $shop_id): Array_Storage_Interface
    {
        return $this->file_storage_factory->create($this->get_storage_file_path($shop_id));
    }
    private function get_storage_file_path(int $shop_id): string
    {
        return Path::join($this->context->get_shop_configuration_directory($shop_id), 'template_extension_chain.yaml');
    }
}