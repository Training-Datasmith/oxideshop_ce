<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Ox_Article_List;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Account article file download page.
 */
class Account_Downloads_Controller extends \Oxid_Esales\Eshop\Application\Controller\Account_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/account/downloads';
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * @var \OxidEsales\Eshop\Application\Model\OrderFileList
     */
    protected $_o_order_files_list;
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $a_path = [];
        $i_base_language = Registry::get_lang()->get_base_language();
        /** @var \OxidEsales\Eshop\Core\SeoEncoder $oSeoEncoder */
        $o_seo_encoder = Registry::get_seo_encoder();
        $a_path['title'] = Registry::get_lang()->translate_string('MY_ACCOUNT', $i_base_language, false);
        $a_path['link'] = $o_seo_encoder->get_static_url($this->get_view_config()->get_self_link() . 'cl=account');
        $a_paths[] = $a_path;
        $a_path['title'] = Registry::get_lang()->translate_string('MY_DOWNLOADS', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
    /**
     * Returns article list which was ordered and has downloadable files
     *
     * @return null|oxArticleList
     */
    public function get_order_files_list()
    {
        if ($this->_o_order_files_list !== null) {
            return $this->_o_order_files_list;
        }
        $o_order_file_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_File_List::class);
        $o_order_file_list->load_user_files($this->get_user()->get_id());
        $this->_o_order_files_list = $this->prepare_for_template($o_order_file_list);
        return $this->_o_order_files_list;
    }
    /**
     * Returns prepared orders files list
     *
     * @param \OxidEsales\Eshop\Application\Model\OrderFileList $oOrderFileList - list or orderfiles
     *
     * @return array
     */
    protected function prepare_for_template($o_order_file_list)
    {
        $o_order_articles = [];
        foreach ($o_order_file_list as $o_order_file) {
            $s_order_article_id_field = 'oxorderfiles__oxorderarticleid';
            $s_order_number_field = 'oxorderfiles__oxordernr';
            $s_order_date_field = 'oxorderfiles__oxorderdate';
            $s_order_title_field = 'oxorderfiles__oxarticletitle';
            $s_order_article_id = $o_order_file->{$s_order_article_id_field}->value;
            $o_order_articles[$s_order_article_id]['oxordernr'] = $o_order_file->{$s_order_number_field}->value;
            $o_order_articles[$s_order_article_id]['oxorderdate'] = substr((string) $o_order_file->{$s_order_date_field}->value, 0, 16);
            $o_order_articles[$s_order_article_id]['oxarticletitle'] = $o_order_file->{$s_order_title_field}->value;
            $o_order_articles[$s_order_article_id]['oxorderfiles'][] = $o_order_file;
        }
        return $o_order_articles;
    }
    /**
     * Returns error code.
     *
     * @return int
     */
    public function get_download_error()
    {
        return Registry::get_request()->get_request_escaped_parameter('download_error');
    }
}