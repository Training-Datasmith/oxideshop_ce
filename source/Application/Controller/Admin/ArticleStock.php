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
 * Admin article inventory manager.
 * Collects such information about article as stock quantity, delivery status,
 * stock message, etc; Updates information (on user submit).
 * Admin Menu: Manage Products -> Articles -> Inventory.
 */
class Article_Stock extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Loads article Inventory information and
     * returns the name of template file.
     *
     * @return string
     */
    public function render()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        parent::render();
        $this->_a_view_data['edit'] = $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_article->load_in_lang($this->_i_edit_lang, $sox_id);
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
            if ($o_article->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // variant handling
            if ($o_article->oxarticles__oxparentid->value) {
                $o_parent_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                $o_parent_article->load($o_article->oxarticles__oxparentid->value);
                $this->_a_view_data['parentarticle'] = $o_parent_article;
                $this->_a_view_data['oxparentid'] = $o_article->oxarticles__oxparentid->value;
            }
            if ($my_config->get_config_param('blMallInterchangeArticles')) {
                $s_shop_select = '1';
            } else {
                $s_shop_id = $my_config->get_shop_id();
                $s_shop_select = " oxshopid =  '{$s_shop_id}' ";
            }
            $o_price_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_price_list->init('oxbase', 'oxprice2article');
            $s_q = 'select * from oxprice2article where oxartid = :oxartid ' . "and {$s_shop_select} and (oxamount > 0 or oxamountto > 0) order by oxamount ";
            $o_price_list->selectstring($s_q, ['oxartid' => $sox_id]);
            $this->_a_view_data['amountprices'] = $o_price_list;
        }
        return 'article_stock';
    }
    /**
     * Saves article Inventori information changes.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->load_in_lang($this->_i_edit_lang, $sox_id);
        $o_article->set_language(0);
        // checkbox handling
        if (!$o_article->oxarticles__oxparentid->value && !isset($a_params['oxarticles__oxremindactive'])) {
            $a_params['oxarticles__oxremindactive'] = 0;
        }
        $o_article->assign($a_params);
        //tells to article to save in different language
        $o_article->set_language($this->_i_edit_lang);
        $o_article = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->process_files($o_article);
        $o_article->reset_remind_status();
        $o_article->update_variants_remind();
        $o_article->save();
    }
    /**
     * Adds or updates amount price to article
     *
     * @param string $sOXID         Object ID
     * @param array  $aUpdateParams Parameters
     */
    public function addprice($s_oxid = null, $a_update_params = null): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $this->reset_content_cache();
        $s_ox_art_id = $this->get_edit_object_id();
        $this->on_article_amount_price_change($s_ox_art_id);
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        if (!is_array($a_params)) {
            return;
        }
        if (isset($a_update_params) && is_array($a_update_params)) {
            $a_params = array_merge($a_params, $a_update_params);
        }
        //replacing commas
        foreach ($a_params as $key => $s_param) {
            $a_params[$key] = str_replace(',', '.', $s_param);
        }
        $a_params['oxprice2article__oxshopid'] = $my_config->get_shop_id();
        if (isset($s_oxid)) {
            $a_params['oxprice2article__oxid'] = $s_oxid;
        }
        $a_params['oxprice2article__oxartid'] = $s_ox_art_id;
        if (!isset($a_params['oxprice2article__oxamount']) || !$a_params['oxprice2article__oxamount']) {
            $a_params['oxprice2article__oxamount'] = '1';
        }
        if (!$my_config->get_config_param('blAllowUnevenAmounts')) {
            $a_params['oxprice2article__oxamount'] = round((string) $a_params['oxprice2article__oxamount']);
            $a_params['oxprice2article__oxamountto'] = round((string) $a_params['oxprice2article__oxamountto']);
        }
        $d_price = $a_params['price'];
        $s_type = $a_params['pricetype'];
        $o_article_price = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $o_article_price->init('oxprice2article');
        $o_article_price->assign($a_params);
        $o_article_price->{$s_type} = new \Oxid_Esales\Eshop\Core\Field($d_price);
        //validating
        if ($o_article_price->{$s_type}->value && $o_article_price->oxprice2article__oxamount->value && $o_article_price->oxprice2article__oxamountto->value && is_numeric($o_article_price->{$s_type}->value) && is_numeric($o_article_price->oxprice2article__oxamount->value) && is_numeric($o_article_price->oxprice2article__oxamountto->value) && $o_article_price->oxprice2article__oxamount->value <= $o_article_price->oxprice2article__oxamountto->value) {
            $o_article_price->save();
        }
        // check if abs price is lower than base price
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->load_in_lang($this->_i_edit_lang, $s_ox_art_id);
        $s_price_field = 'oxarticles__oxprice';
        if ($a_params['price'] >= $o_article->{$s_price_field}->value && $a_params['pricetype'] == 'oxprice2article__oxaddabs') {
            if (is_null($s_oxid)) {
                $s_oxid = $o_article_price->get_id();
            }
            $this->_a_view_data['errorscaleprice'][] = $s_oxid;
        }
    }
    /**
     * Updates all amount prices for article at once
     */
    public function updateprices(): void
    {
        $a_params = Registry::get_request()->get_request_escaped_parameter('updateval');
        if (is_array($a_params)) {
            foreach ($a_params as $sox_id => $a_stock_params) {
                $this->addprice($sox_id, $a_stock_params);
            }
        }
        $s_ox_art_id = $this->get_edit_object_id();
        $this->on_article_amount_price_change($s_ox_art_id);
    }
    /**
     * Adds amount price to article
     */
    public function deleteprice(): void
    {
        $this->reset_content_cache();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $article_id = $this->get_edit_object_id();
        $o_db->execute('delete from oxprice2article where oxid = :oxid and oxartid = :oxartid', ['oxid' => Registry::get_request()->get_request_escaped_parameter('priceid'), 'oxartid' => $article_id]);
        $this->on_article_amount_price_change($article_id);
    }
    /**
     * Method is used to bind to article amount price change.
     *
     * @param string $articleId
     */
    protected function on_article_amount_price_change($article_id)
    {
    }
}