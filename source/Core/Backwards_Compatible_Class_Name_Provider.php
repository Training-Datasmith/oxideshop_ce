<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Forms real class name for edition based classes.
 *
 * @internal Do not make a module extension for this class.
 */
class Backwards_Compatible_Class_Name_Provider
{
    /**
     * @param array $classMap
     */
    public function __construct(private $class_map)
    {
    }
    /**
     * Returns real class name from given alias. If class alias is not found,
     * given class alias is thought to be a real class and is returned.
     *
     * @param string $classAlias
     *
     * @return mixed
     */
    public function get_class_name($class_alias)
    {
        if (array_key_exists($class_alias, $this->class_map)) {
            return $this->class_map[$class_alias];
        }
        return $class_alias;
    }
    /**
     * Method returns class alias by given class name.
     *
     * @param string $className with namespace.
     *
     * @return string|null
     */
    public function get_class_alias_name($class_name): int|string|null
    {
        /*
         * Sanitize input: class names in namespaces should not, but may include a leading backslash
         */
        $class_name = ltrim($class_name, '\\');
        $class_alias = array_search($class_name, $this->class_map);
        if ($class_alias === false) {
            return null;
        }
        return $class_alias;
    }
}