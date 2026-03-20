<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * The implementation of this class maps className to classId and vice versa.
 */
interface Class_Name_Resolver_Interface
{
    /**
     * Map argument classId to related className.
     *
     * @param string $classId Class id.
     *
     * @return string|null
     */
    public function get_class_name_by_id($class_id);
    /**
     * Map argument className to related classId.
     *
     * @param string $className Class name.
     *
     * @return string|null
     */
    public function get_id_by_class_name($class_name);
}