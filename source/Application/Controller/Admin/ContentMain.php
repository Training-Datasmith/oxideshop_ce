<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Content;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Html\Html_Sanitizer_Interface;
use stdClass;
/**
 * Admin content manager.
 * There is possibility to change content description, enter page text etc.
 * Admin Menu: Customerinformations -> Content.
 */
class Content_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $my_config = Registry::get_config();
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        // categorie tree
        $o_cat_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
        $o_cat_tree->load_list();
        $o_content = ox_new(Content::class);
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_content->load_in_lang($this->_i_edit_lang, $sox_id);
            $o_other_lang = $o_content->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_content->load_in_lang(key($o_other_lang), $sox_id);
            }
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
            // mark selected
            if ($o_content->oxcontents__oxcatid->value && isset($o_cat_tree[$o_content->oxcontents__oxcatid->value])) {
                $o_cat_tree[$o_content->oxcontents__oxcatid->value]->selected = 1;
            }
        } else {
            // create ident to make life easier
            $s_u_id = Registry::get_utils_object()->generate_u_id();
            $o_content->oxcontents__oxloadid = new \Oxid_Esales\Eshop\Core\Field($s_u_id);
        }
        $this->_a_view_data['edit'] = $o_content;
        $this->_a_view_data['link'] = '[{ oxgetseourl ident=&quot;' . $o_content->oxcontents__oxloadid->value . '&quot; type=&quot;oxcontent&quot; }]';
        $this->_a_view_data['cattree'] = $o_cat_tree;
        // generate editor
        $s_css = 'content.css';
        if ($o_content->oxcontents__oxsnippet->value == '1') {
            $s_css = null;
        }
        $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 300, $o_content, 'oxcontents__oxcontent', $s_css);
        $this->_a_view_data['afolder'] = $my_config->get_config_param('aCMSfolder');
        $this->_a_view_data['activeSanitizer'] = Container_Facade::get_parameter('oxid_esales.html_sanitizer_enabled');
        return 'content_main';
    }
    public function save(): void
    {
        parent::save();
        $content_id = $this->get_edit_object_id();
        $request_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (isset($request_params['oxcontents__oxloadid'])) {
            $request_params['oxcontents__oxloadid'] = $this->prepare_ident($request_params['oxcontents__oxloadid']);
        }
        if ($this->check_ident($request_params['oxcontents__oxloadid'], $content_id)) {
            $this->_a_view_data['blLoadError'] = true;
            $this->handle_save_error($content_id, $request_params);
            return;
        }
        if ($request_params['oxcontents__oxtype'] == 0) {
            $request_params['oxcontents__oxsnippet'] = 1;
        } else {
            $request_params['oxcontents__oxsnippet'] = 0;
        }
        if ($request_params['oxcontents__oxfolder'] === 'CMSFOLDER_NONE') {
            $request_params['oxcontents__oxfolder'] = '';
        }
        $this->prepare_and_save_content($request_params, $content_id, $this->_i_edit_lang);
    }
    public function saveinnlang(): void
    {
        parent::save();
        $content_id = $this->get_edit_object_id();
        $request_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (isset($request_params['oxcontents__oxloadid'])) {
            $request_params['oxcontents__oxloadid'] = $this->prepare_ident($request_params['oxcontents__oxloadid']);
        }
        if ($this->check_ident($request_params['oxcontents__oxloadid'], $content_id)) {
            $this->_a_view_data['blLoadError'] = true;
            $this->handle_save_error($content_id, $request_params);
            return;
        }
        $this->prepare_and_save_content($request_params, $content_id, Registry::get_request()->get_request_escaped_parameter('new_lang'));
    }
    /**
     * Prepares ident (removes bad chars, leaves only thoose that fits in a-zA-Z0-9_ range)
     *
     * @param string $sIdent ident to filter
     *
     * @return string
     */
    protected function prepare_ident($s_ident)
    {
        if ($s_ident) {
            return Str::get_str()->preg_replace('/[^a-zA-Z0-9_]*/', '', $s_ident);
        }
    }
    /**
     * Check if ident is unique
     *
     * @param string $sIdent ident
     * @param string $sOxId  Object id
     */
    protected function check_ident($s_ident, $s_ox_id)
    {
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = Database_Provider::get_master();
        $bl_allow = false;
        // null not allowed
        if (!strlen($s_ident)) {
            $bl_allow = true;
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        } elseif ($master_db->get_one('select oxid from oxcontents where oxloadid = :oxloadid and oxid != :oxid and oxshopid = :oxshopid', ['oxloadid' => $s_ident, 'oxid' => $s_ox_id, 'oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()])) {
            $bl_allow = true;
        }
        return $bl_allow;
    }
    private function prepare_and_save_content(array $request_params, $content_id, $lang): void
    {
        if (isset($request_params['oxcontents__oxcontent'])) {
            $request_params['oxcontents__oxcontent'] = Container_Facade::get(Html_Sanitizer_Interface::class)->sanitize($request_params['oxcontents__oxcontent']);
        }
        if (!isset($request_params['oxcontents__oxactive'])) {
            $request_params['oxcontents__oxactive'] = 0;
        }
        $content = ox_new(Content::class);
        if ($content_id != '-1') {
            $content->load_in_lang($lang, $content_id);
        } else {
            $request_params['oxcontents__oxid'] = null;
        }
        $content->set_language(0);
        $content->assign($request_params);
        $content->set_language($lang);
        $content->save();
        $this->set_edit_object_id($content->get_id());
    }
    private function handle_save_error($content_id, $request_params): void
    {
        $content = ox_new(Content::class);
        if ($content_id != '-1') {
            $content->load($content_id);
        }
        $content->assign($request_params);
        $this->_a_view_data['edit'] = $content;
    }
}