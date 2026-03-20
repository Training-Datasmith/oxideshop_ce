<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article files parameters manager.
 * Collects and updates (on user submit) files.
 * Admin Menu: Manage Products -> Articles -> Files.
 */
class Article_Files extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Template name
     *
     * @var string
     */
    protected $_s_this_template = 'article_files';
    /**
     * Stores editing article
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_article;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blEnableDownloads')) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('EXCEPTION_DISABLED_DOWNLOADABLE_PRODUCTS');
        }
        $o_article = $this->get_article();
        // variant handling
        if ($o_article->oxarticles__oxparentid->value) {
            $o_parent_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_parent_article->load($o_article->oxarticles__oxparentid->value);
            $o_article->oxarticles__oxisdownloadable = new \Oxid_Esales\Eshop\Core\Field($o_parent_article->oxarticles__oxisdownloadable->value);
            $this->_a_view_data['oxparentid'] = $o_article->oxarticles__oxparentid->value;
        }
        return $this->_s_this_template;
    }
    /**
     * Saves editing article changes (oxisdownloadable)
     * and updates oxFile object which are associated with editing object
     */
    public function save(): void
    {
        // save article changes
        $a_article_changes = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_article = $this->get_article();
        $o_article->assign($a_article_changes);
        $o_article->save();
        //update article files
        $a_article_files = Registry::get_request()->get_request_escaped_parameter('article_files');
        if (is_array($a_article_files)) {
            foreach ($a_article_files as $s_article_file_id => $a_article_file_update) {
                $o_article_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
                $o_article_file->load($s_article_file_id);
                $a_article_file_update = $this->process_options($a_article_file_update);
                $o_article_file->assign($a_article_file_update);
                if ($o_article_file->is_under_download_folder()) {
                    $o_article_file->save();
                } else {
                    \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('EXCEPTION_NOFILE');
                }
            }
        }
    }
    /**
     * Returns current oxarticle object
     *
     * @param bool $blReset Load article again
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_article($bl_reset = false)
    {
        if ($this->_o_article !== null && !$bl_reset) {
            return $this->_o_article;
        }
        $s_product_id = $this->get_edit_object_id();
        $o_product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_product->load($s_product_id);
        return $this->_o_article = $o_product;
    }
    /**
     * Creates new oxFile object and stores newly uploaded file
     */
    public function upload()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($my_config->is_demo_shop()) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $o_ex->set_message('ARTICLE_EXTEND_UPLOADISDISABLED');
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex, false);
            return;
        }
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('newfile');
        $a_params = $this->process_options($a_params);
        $a_new_file = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_uploaded_file('newArticleFile');
        //uploading and processing supplied file
        $o_article_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
        $o_article_file->assign($a_params);
        if (!$a_new_file['name'] && !$o_article_file->oxfiles__oxfilename->value) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('EXCEPTION_NOFILE');
        }
        if ($a_new_file['name']) {
            $o_article_file->oxfiles__oxfilename = new \Oxid_Esales\Eshop\Core\Field($a_new_file['name'], \Oxid_Esales\Eshop\Core\Field::T_RAW);
            try {
                $o_article_file->process_file('newArticleFile');
            } catch (Exception $e) {
                return \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($e->get_message());
            }
        }
        if (!$o_article_file->is_under_download_folder()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('EXCEPTION_NOFILE');
        }
        //save media url
        $o_article_file->oxfiles__oxartid = new \Oxid_Esales\Eshop\Core\Field($sox_id, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $o_article_file->save();
    }
    /**
     * Deletes article file from fileid parameter and checks if this file belongs to current article.
     *
     * @return void
     */
    public function deletefile()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($my_config->is_demo_shop()) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Exception_To_Display::class);
            $o_ex->set_message('ARTICLE_EXTEND_UPLOADISDISABLED');
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display($o_ex, false);
            return;
        }
        $s_article_id = $this->get_edit_object_id();
        $s_article_file_id = Registry::get_request()->get_request_escaped_parameter('fileid');
        $o_article_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
        $o_article_file->load($s_article_file_id);
        if ($o_article_file->has_valid_downloads()) {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display('EXCEPTION_DELETING_VALID_FILE');
        }
        if ($o_article_file->oxfiles__oxartid->value == $s_article_id) {
            $o_article_file->delete();
        }
    }
    /**
     * Returns real config option value
     *
     * @param int $iOption option value
     *
     * @return int
     */
    public function get_config_option_value($i_option)
    {
        return $i_option < 0 ? '' : $i_option;
    }
    /**
     * Process config options. If value is not set, save as "-1" to database
     *
     * @param array $aParams params
     *
     * @return array
     */
    protected function process_options($a_params)
    {
        if (!is_array($a_params)) {
            $a_params = [];
        }
        if (!isset($a_params['oxfiles__oxdownloadexptime']) || $a_params['oxfiles__oxdownloadexptime'] == '') {
            $a_params['oxfiles__oxdownloadexptime'] = -1;
        }
        if (!isset($a_params['oxfiles__oxlinkexptime']) || $a_params['oxfiles__oxlinkexptime'] == '') {
            $a_params['oxfiles__oxlinkexptime'] = -1;
        }
        if (!isset($a_params['oxfiles__oxmaxunregdownloads']) || $a_params['oxfiles__oxmaxunregdownloads'] == '') {
            $a_params['oxfiles__oxmaxunregdownloads'] = -1;
        }
        if (!isset($a_params['oxfiles__oxmaxdownloads']) || $a_params['oxfiles__oxmaxdownloads'] == '') {
            $a_params['oxfiles__oxmaxdownloads'] = -1;
        }
        return $a_params;
    }
}