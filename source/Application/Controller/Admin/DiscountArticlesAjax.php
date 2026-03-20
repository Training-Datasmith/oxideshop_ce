<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages discount articles
 */
class Discount_Articles_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    public const NEW_DISCOUNT_LIST_ID = '-1';
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_bl_allow_ext_columns = true;
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = [
        // field , table, visible, multilanguage, id
        'container1' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxarticles', 0, 0, 1]],
        'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxobject2discount', 0, 0, 1]],
    ];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_article_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_oxid = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_oxid = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_oxid && $s_synch_oxid) {
            $s_q_add = " from {$s_article_table} where 1 ";
            $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? '' : "and {$s_article_table}.oxparentid = '' ";
        } else if ($s_synch_oxid && $s_oxid != $s_synch_oxid) {
            $s_q_add = " from {$s_o2c_view} left join {$s_article_table} on ";
            $s_q_add .= $o_config->get_config_param('blVariantsSelection') ? "({$s_article_table}.oxid={$s_o2c_view}.oxobjectid or {$s_article_table}.oxparentid={$s_o2c_view}.oxobjectid)" : " {$s_article_table}.oxid={$s_o2c_view}.oxobjectid ";
            $s_q_add .= " where {$s_o2c_view}.oxcatnid = " . $o_db->quote($s_oxid) . " and {$s_article_table}.oxid is not null ";
            // resetting
            $s_id = null;
        } else {
            $s_q_add = " from oxobject2discount, {$s_article_table} where {$s_article_table}.oxid=oxobject2discount.oxobjectid ";
            $s_q_add .= ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_oxid) . " and oxobject2discount.oxtype = 'oxarticles' ";
        }
        if ($s_synch_oxid && $s_synch_oxid != $s_oxid) {
            // performance
            $s_sub_select = " select {$s_article_table}.oxid from oxobject2discount, {$s_article_table} where {$s_article_table}.oxid=oxobject2discount.oxobjectid ";
            $s_sub_select .= ' and oxobject2discount.oxdiscountid = ' . $o_db->quote($s_synch_oxid) . " and oxobject2discount.oxtype = 'oxarticles' ";
            if (stristr($s_q_add, 'where') === false) {
                $s_q_add .= ' where ';
            } else {
                $s_q_add .= ' and ';
            }
            $s_q_add .= " {$s_article_table}.oxid not in ( {$s_sub_select} ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes selected article (articles) from discount list
     */
    public function remove_disc_art(): void
    {
        $a_chosen_art = $this->get_action_ids('oxobject2discount.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = parent::add_filter('delete oxobject2discount.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_q = 'delete from oxobject2discount where oxobject2discount.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute($s_q);
        }
    }
    /**
     * Adds selected article (articles) to discount list
     */
    public function add_disc_art(): void
    {
        $article_ids = $this->get_action_ids('oxarticles.oxid');
        $discount_list_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $article_table = $this->get_view_name('oxarticles');
            $article_ids = $this->get_all(parent::add_filter("select {$article_table}.oxid " . $this->get_query()));
        }
        if ($discount_list_id && $discount_list_id != self::NEW_DISCOUNT_LIST_ID && is_array($article_ids)) {
            foreach ($article_ids as $article_id) {
                $this->add_article_to_discount($discount_list_id, $article_id);
            }
        }
    }
    /**
     * Adds article to discount list
     *
     * @param string $discountListId
     * @param string $articleId
     */
    protected function add_article_to_discount($discount_list_id, $article_id)
    {
        $object2Discount = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $object2Discount->init('oxobject2discount');
        $object2Discount->oxobject2discount__oxdiscountid = new \Oxid_Esales\Eshop\Core\Field($discount_list_id);
        $object2Discount->oxobject2discount__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($article_id);
        $object2Discount->oxobject2discount__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxarticles');
        $object2Discount->save();
    }
}