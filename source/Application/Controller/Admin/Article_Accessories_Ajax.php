<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Exception\Database_Connection_Exception;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Model\List_Model;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class controls article assignment to accessories
 */
class Article_Accessories_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_bl_allow_ext_columns = true;
    /**
     * Container ID
     *
     * @var string
     */
    private $container_id;
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxartnum', 'oxarticles', 1, 0, 0],
        ['oxtitle', 'oxarticles', 1, 1, 0],
        ['oxean', 'oxarticles', 1, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1],
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxsort', 'oxaccessoire2article', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxaccessoire2article', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function get_query()
    {
        $my_config = Registry::get_config();
        $oxid_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $synch_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $db = Database_Provider::get_db();
        $this->container_id = Registry::get_request()->get_request_escaped_parameter('cmpid');
        $article_table = $this->get_view_name('oxarticles');
        $object2category_table = $this->get_view_name('oxobject2category');
        // category selected or not ?
        if (!$oxid_id) {
            $output_query = " from {$article_table} where 1 ";
            $output_query .= $my_config->get_config_param('blVariantsSelection') ? '' : " and {$article_table}.oxparentid = '' ";
        } else if ($synch_id && $oxid_id != $synch_id) {
            $bl_variants_selection_parameter = $my_config->get_config_param('blVariantsSelection');
            $true_response = " ( {$article_table}.oxid={$object2category_table}.oxobjectid " . "or {$article_table}.oxparentid={$object2category_table}.oxobjectid )";
            $fail_response = " {$article_table}.oxid={$object2category_table}.oxobjectid ";
            $variant_selection_sql = $bl_variants_selection_parameter ? $true_response : $fail_response;
            $output_query = " from {$object2category_table} left join {$article_table} on {$variant_selection_sql}" . " where {$object2category_table}.oxcatnid = " . $db->quote($oxid_id) . ' ';
        } else {
            $output_query = " from oxaccessoire2article left join {$article_table} " . "on oxaccessoire2article.oxobjectid={$article_table}.oxid " . ' where oxaccessoire2article.oxarticlenid = ' . $db->quote($oxid_id) . ' ';
        }
        if ($synch_id && $synch_id != $oxid_id) {
            // performance
            $sub_select = ' select oxaccessoire2article.oxobjectid from oxaccessoire2article ';
            $sub_select .= ' where oxaccessoire2article.oxarticlenid = ' . $db->quote($synch_id) . ' ';
            $output_query .= " and {$article_table}.oxid not in ( {$sub_select} )";
        }
        // skipping self from list
        $s_id = $synch_id ?: $oxid_id;
        // creating AJAX component
        return $output_query . (" and {$article_table}.oxid != " . $db->quote($s_id) . ' ');
    }
    /**
     * overide default sorting and replace it with OXSORT field
     *
     * @return string
     */
    protected function get_sorting()
    {
        if ($this->container_id == 'container2') {
            return ' order by _2,_0';
        }
        return ' order by _' . $this->get_sort_col() . ' ' . $this->get_sort_dir() . ' ';
    }
    /**
     * Removing article form accessories article list
     */
    public function remove_article_acc(): void
    {
        $a_chosen_art = $this->get_action_ids('oxaccessoire2article.oxid');
        // removing all
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = $this->add_filter('delete oxaccessoire2article.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_chosen_articles = implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art));
            $s_q = "delete from oxaccessoire2article where oxaccessoire2article.oxid in ({$s_chosen_articles}) ";
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adding article to accessories article list
     */
    public function add_article_acc(): void
    {
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $a_chosen_art = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_chosen_art = $this->get_all(parent::add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        if ($o_article->load($sox_id) && $sox_id && $sox_id != '-1' && is_array($a_chosen_art)) {
            foreach ($a_chosen_art as $s_chosen_art) {
                $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new_group->init('oxaccessoire2article');
                $o_new_group->oxaccessoire2article__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_art);
                $o_new_group->oxaccessoire2article__oxarticlenid = new \Oxid_Esales\Eshop\Core\Field($o_article->oxarticles__oxid->value);
                $o_new_group->oxaccessoire2article__oxsort = new \Oxid_Esales\Eshop\Core\Field(0);
                $o_new_group->save();
            }
            $this->on_article_accessory_relation_change($o_article);
        }
    }
    /**
     * Method is used to bind to accessory addition to article action.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     */
    protected function on_article_accessory_relation_change($article)
    {
    }
    /**
     * Applies sorting for Accessories list
     */
    public function sort_accessories_list(): void
    {
        $oxid_relation_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $selected_id_for_sort = Registry::get_request()->get_request_escaped_parameter('sortoxid');
        $sort_direction = Registry::get_request()->get_request_escaped_parameter('direction');
        $accessories_list = ox_new(List_Model::class);
        $accessories_list->init('oxbase', 'oxaccessoire2article');
        $sort_query = 'select * from  oxaccessoire2article where OXARTICLENID = :OXARTICLENID order by oxsort,oxid';
        $accessories_list->select_string($sort_query, ['OXARTICLENID' => $oxid_relation_id]);
        $rebuild_list = $this->rebuild_accessories_sort_indexes($accessories_list);
        if (($selected_position = array_search($selected_id_for_sort, $rebuild_list)) !== false) {
            $selected_sort_record = $accessories_list->offsetGet($rebuild_list[$selected_position]);
            $current_position = $selected_sort_record->oxaccessoire2article__oxsort->value;
            // get current selected row sort position
            if ($sort_direction == 'up' && $current_position > 0 || $sort_direction == 'down' && $current_position < count($rebuild_list) - 1) {
                $new_position = $sort_direction == 'up' ? $current_position - 1 : $current_position + 1;
                // exchanging indexes
                $current_record = $accessories_list->offsetGet($rebuild_list[$current_position]);
                $new_record = $accessories_list->offsetGet($rebuild_list[$new_position]);
                $current_record->oxaccessoire2article__oxsort = new Field($new_position);
                $new_record->oxaccessoire2article__oxsort = new Field($current_position);
                $current_record->save();
                $new_record->save();
            }
        }
        $output_query = $this->get_query();
        $normal_query = 'select ' . $this->get_query_cols() . $output_query;
        $count_query = 'select count( * ) ' . $output_query;
        $this->output_response($this->get_data($count_query, $normal_query));
    }
    /**
     * rebuild Accessories sort indexes
     *
     *
     */
    private function rebuild_accessories_sort_indexes(List_Model $input_list): array
    {
        $counter = 0;
        $output_list = [];
        foreach ($input_list as $key => $value) {
            if (isset($value->oxaccessoire2article__oxsort)) {
                if ($value->oxaccessoire2article__oxsort->value != $counter) {
                    $value->oxaccessoire2article__oxsort = new Field($counter);
                    $value->save();
                }
            }
            $output_list[$counter] = $key;
            $counter++;
        }
        return $output_list;
    }
}