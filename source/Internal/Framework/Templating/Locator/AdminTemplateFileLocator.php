<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Locator;

use Oxid_Esales\Eshop\Core\Config;
/**
 * Class AdminTemplateFileLocator
 * @package OxidEsales\EshopCommunity\Internal\Framework\Templating\Locator
 */
class Admin_Template_File_Locator implements File_Locator_Interface
{
    public function __construct(private readonly Config $context)
    {
    }
    /**
     * Returns a full path for a given file name.
     *
     * @param string $name The file name to locate
     *
     * @return string The full path to the file
     */
    public function locate(string $name): string
    {
        return $this->context->get_template_path($name, true);
    }
}