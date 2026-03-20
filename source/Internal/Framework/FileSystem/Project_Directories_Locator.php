<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System;

use Symfony\Component\Filesystem\Path;
readonly class Project_Directories_Locator
{
    private string $project_root;
    public function __construct()
    {
        $this->project_root = (new Project_Root_Locator())->get_project_root();
    }
    public function get_root_path(): string
    {
        return $this->project_root;
    }
    public function get_source_path(): string
    {
        return Path::join($this->project_root, 'source');
    }
    public function get_vendor_path(): string
    {
        return Path::join($this->project_root, 'vendor');
    }
    public function get_out_path(): string
    {
        return Path::join($this->get_source_path(), 'out');
    }
}