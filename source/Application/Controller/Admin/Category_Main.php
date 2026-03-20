<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use stdClass;
/**
 * Admin article main categories manager.
 * There is possibility to change categories description, sorting, range of price
 * and etc.
 * Admin Menu: Manage Products -> Categories -> Main.
 */
class Category_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    public const NEW_CATEGORY_ID = '-1';
    /**
     * Loads article category data,
     * returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        $my_config = Registry::get_config();
        parent::render();
        /** @var \OxidEsales\Eshop\Application\Model\Category $oCategory */
        $o_category = $this->create_category();
        $category_id = $this->get_edit_object_id();
        $this->_a_view_data['edit'] = $o_category;
        $this->_a_view_data['oxid'] = $category_id;
        if (isset($category_id) && $category_id != self::NEW_CATEGORY_ID) {
            // generating category tree for select list
            $this->create_category_tree('artcattree', $category_id);
            // load object
            $o_category->load_in_lang($this->_i_edit_lang, $category_id);
            //Disable editing for derived items
            if ($o_category->is_derived()) {
                $this->_a_view_data['readonly_fields'] = true;
            }
            $o_other_lang = $o_category->get_available_in_langs();
            if (!isset($o_other_lang[$this->_i_edit_lang])) {
                $o_category->load_in_lang(key($o_other_lang), $category_id);
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
            if ($o_category->oxcategories__oxparentid->value == 'oxrootid') {
                $o_category->oxcategories__oxparentid->set_value('');
            }
            $this->get_category_tree('cattree', $o_category->oxcategories__oxparentid->value, $o_category->oxcategories__oxid->value, true, $o_category->oxcategories__oxshopid->value);
            $this->_a_view_data['defsort'] = $o_category->oxcategories__oxdefsort->value;
        } else {
            $this->create_category_tree('cattree', '', true, $my_config->get_shop_id());
        }
        $this->_a_view_data['sortableFields'] = $this->get_sortable_fields();
        if ($this->get_view_config()->is_alt_image_server_configured()) {
            $this->_a_view_data['imageUrl'] = Container_Facade::get_parameter('oxid_esales.alternative_image_url');
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            /** @var \OxidEsales\Eshop\Application\Controller\Admin\CategoryMainAjax $oCategoryMainAjax */
            $o_category_main_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Category_Main_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_category_main_ajax->get_columns();
            return 'popups/category_main';
        }
        return 'category_main';
    }
    /**
     * Returns an array of article object DB fields, without multi language and unsortible fields.
     *
     * @return array
     */
    public function get_sortable_fields()
    {
        $a_skip_fields = ['OXID', 'OXSHOPID', 'OXMAPID', 'OXPARENTID', 'OXACTIVE', 'OXACTIVEFROM', 'OXACTIVETO', 'OXSHORTDESC', 'OXUNITNAME', 'OXUNITQUANTITY', 'OXEXTURL', 'OXURLDESC', 'OXURLIMG', 'OXVAT', 'OXTHUMB', 'OXPIC1', 'OXPIC2', 'OXPIC3', 'OXPIC4', 'OXPIC5', 'OXPIC6', 'OXPIC7', 'OXPIC8', 'OXPIC9', 'OXPIC10', 'OXPIC11', 'OXPIC12', 'OXSTOCKFLAG', 'OXSTOCKTEXT', 'OXNOSTOCKTEXT', 'OXDELIVERY', 'OXFILE', 'OXSEARCHKEYS', 'OXTEMPLATE', 'OXQUESTIONEMAIL', 'OXISSEARCH', 'OXISCONFIGURABLE', 'OXBUNDLEID', 'OXFOLDER', 'OXSUBCLASS', 'OXREMINDACTIVE', 'OXREMINDAMOUNT', 'OXVENDORID', 'OXMANUFACTURERID', 'OXSKIPDISCOUNTS', 'OXBLFIXEDPRICE', 'OXICON', 'OXVARSELECT', 'OXAMITEMID', 'OXAMTASKID', 'OXPIXIEXPORT', 'OXPIXIEXPORTED', 'OXSORT', 'OXUPDATEPRICE', 'OXUPDATEPRICEA', 'OXUPDATEPRICEB', 'OXUPDATEPRICEC', 'OXUPDATEPRICETIME', 'OXISDOWNLOADABLE', 'OXVARMAXPRICE', 'OXSHOWCUSTOMAGREEMENT'];
        /** @var \OxidEsales\Eshop\Core\DbMetaDataHandler $oDbHandler */
        $o_db_handler = ox_new(\Oxid_Esales\Eshop\Core\Db_Meta_Data_Handler::class);
        $a_fields = array_merge($o_db_handler->get_multilang_fields('oxarticles'), array_keys($o_db_handler->get_singlelang_fields('oxarticles', 0)));
        $a_fields = array_diff($a_fields, $a_skip_fields);
        return array_unique($a_fields);
    }
    /**
     * Saves article category data.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = $this->parse_request_parameters_for_save(Registry::get_request()->get_request_escaped_parameter('editval'));
        if (!$this->validate_request_images()) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_WRONG_IMAGE_FILE_TYPE');
            return;
        }
        /** @var \OxidEsales\Eshop\Application\Model\Category $oCategory */
        $o_category = $this->create_category();
        if ($sox_id != self::NEW_CATEGORY_ID) {
            $this->reset_counter('catArticle', $sox_id);
            $this->reset_category_pictures($o_category, $a_params, $sox_id);
        }
        //Disable editing for derived items
        if ($o_category->is_derived()) {
            return;
        }
        $o_category = $this->update_category_on_save($o_category, $a_params);
        $o_category->save();
        $this->set_edit_object_id($o_category->get_id());
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
        // workaround for firefox showing &lang= as &9001;= entity, mantis#0001272
        return str_replace('&lang=', '&amp;lang=', $s_value);
    }
    /**
     * Saves article category data to different language (eg. english).
     */
    public function saveinnlang(): void
    {
        $this->save();
    }
    /**
     * Deletes selected master picture.
     */
    public function delete_picture(): void
    {
        $my_config = Registry::get_config();
        if ($my_config->is_demo_shop()) {
            // disabling uploading pictures if this is demo shop
            $o_ex = new \Oxid_Esales\Eshop\Core\Exception\Exception_To_Display();
            $o_ex->set_message('CATEGORY_PICTURES_UPLOADISDISABLED');
            /** @var \OxidEsales\Eshop\Core\UtilsView $oUtilsView */
            $o_utils_view = Registry::get_utils_view();
            $o_utils_view->add_error_to_display($o_ex, false);
            return;
        }
        $s_ox_id = $this->get_edit_object_id();
        $s_field = Registry::get_request()->get_request_escaped_parameter('masterPicField');
        if (empty($s_field)) {
            return;
        }
        /** @var \OxidEsales\Eshop\Application\Model\Category $oItem */
        $o_item = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $o_item->load($s_ox_id);
        $this->delete_cat_picture($o_item, $s_field);
    }
    /**
     * Delete category picture, specified in $sField parameter
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $item  active category object
     * @param string                                       $field picture field name
     */
    protected function delete_cat_picture($item, $field)
    {
        if ($item->is_derived()) {
            return;
        }
        $my_config = Registry::get_config();
        $s_item_key = 'oxcategories__' . $field;
        $s_img_type = match ($field) {
            'oxthumb' => 'TC',
            'oxicon' => 'CICO',
            'oxpromoicon' => 'PICO',
            default => false,
        };
        if ($s_img_type !== false) {
            /** @var \OxidEsales\Eshop\Core\UtilsPic $myUtilsPic */
            $my_utils_pic = Registry::get_utils_pic();
            /** @var \OxidEsales\Eshop\Core\UtilsFile $oUtilsFile */
            $o_utils_file = Registry::get_utils_file();
            $s_dir = $my_config->get_picture_dir(false);
            $my_utils_pic->safe_picture_delete($item->{$s_item_key}->value, $s_dir . $o_utils_file->get_image_dir_by_type($s_img_type), 'oxcategories', $field);
            $item->{$s_item_key} = new \Oxid_Esales\Eshop\Core\Field();
            $item->save();
        }
    }
    /**
     * Parse parameters prior to saving category.
     *
     * @param array $aReqParams Request parameters.
     *
     * @return array
     */
    protected function parse_request_parameters_for_save($a_req_params)
    {
        // checkbox handling
        if (!isset($a_req_params['oxcategories__oxactive'])) {
            $a_req_params['oxcategories__oxactive'] = 0;
        }
        if (!isset($a_req_params['oxcategories__oxhidden'])) {
            $a_req_params['oxcategories__oxhidden'] = 0;
        }
        if (!isset($a_req_params['oxcategories__oxdefsortmode'])) {
            $a_req_params['oxcategories__oxdefsortmode'] = 0;
        }
        // null values
        if (!isset($a_req_params['oxcategories__oxvat']) || $a_req_params['oxcategories__oxvat'] === '') {
            $a_req_params['oxcategories__oxvat'] = null;
        }
        if ($this->get_edit_object_id() == self::NEW_CATEGORY_ID) {
            //#550A - if new category is made then is must be default activ
            //#4051: Impossible to create inactive category
            //$aReqParams['oxcategories__oxactive'] = 1;
            $a_req_params['oxcategories__oxid'] = null;
        }
        if (isset($a_req_params['oxcategories__oxlongdesc'])) {
            $a_req_params['oxcategories__oxlongdesc'] = $this->process_long_desc($a_req_params['oxcategories__oxlongdesc']);
        }
        if (empty($a_req_params['oxcategories__oxpricefrom'])) {
            $a_req_params['oxcategories__oxpricefrom'] = 0;
        }
        if (empty($a_req_params['oxcategories__oxpriceto'])) {
            $a_req_params['oxcategories__oxpriceto'] = 0;
        }
        return $a_req_params;
    }
    /**
     * Set parameters, language and files to category object.
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $category
     * @param array                                        $params
     * @param string                                       $categoryId
     */
    protected function reset_category_pictures($category, $params, $category_id)
    {
        $config = Registry::get_config();
        $category->load($category_id);
        $category->load_in_lang($this->_i_edit_lang, $category_id);
        /** @var \OxidEsales\Eshop\Core\UtilsPic $utilsPic */
        $utils_pic = Registry::get_utils_pic();
        // #1173M - not all pic are deleted, after article is removed
        $utils_pic->overwrite_pic($category, 'oxcategories', 'oxthumb', 'TC', '0', $params, $config->get_picture_dir(false));
        $utils_pic->overwrite_pic($category, 'oxcategories', 'oxicon', 'CICO', 'icon', $params, $config->get_picture_dir(false));
        $utils_pic->overwrite_pic($category, 'oxcategories', 'oxpromoicon', 'PICO', 'icon', $params, $config->get_picture_dir(false));
    }
    /**
     * Set parameters, language and files to category object.
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $category
     * @param array                                        $params
     *
     * @return \OxidEsales\Eshop\Application\Model\Category
     */
    protected function update_category_on_save($category, $params)
    {
        $category->assign($params);
        $category->set_language($this->_i_edit_lang);
        $utils_file = Registry::get_utils_file();
        return $utils_file->process_files($category);
    }
    /**
     * @return \OxidEsales\Eshop\Application\Model\Category
     */
    protected function create_category()
    {
        return ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
    }
}