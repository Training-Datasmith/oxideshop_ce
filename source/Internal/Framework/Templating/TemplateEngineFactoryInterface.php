<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating;

interface Template_Engine_Factory_Interface
{
    public function get_template_engine(): Template_Engine_Interface;
}