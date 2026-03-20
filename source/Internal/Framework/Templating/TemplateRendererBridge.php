<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating;

class Template_Renderer_Bridge implements Template_Renderer_Bridge_Interface
{
    public function __construct(private readonly Template_Renderer_Interface $renderer)
    {
    }
    public function get_template_renderer(): Template_Renderer_Interface
    {
        return $this->renderer;
    }
    public function set_engine($engine)
    {
    }
    public function get_engine()
    {
    }
}