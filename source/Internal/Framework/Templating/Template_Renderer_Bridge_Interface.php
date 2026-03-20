<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating;

interface Template_Renderer_Bridge_Interface
{
    public function get_template_renderer(): Template_Renderer_Interface;
    /**
     * @deprecated since 7.0.0 will be removed in next major
     */
    public function set_engine($engine);
    /**
     * @deprecated since 7.0.0 will be removed in next major
     */
    public function get_engine();
}