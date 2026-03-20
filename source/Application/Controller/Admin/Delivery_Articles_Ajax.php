<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages delivery articles
 */
class Delivery_Articles_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
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
    ], 'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxobject2delivery', 0, 0, 1]]];
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_bl_allow_ext_columns = true;
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $request = \Oxid_Esales\Eshop\Core\Registry::get_request();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        // looking for table/view
        $s_art_table = $this->get_view_name('oxarticles');
        $s_o2c_view = $this->get_view_name('oxobject2category');
        $s_del_id = $request->get_request_parameter('oxid');
        $s_synch_del_id = $request->get_request_parameter('synchoxid');
        // category selected or not ?
        if (!$s_del_id) {
            // performance
            $s_q_add = " from {$s_art_table} where 1 ";
            $s_q_add .= $config->get_config_param('blVariantsSelection') ? '' : "and {$s_art_table}.oxparentid = '' ";
        } else if ($s_synch_del_id && $s_del_id != $s_synch_del_id) {
            $s_q_add = " from {$s_o2c_view} left join {$s_art_table} on ";
            $s_q_add .= $config->get_config_param('blVariantsSelection') ? " ( {$s_art_table}.oxid={$s_o2c_view}.oxobjectid or {$s_art_table}.oxparentid={$s_o2c_view}.oxobjectid)" : " {$s_art_table}.oxid={$s_o2c_view}.oxobjectid ";
            $s_q_add .= "where {$s_o2c_view}.oxcatnid = " . $o_db->quote($s_del_id);
        } else {
            $s_q_add = ' from oxobject2delivery left join ' . $s_art_table . ' on ' . $s_art_table . '.oxid=oxobject2delivery.oxobjectid ';
            $s_q_add .= 'where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_del_id) . ' and oxobject2delivery.oxtype = "oxarticles" ';
        }
        if ($s_synch_del_id && $s_synch_del_id != $s_del_id) {
            $s_q_add .= 'and ' . $s_art_table . '.oxid not in ( ';
            $s_q_add .= 'select oxobject2delivery.oxobjectid from oxobject2delivery ';
            $s_q_add .= 'where oxobject2delivery.oxdeliveryid = ' . $o_db->quote($s_synch_del_id) . ' and oxobject2delivery.oxtype = "oxarticles" ) ';
        }
        return $s_q_add;
    }
    /**
     * Removes article from delivery configuration
     */
    public function remove_art_from_del(): void
    {
        $a_chosen_art = $this->get_action_ids('oxobject2delivery.oxid');
        // removing all
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_q = parent::add_filter('delete oxobject2delivery.* ' . $this->get_query());
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_q = 'delete from oxobject2delivery where oxobject2delivery.oxid in (' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_chosen_art)) . ') ';
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->Execute($s_q);
        }
    }
    /**
     * Adds article to delivery configuration
     */
    public function add_art_to_del(): void
    {
        $a_chosen_art = $this->get_action_ids('oxarticles.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // adding
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_art_table = $this->get_view_name('oxarticles');
            $a_chosen_art = $this->get_all($this->add_filter("select {$s_art_table}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_chosen_art)) {
            foreach ($a_chosen_art as $s_chosen_art) {
                $o_object2delivery = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_object2delivery->init('oxobject2delivery');
                $o_object2delivery->oxobject2delivery__oxdeliveryid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_object2delivery->oxobject2delivery__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_chosen_art);
                $o_object2delivery->oxobject2delivery__oxtype = new \Oxid_Esales\Eshop\Core\Field('oxarticles');
                $o_object2delivery->save();
            }
        }
    }
}