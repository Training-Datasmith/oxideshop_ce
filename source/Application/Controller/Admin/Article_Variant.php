<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service\Product_Variant_Media_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use stdClass;
/**
 * Admin article variants manager.
 * Collects and updates article variants data.
 * Admin Menu: Manage Products -> Articles -> Variants.
 */
class Article_Variant extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Variant parent product object
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_product_parent;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_sl_view_name = $table_view_name_generator->get_view_name('oxselectlist');
        // all selectlists
        $o_all_sel = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_all_sel->init('oxselectlist');
        $s_q = "select * from {$s_sl_view_name}";
        $o_all_sel->select_string($s_q);
        $this->_a_view_data['allsel'] = $o_all_sel;
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $this->_a_view_data['edit'] = $o_article;
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_article->load_in_lang($this->_i_edit_lang, $sox_id);
            if ($o_article->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            $_POST['language'] = $_GET['language'] = $this->_i_edit_lang;
            $o_variants = $o_article->get_admin_variants($this->_i_edit_lang);
            $this->_a_view_data['mylist'] = $o_variants;
            // load object in other languages
            $o_other_lang = $o_article->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_article->load_in_lang(key($o_other_lang), $sox_id);
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $o_lang;
            }
            if ($o_article->oxarticles__oxparentid->value) {
                $this->_a_view_data['parentarticle'] = $this->get_product_parent($o_article->oxarticles__oxparentid->value);
                $this->_a_view_data['oxparentid'] = $o_article->oxarticles__oxparentid->value;
                $this->_a_view_data['issubvariant'] = 1;
                // A. disable variant information editing for variant
                $this->_a_view_data['readonly'] = 1;
            }
            $this->_a_view_data['editlanguage'] = $this->_i_edit_lang;
            $a_lang = array_diff(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_names(), $o_other_lang);
            if (count($a_lang)) {
                $this->_a_view_data['posslang'] = $a_lang;
            }
            foreach ($o_other_lang as $id => $language) {
                $o_lang = new stdClass();
                $o_lang->s_lang_desc = $language;
                $o_lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = $o_lang;
            }
        }
        return 'article_variant';
    }
    /**
     * Saves article variant.
     *
     * @param string $sOXID   Object ID
     * @param array  $aParams Parameters
     */
    public function savevariant($s_oxid = null, $a_params = null): void
    {
        if (!isset($s_oxid) && !isset($a_params)) {
            $s_oxid = Registry::get_request()->get_request_escaped_parameter('voxid');
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        }
        // varianthandling
        $soxparent_id = $this->get_edit_object_id();
        if (isset($soxparent_id) && $soxparent_id && $soxparent_id != '-1') {
            $a_params['oxarticles__oxparentid'] = $soxparent_id;
        } else {
            unset($a_params['oxarticles__oxparentid']);
        }
        /** @var \OxidEsales\Eshop\Application\Model\Article $oArticle */
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($s_oxid != '-1') {
            $o_article->load_in_lang($this->_i_edit_lang, $s_oxid);
        }
        // checkbox handling
        if (is_array($a_params) && !isset($a_params['oxarticles__oxactive'])) {
            $a_params['oxarticles__oxactive'] = 0;
        }
        if (!$this->is_anything_changed($o_article, $a_params)) {
            return;
        }
        $o_article->set_language(0);
        $o_article->assign($a_params);
        $o_article->set_language($this->_i_edit_lang);
        // #0004473
        $o_article->reset_remind_status();
        $is_new_variant = $s_oxid === '-1';
        if ($is_new_variant) {
            if ($o_parent = $this->get_product_parent($o_article->oxarticles__oxparentid->value)) {
                // assign field from parent for new variant
                // #4406
                $o_article->oxarticles__oxisconfigurable = new \Oxid_Esales\Eshop\Core\Field($o_parent->oxarticles__oxisconfigurable->value);
                $o_article->oxarticles__oxremindactive = new \Oxid_Esales\Eshop\Core\Field($o_parent->oxarticles__oxremindactive->value);
            }
        }
        $o_article->save();
        if ($is_new_variant && $o_article->oxarticles__oxparentid->value) {
            Container_Facade::get(Product_Variant_Media_Service_Interface::class)->assign_from_parent_to_variant(Id::from_string($o_article->oxarticles__oxparentid->value), Id::from_string($o_article->get_id()));
        }
    }
    /**
     * Checks if anything is changed in given data compared with existing product values.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oProduct Product to be checked.
     * @param array                                       $aData    Data provided for check.
     *
     * @return bool
     */
    protected function is_anything_changed($o_product, $a_data)
    {
        if (!is_array($a_data)) {
            return true;
        }
        foreach ($a_data as $s_key => $s_value) {
            if (isset($o_product->{$s_key}) && $o_product->{$s_key}->value != $a_data[$s_key]) {
                return true;
            }
        }
        return false;
    }
    /**
     * Returns variant parent object
     *
     * @param string $sParentId parent product id
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function get_product_parent($s_parent_id)
    {
        if ($this->_o_product_parent === null || $this->_o_product_parent !== false && $this->_o_product_parent->get_id() != $s_parent_id) {
            $this->_o_product_parent = false;
            $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($o_product->load($s_parent_id)) {
                $this->_o_product_parent = $o_product;
            }
        }
        return $this->_o_product_parent;
    }
    /**
     * Saves all article variants at once.
     */
    public function savevariants(): void
    {
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (is_array($a_params)) {
            foreach ($a_params as $sox_id => $a_var_params) {
                $this->savevariant($sox_id, $a_var_params);
            }
        }
        $this->reset_content_cache();
    }
    /**
     * Deletes article variant.
     */
    public function delete_variant(): void
    {
        $edit_object_oxid = $this->get_edit_object_id();
        $edit_object = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $edit_object->load($edit_object_oxid);
        if ($edit_object->is_derived()) {
            return;
        }
        $this->reset_content_cache();
        $variant_oxid = Registry::get_request()->get_request_parameter('voxid');
        $variant = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $variant->delete($variant_oxid);
    }
    /**
     * Changes name of variant.
     */
    public function changename(): void
    {
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $this->reset_content_cache();
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($sox_id != '-1') {
            $o_article->load_in_lang($this->_i_edit_lang, $sox_id);
        }
        $o_article->set_language(0);
        $o_article->assign($a_params);
        $o_article->set_language($this->_i_edit_lang);
        $o_article->save();
    }
    /**
     * Add selection list
     */
    public function addsel(): void
    {
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($o_article->load($this->get_edit_object_id())) {
            //Disable editing for derived articles
            if ($o_article->is_derived()) {
                return;
            }
            $this->reset_content_cache();
            if ($a_sels = Registry::get_request()->get_request_escaped_parameter('allsel')) {
                $o_variant_handler = ox_new(\Oxid_Esales\Eshop\Application\Model\Variant_Handler::class);
                $o_variant_handler->gen_variant_from_sell($a_sels, $o_article);
            }
        }
    }
}