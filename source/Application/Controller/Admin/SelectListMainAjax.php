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
/**
 * Class manages article select lists configuration
 */
class Select_List_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
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
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxartnum', 'oxarticles', 1, 0, 0],
        ['oxtitle', 'oxarticles', 1, 1, 0],
        ['oxean', 'oxarticles', 0, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1],
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 0, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxobject2selectlist', 0, 0, 1], ['oxid', 'oxarticles', 0, 0, 1]], 'container3' => [['oxtitle', 'oxselectlist', 1, 1, 0], ['oxsort', 'oxobject2selectlist', 1, 0, 0], ['oxident', 'oxselectlist', 0, 0, 0], ['oxvaldesc', 'oxselectlist', 0, 0, 0], ['oxid', 'oxselectlist', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // looking for table/view
        $s_art_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_sel_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_sel_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // category selected or not ?
        if (!$s_sel_id) {
            // performance
            $s_q_add = " from {$s_art_table} where 1 ";
            $s_q_add .= $my_config->get_config_param('blVariantsSelection') ? '' : " and {$s_art_table}.oxparentid = '' ";
        } else if ($s_synch_sel_id && $s_sel_id != $s_synch_sel_id) {
            $s_q_add = " from {$s_o2c_view} as oxobject2category left join {$s_art_table} on " . $my_config->get_config_param('blVariantsSelection') ? " ( {$s_art_table}.oxid=oxobject2category.oxobjectid or" . " {$s_art_table}.oxparentid=oxobject2category.oxobjectid ) " : " {$s_art_table}.oxid=oxobject2category.oxobjectid " . ' where oxobject2category.oxcatnid = ' . $o_db->quote($s_sel_id);
        } else {
            $s_q_add = " from {$s_art_table} left join oxobject2selectlist on" . " {$s_art_table}.oxid=oxobject2selectlist.oxobjectid " . ' where oxobject2selectlist.oxselnid = ' . $o_db->quote($s_sel_id);
        }
        if ($s_synch_sel_id && $s_synch_sel_id != $s_sel_id) {
            // performance
            $s_q_add .= " and {$s_art_table}.oxid not in ( select oxobject2selectlist.oxobjectid from oxobject2selectlist " . ' where oxobject2selectlist.oxselnid = ' . $o_db->quote($s_synch_sel_id) . ' ) ';
        }
        return $s_q_add;
    }
    /**
     * Removes article from Selection list
     */
    public function remove_art_from_sel(): void
    {
        $a_chosen_art = $this->get_action_ids('oxobject2selectlist.oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = parent::add_filter('delete oxobject2selectlist.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_q = 'delete from oxobject2selectlist where oxobject2selectlist.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds article to Selection list
     *
     * @throws Exception
     */
    public function add_art_to_sel(): void
    {
        $a_add_article = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_add_article = $this->get_all(parent::add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_add_article)) {
            // We force reading from master to prevent issues with slow replications or open transactions
            // (see ESDEV-3804 and ESDEV-3822).
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            foreach ($a_add_article as $s_add) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new_group->init('oxobject2selectlist');
                $o_new_group->oxobject2selectlist__oxobjectid = new Field($s_add);
                $o_new_group->oxobject2selectlist__oxselnid = new Field($sox_id);
                $o_new_group->oxobject2selectlist__oxsort = new Field((int) $database->get_one('select max(oxsort) + 1 from oxobject2selectlist where oxobjectid = :oxobjectid', ['oxobjectid' => $s_add]));
                $o_new_group->save();
                $this->on_article_add_to_selection_list($s_add);
            }
        }
    }
    /**
     * Method is used for overriding.
     *
     * @param string $articleId
     */
    protected function on_article_add_to_selection_list($article_id)
    {
    }
}