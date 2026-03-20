<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Base seo config class.
 */
class Object_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Executes parent method parent::render(),
     * and returns name of template file
     * "object_main".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        if ($s_type = $this->get_type()) {
            $o_object = ox_new($s_type);
            if ($o_object->load($this->get_edit_object_id())) {
                $o_other_lang = $o_object->get_available_in_langs();
                $language_id = $this->get_documentation_language_id();
                if (!isset($o_other_lang[$language_id])) {
                    $o_object->load_in_lang(key($o_other_lang), $this->get_edit_object_id());
                }
                $this->_a_view_data['edit'] = $o_object;
            }
            if ($o_object->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        $i_lang = $this->get_edit_lang();
        $a_langs = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names();
        foreach ($a_langs as $s_lang_id => $s_language) {
            $o_lang = new stdClass();
            $o_lang->s_lang_desc = $s_language;
            $o_lang->selected = $s_lang_id == $i_lang;
            $this->_a_view_data['otherlang'][$s_lang_id] = clone $o_lang;
        }
        return 'object_seo';
    }
    /**
     * Saves selection list parameters changes.
     */
    public function save(): void
    {
        // saving/updating seo params
        if ($s_oxid = $this->get_save_object_id()) {
            $a_seo_data = Registry::get_request()->get_request_escaped_parameter('aSeoData');
            $i_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
            $i_lang = $this->get_edit_lang();
            // checkbox handling
            if (!isset($a_seo_data['oxfixed'])) {
                $a_seo_data['oxfixed'] = 0;
            }
            $s_params = $this->get_additional_params_from_seo_data($a_seo_data);
            $o_encoder = $this->get_encoder();
            // marking self and page links as expired
            $o_encoder->mark_as_expired($s_oxid, $i_shop_id, 1, $i_lang, $s_params);
            // saving
            $o_encoder->add_seo_entry($s_oxid, $i_shop_id, $i_lang, $this->get_std_url($s_oxid), $a_seo_data['oxseourl'], $this->get_seo_entry_type(), $a_seo_data['oxfixed'], trim((string) $a_seo_data['oxkeywords']), trim((string) $a_seo_data['oxdescription']), $this->process_param($a_seo_data['oxparams']), true, $this->get_alt_seo_entry_id());
        }
    }
    /**
     * Gets additional params from aSeoData['oxparams'] if it is set.
     *
     * @param array $aSeoData Seo data array
     *
     * @return null|string
     */
    protected function get_additional_params_from_seo_data($a_seo_data)
    {
        $s_params = null;
        if (isset($a_seo_data['oxparams'])) {
            if (preg_match('/([a-z]*#)?(?<objectseo>[a-z0-9]+)(#[0-9])?/i', $a_seo_data['oxparams'], $a_matches)) {
                $s_quoted_object_seo_id = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($a_matches['objectseo']);
                $s_params = "oxparams = {$s_quoted_object_seo_id}";
            }
        }
        return $s_params;
    }
    /**
     * Returns id of object which must be saved
     *
     * @return string
     */
    protected function get_save_object_id()
    {
        return $this->get_edit_object_id();
    }
    /**
     * Returns object seo data
     *
     * @param string $sMetaType meta data type (oxkeywords/oxdescription)
     *
     * @return string
     */
    public function get_entry_meta_data($s_meta_type)
    {
        return $this->get_encoder()->get_meta_data($this->get_edit_object_id(), $s_meta_type, \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id(), $this->get_edit_lang());
    }
    /**
     * Returns TRUE if current seo entry has fixed state
     *
     * @return bool
     */
    public function is_entry_fixed()
    {
        $i_lang = (int) $this->get_edit_lang();
        $i_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $s_q = "select oxfixed from oxseo where\n                   oxseo.oxobjectid = :oxobjectid and\n                   oxseo.oxshopid = :oxshopid and oxseo.oxlang = :oxlang and oxparams = '' ";
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        return (bool) \Oxid_Esales\Eshop\Core\Database_Provider::get_master()->get_one($s_q, ['oxobjectid' => $this->get_edit_object_id(), 'oxshopid' => $i_shop_id, 'oxlang' => $i_lang]);
    }
    /**
     * Returns url type
     */
    protected function get_type()
    {
    }
    /**
     * Returns objects std url
     *
     * @param string $sOxid object id
     *
     * @return string
     */
    protected function get_std_url($s_oxid)
    {
        if ($s_type = $this->get_type()) {
            $o_object = ox_new($s_type);
            if ($o_object->load($s_oxid)) {
                return $o_object->get_base_std_link($this->get_edit_lang(), true, false);
            }
        }
    }
    /**
     * Returns edit language id
     *
     * @return int
     */
    public function get_edit_lang()
    {
        return $this->_i_edit_lang;
    }
    /**
     * Returns alternative seo entry id
     */
    protected function get_alt_seo_entry_id()
    {
    }
    /**
     * Returns seo entry type
     *
     * @return string
     */
    protected function get_seo_entry_type()
    {
        return $this->get_type();
    }
    /**
     * Processes parameter before writing to db
     *
     * @param string $sParam parameter to process
     *
     * @return string
     */
    public function process_param($s_param)
    {
        return $s_param;
    }
    /**
     * Returns current object type seo encoder object
     */
    protected function get_encoder()
    {
    }
    /**
     * Returns seo uri
     */
    public function get_entry_uri()
    {
    }
    /**
     * Returns true if SEO object id has suffix enabled. Default is FALSE
     *
     * @return bool
     */
    public function is_entry_suffixed()
    {
        return false;
    }
    /**
     * Returns TRUE if seo object supports suffixes. Default is FALSE
     *
     * @return bool
     */
    public function is_suffix_supported()
    {
        return false;
    }
    /**
     * Returns FALSE, as this view does not support category selector
     *
     * @return bool
     */
    public function show_cat_select()
    {
        return false;
    }
    /**
     * Returns FALSE, as this view does not support active selection type
     *
     * @return bool
     */
    public function get_act_cat_type()
    {
        return false;
    }
}