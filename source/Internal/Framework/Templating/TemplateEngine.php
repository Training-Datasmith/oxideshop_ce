<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating;

class Template_Engine implements Template_Engine_Interface
{
    private array $globals = [];
    /**
     * @param mixed  $value
     */
    public function add_global(string $name, $value): void
    {
        $this->globals[$name] = $value;
    }
    public function get_globals(): array
    {
        return $this->globals;
    }
    /**
     * Renders a template.
     *
     * @param string $name    A template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The evaluated template as a string
     */
    public function render(string $name, array $context = []): string
    {
        return $name;
    }
    /**
     * Renders a fragment of the template.
     *
     * @param string $fragment   The template fragment to render
     * @param string $fragmentId The Id of the fragment
     * @param array  $context    An array of parameters to pass to the template
     */
    public function render_fragment(string $fragment, string $fragment_id, array $context = []): string
    {
        return $fragment;
    }
    /**
     * Returns true if the template exists.
     *
     * @param string $name A template name
     *
     * @return bool true if the template exists, false otherwise
     */
    public function exists(string $name): bool
    {
        return true;
    }
}