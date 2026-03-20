<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Templating;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
class Template_Renderer implements Template_Renderer_Interface
{
    public function __construct(private readonly Template_Engine_Interface $template_engine, private readonly Context_Interface $context, private readonly string $filename_extension)
    {
    }
    public function render_template(string $template, array $context = []): string
    {
        return $this->get_template_engine()->render($this->append_default_filename_extension($template), $context);
    }
    public function render_fragment(string $fragment, string $fragment_id, array $context = []): string
    {
        if ($this->do_not_render_for_demo_shop()) {
            return $fragment;
        }
        return $this->get_template_engine()->render_fragment($fragment, $fragment_id, $context);
    }
    public function get_template_engine(): Template_Engine_Interface
    {
        return $this->template_engine;
    }
    public function exists(string $name): bool
    {
        return $this->get_template_engine()->exists($this->append_default_filename_extension($name));
    }
    private function do_not_render_for_demo_shop(): bool
    {
        return $this->context->is_shop_in_demo_mode();
    }
    private function append_default_filename_extension(string $template_name): string
    {
        return str_ends_with($template_name, $this->filename_extension) ? $template_name : "{$template_name}.{$this->filename_extension}";
    }
}