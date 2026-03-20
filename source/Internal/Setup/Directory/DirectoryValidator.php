<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Directory;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use Symfony\Component\Filesystem\Path;
class Directory_Validator implements Directory_Validator_Interface
{
    private const DIRECTORIES_LIST = ['out/pictures/promo/', 'out/pictures/master/', 'out/pictures/generated/', 'out/pictures/media/', 'out/media/', 'log/', '../var/'];
    public function __construct(private readonly Basic_Context_Interface $basic_context)
    {
    }
    /**
     * @throws NoPermissionDirectoryException
     * @throws NonExistenceDirectoryException
     */
    public function validate_directory(string $compile_directory): void
    {
        $directories = $this->get_directories($compile_directory);
        $this->check_directories_existent($directories);
        $this->check_directories_permission($directories);
    }
    /**
     * @throws NotAbsolutePathException
     */
    public function check_path_is_absolute(string $compile_directory): void
    {
        if (!Path::is_absolute($compile_directory)) {
            throw new Not_Absolute_Path_Exception(Not_Absolute_Path_Exception::NOT_ABSOLUTE_PATHS);
        }
    }
    /**
     * @throws NonExistenceDirectoryException
     */
    private function check_directories_existent(array $directories): void
    {
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                throw new Non_Existence_Directory_Exception(Non_Existence_Directory_Exception::NON_EXISTENCE_DIRECTORY . ': ' . $directory);
            }
        }
    }
    /**
     * @throws NoPermissionDirectoryException
     */
    private function check_directories_permission(array $directories): void
    {
        $sub_directories = $this->get_directories_and_sub_directories($directories);
        foreach ($sub_directories as $sub_directory) {
            if (!is_readable($sub_directory) || !is_writable($sub_directory)) {
                throw new No_Permission_Directory_Exception(No_Permission_Directory_Exception::NO_PERMISSION_DIRECTORY . ': ' . $sub_directory);
            }
        }
    }
    private function get_directories_and_sub_directories(array $directories): array
    {
        foreach ($directories as $directory) {
            $recursive_iterator = new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($directory, Recursive_Directory_Iterator::SKIP_DOTS), Recursive_Iterator_Iterator::SELF_FIRST, Recursive_Iterator_Iterator::CATCH_GET_CHILD);
            foreach ($recursive_iterator as $path => $dir) {
                if ($dir->is_dir()) {
                    $directories[] = rtrim((string) $path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                }
            }
        }
        return $directories;
    }
    private function get_directories(string $compile_directory): array
    {
        $shop_source_path = rtrim($this->basic_context->get_source_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $directories = array_map(static fn(string $value): string => $shop_source_path . $value, self::DIRECTORIES_LIST);
        $directories[] = rtrim($compile_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $directories;
    }
}