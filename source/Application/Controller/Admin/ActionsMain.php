<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Actions;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Request;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use stdClass;
/**
 * Admin article main actions manager.
 * There is possibility to change actions description, assign articles to
 * this actions, etc.
 * Admin Menu: Manage Products -> actions -> Main.
 */
class Actions_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if ($this->is_new_edit_object() !== true) {
            $o_action = ox_new(Actions::class);
            $o_action->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_action->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_action->load_in_lang(key($o_other_lang), $sox_id);
            }
            $this->_a_view_data['edit'] = $o_action;
            // remove already created languages
            $a_lang = array_diff(Registry::get_lang()->get_language_names(), $o_other_lang);
            if (count($a_lang)) {
                $this->_a_view_data['posslang'] = $a_lang;
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
        }
        if ($this->get_view_config()->is_alt_image_server_configured()) {
            $this->_a_view_data['imageUrl'] = Container_Facade::get_parameter('oxid_esales.alternative_image_url');
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            // generating category tree for select list
            $this->create_category_tree('artcattree', $sox_id);
            $o_actions_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Actions_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_actions_main_ajax->get_columns();
            return 'popups/actions_main';
        }
        if ($o_promotion = $this->get_view_data_element('edit')) {
            if ($o_promotion->oxactions__oxtype->value == 2 || $o_promotion->oxactions__oxtype->value == 3) {
                if ($i_aoc = Registry::get_request()->get_request_escaped_parameter('oxpromotionaoc')) {
                    $s_popup = false;
                    switch ($i_aoc) {
                        case 'article':
                            // generating category tree for select list
                            $this->create_category_tree('artcattree', $sox_id);
                            if ($o_article = $o_promotion->get_banner_article()) {
                                $this->_a_view_data['actionarticle_artnum'] = $o_article->oxarticles__oxartnum->value;
                                $this->_a_view_data['actionarticle_title'] = $o_article->oxarticles__oxtitle->value;
                            }
                            $s_popup = 'actions_article';
                            break;
                        case 'groups':
                            $s_popup = 'actions_groups';
                            break;
                    }
                    if ($s_popup) {
                        $o_actions_article_ajax = ox_new($s_popup . '_ajax');
                        $this->_a_view_data['oxajax'] = $o_actions_article_ajax->get_columns();
                        return "popups/{$s_popup}";
                    }
                } else if ($o_promotion->oxactions__oxtype->value == 2) {
                    $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 300, $o_promotion, 'oxactions__oxlongdesc', 'details.css');
                }
            }
        }
        return 'actions_main';
    }
    /**
     * Saves Promotions
     */
    public function save(): void
    {
        parent::save();
        $action = ox_new(Actions::class);
        if ($this->is_new_edit_object() !== true) {
            $action->load($this->get_edit_object_id());
        }
        if ($this->check_access_to_edit_action($action) === true) {
            $action->assign($this->get_action_form_data());
            $action->set_language($this->_i_edit_lang);
            $action = Registry::get_utils_file()->process_files($action);
            $action->save();
            $this->set_edit_object_id($action->get_id());
        }
    }
    /**
     * Saves changed selected action parameters in different language.
     */
    public function saveinnlang(): void
    {
        $this->save();
    }
    /**
     * Checks access to edit Action.
     *
     *
     * @return bool
     */
    protected function check_access_to_edit_action(Actions $action)
    {
        return true;
    }
    /**
     * Returns form data for Action.
     */
    private function get_action_form_data(): array
    {
        $request = ox_new(Request::class);
        $form_data = $request->get_request_escaped_parameter('editval');
        return $this->normalize_action_form_data($form_data);
    }
    /**
     * Normalizes form data for Action.
     *
     *
     */
    private function normalize_action_form_data(array $form_data): array
    {
        if ($this->is_new_edit_object() === true) {
            $form_data['oxactions__oxid'] = null;
        }
        if (!isset($form_data['oxactions__oxactive']) || !$form_data['oxactions__oxactive']) {
            $form_data['oxactions__oxactive'] = 0;
        }
        return $form_data;
    }
}