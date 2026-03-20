<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating;

interface Template_Renderer_Interface
{
    /**
     * @param string $template The template name
     * @param array  $context  An array of parameters to pass to the template
     */
    public function render_template(string $template, array $context = []): string;
    /**
     * Renders a fragment of the template.
     *
     * @param string $fragment The template fragment to render
     * @param string $fragmentId The id of the fragment
     * @param array  $context    An array of parameters to pass to the template
     */
    public function render_fragment(string $fragment, string $fragment_id, array $context = []): string;
    public function get_template_engine(): Template_Engine_Interface;
    /**
     * Returns true if the template exists.
     *
     * @param string $name A template name
     *
     * @return bool true if the template exists, false otherwise
     */
    public function exists(string $name): bool;
}