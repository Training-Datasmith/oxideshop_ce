<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article overview manager.
 * Collects and previews such article information as article creation date,
 * last modification date, sales rating and etc.
 * Admin Menu: Manage Products -> Articles -> Overview.
 */
class Article_Overview extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $config = Registry::get_config();
        parent::render();
        $this->_a_view_data['edit'] = $product = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $product_edit_id = $this->get_edit_object_id();
        if (isset($product_edit_id) && $product_edit_id != '-1') {
            $database = $this->get_database();
            $this->update_article($product, $product_edit_id);
            $shop_id = $config->get_shop_id();
            $query = $this->form_order_amount_query($product_edit_id);
            $this->_a_view_data['totalordercnt'] = $i_total_order_cnt = (float) $database->get_one($query);
            $query = $this->form_sold_out_amount_query($product_edit_id);
            $this->_a_view_data['soldcnt'] = $i_sold_cnt = (float) $database->get_one($query);
            $query = $this->form_canceled_amount_query($product_edit_id);
            $this->_a_view_data['canceledcnt'] = $i_canceled_cnt = (float) $database->get_one($query);
            $this->_a_view_data['leftordercnt'] = $i_total_order_cnt - $i_sold_cnt - $i_canceled_cnt;
            $query = 'select oxartid,sum(oxamount) as cnt from oxorderarticles ' . 'where oxordershopid = :oxordershopid group by oxartid order by cnt desc';
            $product_ids = $database->get_col($query, ['oxordershopid' => $shop_id]);
            $top_position = 0;
            $position = 0;
            foreach ($product_ids as $product_id) {
                $position++;
                if ($product_id == $product_edit_id) {
                    $top_position = $position;
                }
            }
            $this->_a_view_data['postopten'] = $top_position;
            $this->_a_view_data['toptentotal'] = $position;
        }
        $this->_a_view_data['afolder'] = $config->get_config_param('aProductfolder');
        $this->_a_view_data['aSubclass'] = $config->get_config_param('aArticleClasses');
        return 'article_overview';
    }
    /**
     * @return \OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface
     */
    protected function get_database()
    {
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
    }
    /**
     * Forms query to get total order count.
     *
     * @param string $oxId
     *
     * @return string
     */
    protected function form_order_amount_query($ox_id)
    {
        $query = 'select sum(oxamount) from oxorderarticles ';
        return $query . ('where oxartid=' . $this->get_database()->quote($ox_id));
    }
    /**
     * Forms query to get sold out amount count.
     *
     * @param string $oxId
     *
     * @return string
     */
    protected function form_sold_out_amount_query($ox_id)
    {
        return 'select sum(oxorderarticles.oxamount) from  oxorderarticles, oxorder ' . "where (oxorder.oxpaid>0 or oxorder.oxsenddate > 0) and oxorderarticles.oxstorno != '1' " . 'and oxorderarticles.oxartid=' . $this->get_database()->quote($ox_id) . 'and oxorder.oxid =oxorderarticles.oxorderid';
    }
    /**
     * Forms query to get canceled amount count.
     *
     * @param string $soxId
     *
     * @return string
     */
    protected function form_canceled_amount_query($sox_id)
    {
        return "select sum(oxamount) from oxorderarticles where oxstorno = '1' " . 'and oxartid=' . $this->get_database()->quote($sox_id);
    }
    /**
     * Loads language for article object.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     * @param string                                      $oxId
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function update_article($article, $ox_id)
    {
        $article->load_in_lang(Registry::get_request()->get_request_escaped_parameter('editlanguage'), $ox_id);
        return $article;
    }
}