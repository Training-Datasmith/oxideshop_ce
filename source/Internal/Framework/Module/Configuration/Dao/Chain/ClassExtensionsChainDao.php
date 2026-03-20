<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Class_Extensions_Chain;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Event\Module_Class_Extension_Chain_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\Array_Storage_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\File_Storage_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\Filesystem\Path;
class Class_Extensions_Chain_Dao implements Class_Extensions_Chain_Dao_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context, private readonly File_Storage_Factory_Interface $file_storage_factory, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    public function get_chain(int $shop_id): Class_Extensions_Chain
    {
        return new Class_Extensions_Chain($this->storage_exists($shop_id) ? $this->get_storage($shop_id)->get() : []);
    }
    public function save_chain(int $shop_id, Class_Extensions_Chain $chain): void
    {
        $this->get_storage($shop_id)->save($chain->get_chain());
        $this->event_dispatcher->dispatch(new Module_Class_Extension_Chain_Changed_Event());
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
        return Path::join($this->context->get_shop_configuration_directory($shop_id), 'class_extension_chain.yaml');
    }
}