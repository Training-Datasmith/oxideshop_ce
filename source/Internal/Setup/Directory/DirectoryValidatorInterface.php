<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Directory;

interface Directory_Validator_Interface
{
    /**
     * @throws NoPermissionDirectoryException
     * @throws NonExistenceDirectoryException
     */
    public function validate_directory(string $compile_directory): void;
    /**
     * @throws NotAbsolutePathException
     */
    public function check_path_is_absolute(string $compile_directory): void;
}