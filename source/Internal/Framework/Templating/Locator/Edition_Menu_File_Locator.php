<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Locator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Edition\Edition;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Bridge\Admin_Theme_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Edition_Menu_File_Locator implements Navigation_File_Locator_Interface
{
    private readonly string $theme_name;
    private string $file_name = 'menu.xml';
    public function __construct(Admin_Theme_Bridge_Interface $admin_theme_bridge, private readonly Basic_Context_Interface $context, private readonly Filesystem $file_system)
    {
        $this->theme_name = $admin_theme_bridge->get_active_theme();
    }
    public function locate(): array
    {
        $path = $this->context->get_edition() === Edition::Community ? $this->context->get_source_path() : $this->context->get_edition_source_path($this->context->get_edition());
        $file_path = Path::join($path, 'Application', 'views', $this->theme_name, $this->file_name);
        return $this->file_system->exists($file_path) ? [$file_path] : [];
    }
}