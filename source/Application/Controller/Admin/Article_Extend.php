<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Admin article extended parameters manager.
 * Collects and updates (on user submit) extended article properties ( such as
 * weight, dimensions, purchase Price and etc.). There is ability to assign article
 * to any chosen article group.
 * Admin Menu: Manage Products -> Articles -> Extended.
 */
class Article_Extend extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Unit array
     *
     * @var array
     */
    protected $_a_units_array;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $ox_id = $this->get_edit_object_id();
        $this->create_category_tree('artcattree');
        // all categories
        if (isset($ox_id) && $ox_id != '-1') {
            // load object
            $article->load_in_lang($this->_i_edit_lang, $ox_id);
            $article = $this->update_article($article);
            // load object in other languages
            $other_lang = $article->get_available_in_langs();
            if (!isset($other_lang[$this->_i_edit_lang])) {
                $article->load_in_lang(key($other_lang), $ox_id);
            }
            foreach ($other_lang as $id => $language) {
                $lang = new stdClass();
                $lang->s_lang_desc = $language;
                $lang->selected = $id == $this->_i_edit_lang;
                $this->_a_view_data['otherlang'][$id] = clone $lang;
            }
            // variant handling
            if ($article->oxarticles__oxparentid->value) {
                $parent_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                $parent_article->load($article->oxarticles__oxparentid->value);
                $this->_a_view_data['parentarticle'] = $parent_article;
                $this->_a_view_data['oxparentid'] = $article->oxarticles__oxparentid->value;
            }
        }
        $this->prepare_bundled_articles_data_for_view($article);
        $i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
        if ($i_aoc == 1) {
            $o_article_extend_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Article_Extend_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_article_extend_ajax->get_columns();
            return 'popups/article_extend';
        }
        if ($i_aoc == 2) {
            $o_article_bundle_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Article_Bundle_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_article_bundle_ajax->get_columns();
            return 'popups/article_bundle';
        }
        //load media files
        $this->_a_view_data['aMediaUrls'] = $article->get_media_urls();
        return 'article_extend';
    }
    /**
     * Saves modified extended article parameters.
     *
     * @return mixed
     */
    public function save()
    {
        parent::save();
        $a_my_file = Registry::get_config()->get_uploaded_file('myfile');
        $a_media_file = Registry::get_config()->get_uploaded_file('mediaFile');
        if (is_array($a_my_file['name']) && reset($a_my_file['name']) || $a_media_file['name']) {
            $my_config = Registry::get_config();
            if ($my_config->is_demo_shop()) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
                $o_ex->set_message('ARTICLE_EXTEND_UPLOADISDISABLED');
                Registry::get_utils_view()->add_error_to_display($o_ex, false);
                return;
            }
        }
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // checkbox handling
        if (!isset($a_params['oxarticles__oxissearch'])) {
            $a_params['oxarticles__oxissearch'] = 0;
        }
        if (!isset($a_params['oxarticles__oxblfixedprice'])) {
            $a_params['oxarticles__oxblfixedprice'] = 0;
        }
        // new way of handling bundled articles
        //#1517C - remove possibility to add Bundled Product
        //$this->setBundleId($aParams, $soxId);
        // default values
        $a_params = $this->add_default_values($a_params);
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->load_in_lang($this->_i_edit_lang, $sox_id);
        $s_t_price_field = 'oxarticles__oxtprice';
        $s_price_field = 'oxarticles__oxprice';
        $d_t_price = $a_params['oxarticles__oxtprice'];
        if ($d_t_price && $d_t_price != $o_article->{$s_t_price_field}->value && $d_t_price <= $o_article->{$s_price_field}->value) {
            $this->_a_view_data['errorsavingtprice'] = 1;
        }
        $o_article->set_language(0);
        $o_article->assign($a_params);
        $o_article->set_language($this->_i_edit_lang);
        $o_article = Registry::get_utils_file()->process_files($o_article);
        $o_article->save();
        //saving media file
        $s_media_url = Registry::get_request()->get_request_escaped_parameter('mediaUrl');
        $s_media_desc = Registry::get_request()->get_request_escaped_parameter('mediaDesc');
        if ($s_media_url && $s_media_url != 'http://' || $a_media_file['name'] || $s_media_desc) {
            if (!$s_media_desc) {
                return Registry::get_utils_view()->add_error_to_display('EXCEPTION_NODESCRIPTIONADDED');
            }
            if ((!$s_media_url || $s_media_url == 'http://') && !$a_media_file['name']) {
                return Registry::get_utils_view()->add_error_to_display('EXCEPTION_NOMEDIAADDED');
            }
            $o_media_url = ox_new(\Oxid_Esales\Eshop\Application\Model\Media_Url::class);
            $o_media_url->set_language($this->_i_edit_lang);
            $o_media_url->oxmediaurls__oxisuploaded = new Field(0, Field::T_RAW);
            //handle uploaded file
            if ($a_media_file['name']) {
                try {
                    $s_media_url = Registry::get_utils_file()->process_file('mediaFile', 'out/media/');
                    $o_media_url->oxmediaurls__oxisuploaded = new Field(1, Field::T_RAW);
                } catch (Exception $e) {
                    return Registry::get_utils_view()->add_error_to_display($e->get_message());
                }
            }
            //save media url
            $o_media_url->oxmediaurls__oxobjectid = new Field($sox_id, Field::T_RAW);
            $o_media_url->oxmediaurls__oxurl = new Field($s_media_url, Field::T_RAW);
            $o_media_url->oxmediaurls__oxdesc = new Field($s_media_desc, Field::T_RAW);
            $o_media_url->save();
        }
        // renew price update time
        ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class)->renew_price_update_time();
    }
    /**
     * Deletes media url (with possible linked files)
     */
    public function deletemedia(): void
    {
        $sox_id = $this->get_edit_object_id();
        $s_media_id = Registry::get_request()->get_request_escaped_parameter('mediaid');
        if ($s_media_id && $sox_id) {
            $o_media_url = ox_new(\Oxid_Esales\Eshop\Application\Model\Media_Url::class);
            $o_media_url->load($s_media_id);
            $o_media_url->delete();
        }
    }
    /**
     * Adds default values for extended article parameters. Returns modified
     * parameters array.
     *
     * @param array $aParams Article parameters array
     *
     * @return array
     */
    public function add_default_values($a_params)
    {
        return $a_params;
    }
    /**
     * Updates existing media descriptions
     */
    public function update_media(): void
    {
        $a_media_urls = Registry::get_request()->get_request_escaped_parameter('aMediaUrls');
        if (is_array($a_media_urls)) {
            foreach ($a_media_urls as $s_media_id => $a_media_params) {
                $o_media = ox_new(\Oxid_Esales\Eshop\Application\Model\Media_Url::class);
                if ($o_media->load($s_media_id)) {
                    $o_media->set_language(0);
                    $o_media->assign($a_media_params);
                    $o_media->set_language($this->_i_edit_lang);
                    $o_media->save();
                }
            }
        }
    }
    /**
     * Returns array of possible unit combination and its translation for edit language
     *
     * @return array
     */
    public function get_units_array()
    {
        if ($this->_a_units_array === null) {
            $this->_a_units_array = Registry::get_lang()->get_similar_by_key('_UNIT_', $this->_i_edit_lang, false);
        }
        return $this->_a_units_array;
    }
    /**
     * Method used to overload and update article.
     *
     * @param \oxArticle $article
     *
     * @return \oxArticle
     */
    protected function update_article($article)
    {
        return $article;
    }
    /**
     * Adds data to _aViewData for later use in templates.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     */
    protected function prepare_bundled_articles_data_for_view($article)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $config = Registry::get_config();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $article_table = $table_view_name_generator->get_view_name('oxarticles', $this->_i_edit_lang);
        $query = "select {$article_table}.oxtitle, {$article_table}.oxartnum, {$article_table}.oxvarselect " . "from {$article_table} where 1 ";
        // #546
        $is_variant_selection_enabled = $config->get_config_param('blVariantsSelection');
        $bundle_id_field = 'oxarticles__oxbundleid';
        $query .= $is_variant_selection_enabled ? '' : " and {$article_table}.oxparentid = '' ";
        $query .= " and {$article_table}.oxid = :oxid";
        $result_from_database = $database->select($query, ['oxid' => $article->{$bundle_id_field}->value]);
        if ($result_from_database != false && $result_from_database->count() > 0) {
            while (!$result_from_database->EOF) {
                $article_number = new Field($result_from_database->fields['oxartnum']);
                $article_title = new Field($result_from_database->fields['oxtitle'] . ' ' . $result_from_database->fields['oxvarselect']);
                $result_from_database->fetch_row();
            }
        }
        $this->_a_view_data['bundle_artnum'] = $article_number ?? null;
        $this->_a_view_data['bundle_title'] = $article_title ?? null;
    }
}