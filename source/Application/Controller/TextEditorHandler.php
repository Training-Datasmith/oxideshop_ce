<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

/**
 * Responsible for generation of text editor output.
 *
 * Class TextEditorHandler
 */
class Text_Editor_Handler
{
    /**
     * @var string The style sheet for the editor.
     */
    private $stylesheet;
    /**
     * @var bool Information in the text editor is editable by default.
     *           In some cases it should not be etc. when product is derived.
     */
    protected $text_editor_disabled = false;
    /**
     * Render text editor.
     *
     * @param int    $width       The editor width.
     * @param int    $height      The editor height.
     * @param object $objectValue The object value passed to editor.
     * @param string $fieldName   The name of object field which content is passed to editor.
     *
     * @return string The Editor output.
     */
    public function render_text_editor($width, $height, $object_value, $field_name): string
    {
        $s_editor_html = $this->render_rich_text_editor($width, $height, $object_value, $field_name);
        if (!$s_editor_html) {
            return $this->render_plain_text_editor($width, $height, $object_value, $field_name);
        }
        return $s_editor_html;
    }
    /**
     * Returns simple textarea element filled with object text to edit.
     *
     * @param int    $width       The editor width.
     * @param int    $height      The editor height.
     * @param object $objectValue The object value passed to editor.
     * @param string $fieldName   The name of object field which content is passed to editor.
     *
     * @return string The Editor output.
     */
    public function render_plain_text_editor($width, $height, $object_value, $field_name): string
    {
        if (!str_contains($width, '%')) {
            $width .= 'px';
        }
        if (!str_contains($height, '%')) {
            $height .= 'px';
        }
        $disabled_text_editor = $this->is_text_editor_disabled() ? 'disabled ' : '';
        return "<textarea {$disabled_text_editor}id='editor_{$field_name}' name='{$field_name}' " . "style='width:{$width}; height:{$height};'>{$object_value}</textarea>";
    }
    /**
     * Returns the generated output of wysiwyg editor.
     *
     * @param int    $width       The editor width.
     * @param int    $height      The editor height.
     * @param object $objectValue The object value passed to editor.
     * @param string $fieldName   The name of object field which content is passed to editor.
     *
     * @return string The Editor output.
     */
    public function render_rich_text_editor($width, $height, $object_value, $field_name): string
    {
        return '';
    }
    /**
     * Set the style sheet for the editor.
     *
     * @param string $stylesheet The stylesheet for editor.
     */
    public function set_style_sheet($stylesheet): void
    {
        $this->stylesheet = $stylesheet;
    }
    /**
     * Get the style sheet for the editor.
     *
     * @return string The stylesheet for the editor.
     */
    public function get_style_sheet()
    {
        return $this->stylesheet;
    }
    /**
     * Mark text editor disabled: information in it should not be editable.
     */
    public function disable_text_editor(): void
    {
        $this->text_editor_disabled = true;
    }
    /**
     * If information in text editor is not editable.
     *
     * @return bool
     */
    public function is_text_editor_disabled()
    {
        return $this->text_editor_disabled;
    }
}