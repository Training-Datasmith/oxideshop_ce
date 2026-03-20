<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

use ArrayIterator;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Class_Extension;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Extension_Not_In_Chain_Exception;
use Traversable;
class Class_Extensions_Chain implements \IteratorAggregate
{
    public const NAME = 'classExtensions';
    public function __construct(private array $chain = [])
    {
    }
    public function get_name(): string
    {
        return self::NAME;
    }
    public function get_chain(): array
    {
        return $this->chain;
    }
    public function set_chain(array $chain): Class_Extensions_Chain
    {
        $this->chain = $chain;
        return $this;
    }
    /**
     * @param ClassExtension[] $extensions
     */
    public function add_extensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->add_extension($extension);
        }
    }
    /**
     * @throws ExtensionNotInChainException
     */
    public function remove_extension(Class_Extension $class_extension): void
    {
        $extended = $class_extension->get_shop_class_name();
        $extension = $class_extension->get_module_extension_class_name();
        if (false === \array_key_exists($extended, $this->chain) || false === \array_search($extension, $this->chain[$extended], true)) {
            throw new Extension_Not_In_Chain_Exception('There is no class ' . $extended . ' extended by class ' . $extension . ' in the current chain');
        }
        $result_offset = \array_search($extension, $this->chain[$extended], true);
        unset($this->chain[$extended][$result_offset]);
        $this->chain[$extended] = \array_values($this->chain[$extended]);
        if (empty($this->chain[$extended])) {
            unset($this->chain[$extended]);
        }
    }
    public function add_extension(Class_Extension $extension): void
    {
        if (\array_key_exists($extension->get_shop_class_name(), $this->chain)) {
            if (!$this->is_module_extension_class_name_in_chain($extension)) {
                array_push($this->chain[$extension->get_shop_class_name()], $extension->get_module_extension_class_name());
            }
        } else {
            $this->chain[$extension->get_shop_class_name()] = [$extension->get_module_extension_class_name()];
        }
    }
    private function is_module_extension_class_name_in_chain(Class_Extension $extension): bool
    {
        if (\in_array($extension->get_module_extension_class_name(), $this->chain[$extension->get_shop_class_name()])) {
            return true;
        }
        return false;
    }
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->chain);
    }
}