<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Model\Base_Model;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Admin article main manager.
 * Collects and updates (on user submit) article base parameters data ( such as
 * title, article No., short Description and etc.).
 * Admin Menu: Manage Products -> Articles -> Main.
 */
class Article_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        Registry::get_config()->set_config_param('bl_perfLoadPrice', true);
        $o_article = $this->create_article();
        $o_article->enable_price_load();
        $this->_a_view_data['edit'] = $o_article;
        $s_ox_id = $this->get_edit_object_id();
        $s_vox_id = Registry::get_request()->get_request_escaped_parameter('voxid');
        $s_ox_parent_id = Registry::get_request()->get_request_escaped_parameter('oxparentid');
        // new variant ?
        if (isset($s_vox_id) && $s_vox_id == '-1' && isset($s_ox_parent_id) && $s_ox_parent_id && $s_ox_parent_id != '-1') {
            $o_parent_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_parent_article->load($s_ox_parent_id);
            $this->_a_view_data['parentarticle'] = $o_parent_article;
            $this->_a_view_data['oxparentid'] = $s_ox_parent_id;
            $this->_a_view_data['oxid'] = $s_ox_id = '-1';
        }
        if ($s_ox_id && $s_ox_id != '-1') {
            // load object
            $o_article = $this->update_article($o_article, $s_ox_id);
            // load object in other languages
            $o_other_lang = $o_article->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_article->load_in_lang(key($o_other_lang), $s_ox_id);
            }
            // variant handling
            if ($o_article->oxarticles__oxparentid->value) {
                $o_parent_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                $o_parent_article->load($o_article->oxarticles__oxparentid->value);
                $this->_a_view_data['parentarticle'] = $o_parent_article;
                $this->_a_view_data['oxparentid'] = $o_article->oxarticles__oxparentid->value;
                $this->_a_view_data['issubvariant'] = 1;
            }
            // #381A
            $this->form_jump_list($o_article, $o_parent_article ?? null);
            //hook for modules
            $o_article = $this->customize_article_information($o_article);
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
        $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 300, $o_article, 'oxarticles__oxlongdesc', 'details.css');
        $this->_a_view_data['blUseTimeCheck'] = Registry::get_config()->get_config_param('blUseTimeCheck');
        return 'article_main';
    }
    /**
     * @inheritDoc
     */
    protected function get_edit_value($object, $field_name)
    {
        return $object ? $object->get_long_description()->get_raw_value() : '';
    }
    /**
     * Saves changes of article parameters.
     */
    public function save(): void
    {
        parent::save();
        $o_db = Database_Provider::get_db();
        $o_config = Registry::get_config();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        // default values
        $a_params = $this->add_default_values($a_params);
        // null values
        if (isset($a_params['oxarticles__oxvat']) && $a_params['oxarticles__oxvat'] === '') {
            $a_params['oxarticles__oxvat'] = null;
        }
        // varianthandling
        $soxparent_id = Registry::get_request()->get_request_escaped_parameter('oxparentid');
        if (isset($soxparent_id) && $soxparent_id && $soxparent_id != '-1') {
            $a_params['oxarticles__oxparentid'] = $soxparent_id;
        } else {
            unset($a_params['oxarticles__oxparentid']);
        }
        $o_article = $this->create_article();
        $o_article->set_language($this->_i_edit_lang);
        if ($sox_id != '-1') {
            $o_article->load_in_lang($this->_i_edit_lang, $sox_id);
        } else {
            $a_params['oxarticles__oxid'] = null;
            $a_params['oxarticles__oxissearch'] = 1;
            $a_params['oxarticles__oxstockflag'] = 1;
            if (empty($a_params['oxarticles__oxstock'])) {
                $a_params['oxarticles__oxstock'] = 0;
            }
            if (!isset($a_params['oxarticles__oxactive'])) {
                $a_params['oxarticles__oxactive'] = 0;
            }
        }
        //article number handling, warns for artnum duplicates
        if (isset($a_params['oxarticles__oxartnum']) && strlen($a_params['oxarticles__oxartnum']) > 0 && $o_config->get_config_param('blWarnOnSameArtNums') && $o_article->oxarticles__oxartnum->value != $a_params['oxarticles__oxartnum']) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_select = 'select oxid from ' . $table_view_name_generator->get_view_name('oxarticles');
            $s_select .= ' where oxartnum = ' . $o_db->quote($a_params['oxarticles__oxartnum']);
            $s_select .= ' and oxid != ' . $o_db->quote($a_params['oxarticles__oxid']);
            $record = Database_Provider::get_db()->select($s_select);
            if ($record && $record->count() > 0) {
                $o_article->assign($record->fields);
                $this->_a_view_data['errorsavingatricle'] = 1;
            }
        }
        $o_article->set_language(0);
        //triming spaces from article title (M:876)
        if (isset($a_params['oxarticles__oxtitle'])) {
            $a_params['oxarticles__oxtitle'] = trim($a_params['oxarticles__oxtitle']);
        }
        $o_article->assign($a_params);
        $o_article->set_article_long_desc($this->process_long_desc($a_params['oxarticles__oxlongdesc']));
        $o_article->set_language($this->_i_edit_lang);
        $o_article = Registry::get_utils_file()->process_files($o_article);
        $o_article->save();
        // set oxid if inserted
        if ($sox_id == '-1') {
            $s_fast_cat = Registry::get_request()->get_request_escaped_parameter('art_category');
            if ($s_fast_cat != '-1') {
                $this->add_to_category($s_fast_cat, $o_article->get_id());
            }
        }
        $o_article = $this->save_additional_article_data($o_article, $a_params);
        $this->set_edit_object_id($o_article->get_id());
    }
    /**
     * Fixes html broken by html editor
     *
     * @param string $sValue value to fix
     *
     * @return string
     */
    protected function process_long_desc($s_value)
    {
        // TODO: the code below is redundant, optimize it, assignments should go smooth without conversions
        // hack, if editor screws up text, htmledit tends to do so
        $s_value = str_replace('&amp;nbsp;', '&nbsp;', $s_value);
        $s_value = str_replace('&amp;', '&', $s_value);
        $s_value = str_replace('&quot;', '"', $s_value);
        $s_value = str_replace('&lang=', '&amp;lang=', $s_value);
        $s_value = str_replace('<p>&nbsp;</p>', '', $s_value);
        return str_replace('<p>&nbsp; </p>', '', $s_value);
    }
    /**
     * Resets article categories counters
     *
     * @param string $sArticleId Article id
     */
    protected function reset_categories_counter($s_article_id)
    {
        $categories = Database_Provider::get_db()->get_col('select oxcatnid from oxobject2category where oxobjectid = :oxobjectid', ['oxobjectid' => $s_article_id]);
        foreach ($categories as $category) {
            $this->reset_counter('catArticle', $category);
        }
    }
    /**
     * Add article to category.
     *
     * @param string $sCatID Category id
     * @param string $sOXID  Article id
     */
    public function add_to_category($s_cat_id, $s_oxid): void
    {
        $base = ox_new(Base_Model::class);
        $base->init('oxobject2category');
        $base->oxobject2category__oxtime = new \Oxid_Esales\Eshop\Core\Field(0);
        $base->oxobject2category__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_oxid);
        $base->oxobject2category__oxcatnid = new \Oxid_Esales\Eshop\Core\Field($s_cat_id);
        $base = $this->update_base($base);
        $base->save();
    }
    /**
     * Copies article (with all parameters) to new articles.
     *
     * @param string $sOldId    old product id (default null)
     * @param string $sNewId    new product id (default null)
     * @param string $sParentId product parent id
     */
    public function copy_article($s_old_id = null, $s_new_id = null, $s_parent_id = null): void
    {
        $my_config = Registry::get_config();
        $s_old_id = $s_old_id ?: $this->get_edit_object_id();
        $s_new_id = $s_new_id ?: Registry::get_utils_object()->generate_uid();
        $o_article = ox_new(Base_Model::class);
        $o_article->init('oxarticles');
        if ($o_article->load($s_old_id)) {
            if ($my_config->get_config_param('blDisableDublArtOnCopy')) {
                $o_article->oxarticles__oxactive->set_value(0);
                $o_article->oxarticles__oxactivefrom->set_value(0);
                $o_article->oxarticles__oxactiveto->set_value(0);
            }
            // setting parent id
            if ($s_parent_id) {
                $o_article->oxarticles__oxparentid->set_value($s_parent_id);
            }
            // setting oxinsert/oxtimestamp
            $i_now = date('Y-m-d H:i:s', Registry::get_utils_date()->get_time());
            $o_article->oxarticles__oxinsert = new \Oxid_Esales\Eshop\Core\Field($i_now);
            // mantis#0001590: OXRATING and OXRATINGCNT not set to 0 when copying article
            $o_article->oxarticles__oxrating = new \Oxid_Esales\Eshop\Core\Field(0);
            $o_article->oxarticles__oxratingcnt = new \Oxid_Esales\Eshop\Core\Field(0);
            $o_article->oxarticles__oxsoldamount = new \Oxid_Esales\Eshop\Core\Field(0);
            $o_article->set_id($s_new_id);
            $o_article->save();
            //copy categories
            $this->copy_categories($s_old_id, $s_new_id);
            //atributes
            $this->copy_attributes($s_old_id, $s_new_id);
            //sellist
            $this->copy_selectlists($s_old_id, $s_new_id);
            //crossseling
            $this->copy_crossseling($s_old_id, $s_new_id);
            //accessoire
            $this->copy_accessoires($s_old_id, $s_new_id);
            // #983A copying staffelpreis info
            $this->copy_staffelpreis($s_old_id, $s_new_id);
            //copy article extends (longdescription)
            $this->copy_art_extends($s_old_id, $s_new_id);
            //files
            $this->copy_files($s_old_id, $s_new_id);
            $this->reset_content_cache();
            $my_utils_object = Registry::get_utils_object();
            $database = Database_Provider::get_db();
            $products_ids = Database_Provider::get_db()->get_col('select oxid from oxarticles where oxparentid = :oxparentid', ['oxparentid' => $s_old_id]);
            foreach ($products_ids as $product_id) {
                $this->copy_article($product_id, $my_utils_object->generate_uid(), $s_new_id);
            }
            // only for top articles
            if (!$s_parent_id) {
                $this->set_edit_object_id($o_article->get_id());
                //article number handling, warns for artnum duplicates
                $s_fnc_parameter = Registry::get_request()->get_request_escaped_parameter('fnc');
                $s_art_num_field = 'oxarticles__oxartnum';
                if ($my_config->get_config_param('blWarnOnSameArtNums') && $o_article->{$s_art_num_field}->value && $s_fnc_parameter == 'copyArticle') {
                    $s_select = 'select oxid from ' . $o_article->get_core_table_name() . ' where oxartnum = ' . $database->quote($o_article->{$s_art_num_field}->value) . ' and oxid != ' . $database->quote($s_new_id);
                    $record = $database->select($s_select);
                    if ($record && $record->count() > 0) {
                        $o_article->assign($record->fields);
                        $this->_a_view_data['errorsavingatricle'] = 1;
                    }
                }
            }
        }
    }
    /**
     * Copying category assignments
     *
     * @param string $sOldId       Id from old article
     * @param string $newArticleId Id from new article
     */
    protected function copy_categories($s_old_id, $new_article_id)
    {
        $my_utils_object = Registry::get_utils_object();
        $database = Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category');
        $categories = $database->get_all(sprintf('select oxcatnid, oxtime from %s where oxobjectid = :oxobjectid', $s_o2c_view), ['oxobjectid' => $s_old_id]);
        foreach ($categories as $category) {
            $unique_id = $my_utils_object->generate_uid();
            $s_cat_id = $category['oxcatnid'];
            $s_time = $category['oxtime'];
            $s_sql = $this->form_query_for_copying_to_category($new_article_id, $unique_id, $s_cat_id, $s_time);
            $database->execute($s_sql);
        }
    }
    /**
     * Copying attributes assignments
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_attributes($s_old_id, $s_new_id)
    {
        $utils_object = Registry::get_utils_object();
        $article_attributes_ids = Database_Provider::get_db()->get_col('select oxid from oxobject2attribute where oxobjectid = :oxobjectid', ['oxobjectid' => $s_old_id]);
        foreach ($article_attributes_ids as $article_attributes_id) {
            $article_attribute = ox_new(Base_Model::class);
            $article_attribute->init('oxobject2attribute');
            $article_attribute->load($article_attributes_id);
            $article_attribute->set_id($utils_object->generate_uid());
            $article_attribute->oxobject2attribute__oxobjectid->set_value($s_new_id);
            $article_attribute->save();
        }
    }
    /**
     * Copying files
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_files($s_old_id, $s_new_id)
    {
        $my_utils_object = Registry::get_utils_object();
        $o_db = Database_Provider::get_db();
        $s_q = 'SELECT * FROM `oxfiles` WHERE `oxartid` = :oxartid';
        $o_rs = $o_db->select($s_q, ['oxartid' => $s_old_id]);
        if ($o_rs !== false && $o_rs->count() > 0) {
            while (!$o_rs->EOF) {
                $o_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
                $o_file->set_id($my_utils_object->generate_uid());
                $o_file->oxfiles__oxartid = new \Oxid_Esales\Eshop\Core\Field($s_new_id);
                $o_file->oxfiles__oxfilename = new \Oxid_Esales\Eshop\Core\Field($o_rs->fields['OXFILENAME']);
                $o_file->oxfiles__oxfilesize = new \Oxid_Esales\Eshop\Core\Field($o_rs->fields['OXFILESIZE']);
                $o_file->oxfiles__oxstorehash = new \Oxid_Esales\Eshop\Core\Field($o_rs->fields['OXSTOREHASH']);
                $o_file->oxfiles__oxpurchasedonly = new \Oxid_Esales\Eshop\Core\Field($o_rs->fields['OXPURCHASEDONLY']);
                $o_file->save();
                $o_rs->fetch_row();
            }
        }
    }
    /**
     * Copying selectlists assignments
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_selectlists($s_old_id, $s_new_id)
    {
        $my_utils_object = Registry::get_utils_object();
        $database = Database_Provider::get_db();
        $select_list_ids = $database->get_col('select oxselnid from oxobject2selectlist where oxobjectid = :oxobjectid', ['oxobjectid' => $s_old_id]);
        foreach ($select_list_ids as $select_list_id) {
            $database->execute('INSERT INTO oxobject2selectlist (oxid, oxobjectid, oxselnid) VALUES (:oxid, :oxobjectid, :oxselnid)', ['oxid' => $my_utils_object->generate_uid(), 'oxobjectid' => $s_new_id, 'oxselnid' => $select_list_id]);
        }
    }
    /**
     * Copying crossseling assignments
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_crossseling($s_old_id, $s_new_id)
    {
        $my_utils_object = Registry::get_utils_object();
        $database = Database_Provider::get_db();
        $objects = $database->get_col('select oxobjectid from oxobject2article where oxarticlenid = :oxarticlenid', ['oxarticlenid' => $s_old_id]);
        foreach ($objects as $object) {
            $query = 'INSERT INTO oxobject2article (oxid, oxobjectid, oxarticlenid) ' . 'VALUES (:oxid, :oxobjectid, :oxarticlenid)';
            $database->execute($query, ['oxid' => $my_utils_object->generate_uid(), 'oxobjectid' => $object, 'oxarticlenid' => $s_new_id]);
        }
    }
    /**
     * Copying accessoires assignments
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_accessoires($s_old_id, $s_new_id)
    {
        $my_utils_object = Registry::get_utils_object();
        $database = Database_Provider::get_db();
        $accessories = $database->get_col('select oxobjectid from oxaccessoire2article where oxarticlenid = :oxarticlenid', ['oxarticlenid' => $s_old_id]);
        foreach ($accessories as $accessory_id) {
            $s_sql = 'INSERT INTO oxaccessoire2article (oxid, oxobjectid, oxarticlenid) ' . 'VALUES (:oxid, :oxobjectid, :oxarticlenid)';
            $database->execute($s_sql, ['oxid' => $my_utils_object->generate_uid(), 'oxobjectid' => $accessory_id, 'oxarticlenid' => $s_new_id]);
        }
    }
    /**
     * Copying staffelpreis assignments
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_staffelpreis($s_old_id, $s_new_id)
    {
        $s_shop_id = Registry::get_config()->get_shop_id();
        $o_price_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_price_list->init('oxbase', 'oxprice2article');
        $s_q = 'select * from oxprice2article where oxartid = :oxartid and oxshopid = :oxshopid ' . 'and (oxamount > 0 or oxamountto > 0) order by oxamount ';
        $o_price_list->select_string($s_q, ['oxartid' => $s_old_id, 'oxshopid' => $s_shop_id]);
        if ($o_price_list->count()) {
            foreach ($o_price_list as $o_item) {
                $o_item->oxprice2article__oxid->set_value($o_item->set_id());
                $o_item->oxprice2article__oxartid->set_value($s_new_id);
                $o_item->save();
            }
        }
    }
    /**
     * Copying article extends
     *
     * @param string $sOldId Id from old article
     * @param string $sNewId Id from new article
     */
    protected function copy_art_extends($s_old_id, $s_new_id)
    {
        $o_ext = ox_new(Base_Model::class);
        $o_ext->init('oxartextends');
        $o_ext->load($s_old_id);
        $o_ext->set_id($s_new_id);
        $o_ext->save();
    }
    /**
     * Saves article parameters in different language.
     */
    public function saveinnlang(): void
    {
        $this->save();
    }
    /**
     * Sets default values for empty article (currently does nothing), returns
     * array with parameters.
     *
     * @param array $aParams Parameters, to set default values
     *
     * @return array
     */
    public function add_default_values($a_params)
    {
        return $a_params;
    }
    /**
     * Function forms article variants jump list.
     *
     * @param object $oArticle       article object
     * @param object $oParentArticle article parent object
     */
    protected function form_jump_list($o_article, $o_parent_article)
    {
        $a_jump_list = [];
        //fetching parent article variants
        $s_ox_id_field = 'oxarticles__oxid';
        if (isset($o_parent_article)) {
            $a_jump_list[] = [$o_parent_article->{$s_ox_id_field}->value, $this->get_title($o_parent_article)];
            $s_edit_language_parameter = Registry::get_request()->get_request_escaped_parameter('editlanguage');
            $o_parent_variants = $o_parent_article->get_admin_variants($s_edit_language_parameter);
            if ($o_parent_variants->count()) {
                foreach ($o_parent_variants as $o_var) {
                    $a_jump_list[] = [$o_var->{$s_ox_id_field}->value, ' - ' . $this->get_title($o_var)];
                    if ($o_var->{$s_ox_id_field}->value == $o_article->{$s_ox_id_field}->value) {
                        $o_variants = $o_article->get_admin_variants($s_edit_language_parameter);
                        if ($o_variants->count()) {
                            foreach ($o_variants as $o_v_var) {
                                $a_jump_list[] = [$o_v_var->{$s_ox_id_field}->value, ' -- ' . $this->get_title($o_v_var)];
                            }
                        }
                    }
                }
            }
        } else {
            $a_jump_list[] = [$o_article->{$s_ox_id_field}->value, $this->get_title($o_article)];
            //fetching this article variants data
            $o_variants = $o_article->get_admin_variants(Registry::get_request()->get_request_escaped_parameter('editlanguage'));
            if ($o_variants && $o_variants->count()) {
                foreach ($o_variants as $o_var) {
                    $a_jump_list[] = [$o_var->{$s_ox_id_field}->value, ' - ' . $this->get_title($o_var)];
                }
            }
        }
        if (count($a_jump_list) > 1) {
            $this->_a_view_data['thisvariantlist'] = $a_jump_list;
        }
    }
    /**
     * Returns formed variant title
     *
     * @param object $oObj product object
     *
     * @return string
     */
    protected function get_title($o_obj)
    {
        $s_title = $o_obj->oxarticles__oxtitle->value;
        if (!strlen((string) $s_title)) {
            return $o_obj->oxarticles__oxvarselect->value;
        }
        return $s_title;
    }
    /**
     * Returns shop manufacturers list
     *
     * @return \OxidEsales\Eshop\Application\Model\CategoryList
     */
    public function get_category_list()
    {
        $o_cat_tree = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
        $o_cat_tree->load_list();
        return $o_cat_tree;
    }
    /**
     * Returns shop manufacturers list
     *
     * @return \OxidEsales\Eshop\Application\Model\VendorList
     */
    public function get_vendor_list()
    {
        $o_vendorlist = ox_new(\Oxid_Esales\Eshop\Application\Model\Vendor_List::class);
        $o_vendorlist->load_vendor_list();
        return $o_vendorlist;
    }
    /**
     * Returns shop manufacturers list
     *
     * @return \OxidEsales\Eshop\Application\Model\ManufacturerList
     */
    public function get_manufacturer_list()
    {
        $o_manufacturer_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Manufacturer_List::class);
        $o_manufacturer_list->load_manufacturer_list();
        return $o_manufacturer_list;
    }
    /**
     * Loads language for article.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle
     * @param string                                      $sOxId
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function update_article($o_article, $s_ox_id)
    {
        $o_article->load_in_lang($this->_i_edit_lang, $s_ox_id);
        return $o_article;
    }
    /**
     * Forms query which is used for adding article to category.
     *
     * @param string $newArticleId
     * @param string $sUid
     * @param string $sCatId
     * @param string $sTime
     *
     * @return string
     */
    protected function form_query_for_copying_to_category($new_article_id, $s_uid, $s_cat_id, $s_time)
    {
        $o_db = Database_Provider::get_db();
        return 'insert into oxobject2category (oxid, oxobjectid, oxcatnid, oxtime) ' . 'VALUES (' . $o_db->quote($s_uid) . ', ' . $o_db->quote($new_article_id) . ', ' . $o_db->quote($s_cat_id) . ', ' . $o_db->quote($s_time) . ') ';
    }
    /**
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $base
     *
     * @return \OxidEsales\Eshop\Core\Model\BaseModel $base
     */
    protected function update_base($base)
    {
        return $base;
    }
    /**
     * Customize article data for rendering.
     * Intended to be used by modules.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function customize_article_information($article)
    {
        return $article;
    }
    /**
     * Save non standard article information if needed.
     * Intended to be used by modules.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     * @param array                                       $parameters
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function save_additional_article_data($article, $parameters)
    {
        return $article;
    }
    /**
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function create_article()
    {
        return ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
    }
}