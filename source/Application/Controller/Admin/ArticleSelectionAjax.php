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
 * Class controls article assignment to selection lists
 */
class Article_Selection_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table, visible, multilanguage, ident
        ['oxtitle', 'oxselectlist', 1, 1, 0],
        ['oxident', 'oxselectlist', 1, 0, 0],
        ['oxvaldesc', 'oxselectlist', 1, 0, 0],
        ['oxid', 'oxselectlist', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxselectlist', 1, 1, 0], ['oxident', 'oxselectlist', 1, 0, 0], ['oxvaldesc', 'oxselectlist', 1, 0, 0], ['oxid', 'oxobject2selectlist', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $s_sl_view_name = $this->get_view_name('oxselectlist');
        $s_art_view_name = $this->get_view_name('oxarticles');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_art_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_art_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $s_oxid = $s_art_id ?: $s_synch_art_id;
        $s_q = "select oxparentid from {$s_art_view_name} where oxid = :oxid and oxparentid != '' ";
        $s_q .= 'and (select count(oxobjectid) from oxobject2selectlist ' . 'where oxobjectid = :oxobjectid) = 0';
        // We force reading from master to prevent issues with slow replications or open transactions
        // (see ESDEV-3804 and ESDEV-3822).
        $s_parent_id = \Oxid_Esales\Eshop\Core\Database_Provider::get_master()->get_one($s_q, ['oxid' => $s_oxid, 'oxobjectid' => $s_oxid]);
        // all selectlists article is in
        $s_q_add = " from oxobject2selectlist left join {$s_sl_view_name} " . "on {$s_sl_view_name}.oxid=oxobject2selectlist.oxselnid  " . 'where oxobject2selectlist.oxobjectid = ' . $o_db->quote($s_oxid) . ' ';
        if ($s_parent_id) {
            $s_q_add .= 'or oxobject2selectlist.oxobjectid = ' . $o_db->quote($s_parent_id) . ' ';
        }
        // all not assigned selectlists
        if ($s_synch_art_id) {
            return " from {$s_sl_view_name}  " . "where {$s_sl_view_name}.oxid not in ( select oxobject2selectlist.oxselnid {$s_q_add} ) ";
        }
        return $s_q_add;
    }
    /**
     * Removes article selection lists.
     */
    public function remove_sel(): void
    {
        $a_chosen_art = $this->get_action_ids('oxobject2selectlist.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxobject2selectlist.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_chosen_articles = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art));
            $s_q = 'delete from oxobject2selectlist ' . 'where oxobject2selectlist.oxid in (' . $s_chosen_articles . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
        $article_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $this->on_article_selection_list_change($article_id);
    }
    /**
     * Adds selection lists to article.
     *
     * @throws Exception
     */
    public function add_sel(): void
    {
        $a_add_sel = $this->get_action_ids('oxselectlist.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_sl_view_name = $this->get_view_name('oxselectlist');
            $a_add_sel = $this->get_all($this->add_filter("select {$s_sl_view_name}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_add_sel)) {
            // We force reading from master to prevent issues with slow replications or open transactions
            // (see ESDEV-3804).
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            foreach ($a_add_sel as $s_add) {
                $o_new = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new->init('oxobject2selectlist');
                $s_object_id_field = 'oxobject2selectlist__oxobjectid';
                $s_selectetion_id_field = 'oxobject2selectlist__oxselnid';
                $s_ox_sort_field = 'oxobject2selectlist__oxsort';
                $o_new->{$s_object_id_field} = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_new->{$s_selectetion_id_field} = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $s_sql = 'select max(oxsort) + 1 from oxobject2selectlist where oxobjectid = :oxobjectid';
                $o_new->{$s_ox_sort_field} = new \Oxid_Esales\Eshop\Core\Field((int) $database->get_one($s_sql, ['oxobjectid' => $sox_id]));
                $o_new->save();
            }
            $this->on_article_selection_list_change($sox_id);
        }
    }
    /**
     * Method is used to bind to article selection list change.
     *
     * @param string $articleId
     */
    protected function on_article_selection_list_change($article_id)
    {
    }
}