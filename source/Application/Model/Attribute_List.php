<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Attribute list manager.
 */
class Attribute_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('oxattribute');
    }
    /**
     * Load all attributes by article Id's
     *
     * @param array $aIds article id's
     *
     * @return array $aAttributes;
     */
    public function load_attributes_by_ids($a_ids)
    {
        if (!count($a_ids)) {
            return;
        }
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_attr_view_name = $table_view_name_generator->get_view_name('oxattribute');
        $s_view_name = $table_view_name_generator->get_view_name('oxobject2attribute');
        $ox_object_ids_sql = implode(',', Database_Provider::get_db()->quote_array($a_ids));
        $s_select = "select {$s_attr_view_name}.oxid, {$s_attr_view_name}.oxtitle, {$s_view_name}.oxvalue, {$s_view_name}.oxobjectid ";
        $s_select .= "from {$s_view_name} left join {$s_attr_view_name} on {$s_attr_view_name}.oxid = {$s_view_name}.oxattrid ";
        $s_select .= "where {$s_view_name}.oxobjectid in ( " . $ox_object_ids_sql . ' ) ';
        $s_select .= "order by {$s_view_name}.oxpos, {$s_attr_view_name}.oxpos";
        return $this->create_attribute_list_from_sql($s_select);
    }
    /**
     * Fills array with keys and products with value
     *
     * @param string $sSelect SQL select
     *
     * @return array $aAttributes
     */
    protected function create_attribute_list_from_sql($select)
    {
        $attributes = [];
        $result = Database_Provider::get_db()->select($select);
        if ($result != false && $result->count() > 0) {
            while (!$result->EOF) {
                $fields = array_values($result->fields);
                if (!isset($attributes[$fields[0]])) {
                    $attributes[$fields[0]] = new stdClass();
                }
                $attributes[$fields[0]]->title = $fields[1];
                if (!isset($attributes[$fields[0]]->a_prod[$fields[3]])) {
                    $attributes[$fields[0]]->a_prod[$fields[3]] = new stdClass();
                }
                $attributes[$fields[0]]->a_prod[$fields[3]]->value = $fields[2];
                $result->fetch_row();
            }
        }
        return $attributes;
    }
    /**
     * Load attributes by article Id
     *
     * @param string $sArticleId article id
     * @param string $sParentId  article parent id
     */
    public function load_attributes($s_article_id, $s_parent_id = null): void
    {
        if ($s_article_id) {
            $o_db = Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_attr_view_name = $table_view_name_generator->get_view_name('oxattribute');
            $s_view_name = $table_view_name_generator->get_view_name('oxobject2attribute');
            $s_select = "select {$s_attr_view_name}.`oxid`, {$s_attr_view_name}.`oxtitle`, o2a.`oxvalue` from {$s_view_name} as o2a ";
            $s_select .= "left join {$s_attr_view_name} on {$s_attr_view_name}.oxid = o2a.oxattrid ";
            $s_select .= "where o2a.oxobjectid = :oxobjectid and o2a.oxvalue != '' ";
            $s_select .= "order by o2a.oxpos, {$s_attr_view_name}.oxpos";
            $a_attributes = $o_db->get_all($s_select, ['oxobjectid' => $s_article_id]);
            if ($s_parent_id) {
                $a_parent_attributes = $o_db->get_all($s_select, ['oxobjectid' => $s_parent_id]);
                $a_attributes = $this->merge_attributes($a_attributes, $a_parent_attributes);
            }
            $this->assign_array($a_attributes);
        }
    }
    /**
     * Load displayable in baskte/order attributes by article Id
     *
     * @param string $sArtId    article ids
     * @param string $sParentId parent id
     */
    public function load_attributes_displayable_in_basket($s_art_id, $s_parent_id = null): void
    {
        if ($s_art_id) {
            $o_db = Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_attr_view_name = $table_view_name_generator->get_view_name('oxattribute');
            $s_view_name = $table_view_name_generator->get_view_name('oxobject2attribute');
            $s_select = "select o2a.*, {$s_attr_view_name}.* from {$s_view_name} as o2a ";
            $s_select .= "left join {$s_attr_view_name} on {$s_attr_view_name}.oxid = o2a.oxattrid ";
            $s_select .= "where o2a.oxobjectid = :oxobjectid and {$s_attr_view_name}.oxdisplayinbasket  = 1 and o2a.oxvalue != '' ";
            $s_select .= "order by o2a.oxpos, {$s_attr_view_name}.oxpos";
            $a_attributes = $o_db->get_all($s_select, ['oxobjectid' => $s_art_id]);
            if ($s_parent_id) {
                $a_parent_attributes = $o_db->get_all($s_select, ['oxobjectid' => $s_parent_id]);
                $a_attributes = $this->merge_attributes($a_attributes, $a_parent_attributes);
            }
            $this->assign_array($a_attributes);
        }
    }
    /**
     * get category attributes by category Id
     *
     * @param string  $sCategoryId category Id
     * @param integer $iLang       language No
     *
     * @return object
     */
    public function get_category_attributes($s_category_id, $i_lang)
    {
        $a_session_filter = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('session_attrfilter');
        $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_art_list->load_category_i_ds($s_category_id, $a_session_filter);
        // Only if we have articles
        if (count($o_art_list) > 0) {
            $o_db = Database_Provider::get_db();
            $s_art_ids = '';
            foreach (array_keys($o_art_list->get_array()) as $s_id) {
                if ($s_art_ids) {
                    $s_art_ids .= ',';
                }
                $s_art_ids .= $o_db->quote($s_id);
            }
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_att_tbl = $table_view_name_generator->get_view_name('oxattribute', $i_lang);
            $s_o2a_tbl = $table_view_name_generator->get_view_name('oxobject2attribute', $i_lang);
            $s_c2a_tbl = $table_view_name_generator->get_view_name('oxcategory2attribute', $i_lang);
            $s_select = 'SELECT DISTINCT att.oxid as oxid, att.oxtitle as oxtitle, o2a.oxvalue as oxvalue' . " FROM {$s_att_tbl} as att, {$s_o2a_tbl} as o2a ,{$s_c2a_tbl} as c2a" . ' WHERE att.oxid = o2a.oxattrid AND c2a.oxobjectid = :oxobjectid AND c2a.oxattrid = att.oxid' . " AND o2a.oxvalue !='' AND o2a.oxobjectid IN ({$s_art_ids})" . ' ORDER BY c2a.oxsort , att.oxpos, att.oxtitle, o2a.oxvalue';
            $rs = $o_db->select($s_select, ['oxobjectid' => $s_category_id]);
            if ($rs != false && $rs->count() > 0) {
                while (!$rs->EOF && [$s_att_id, $s_att_title, $s_att_value] = array_values($rs->fields)) {
                    if (!$this->offsetExists($s_att_id)) {
                        $o_attribute = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
                        $o_attribute->set_title($s_att_title);
                        $this->offsetSet($s_att_id, $o_attribute);
                        $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
                        if (isset($a_session_filter[$s_category_id][$i_lang][$s_att_id])) {
                            $o_attribute->set_active_value($a_session_filter[$s_category_id][$i_lang][$s_att_id]);
                        }
                    } else {
                        $o_attribute = $this->offsetGet($s_att_id);
                    }
                    $o_attribute->add_value($s_att_value);
                    $rs->fetch_row();
                }
            }
        }
        return $this;
    }
    /**
     * Merge attribute arrays
     *
     * @param array $aAttributes       array of attributes
     * @param array $aParentAttributes array of parent article attributes
     *
     * @return array $aAttributes
     */
    protected function merge_attributes($a_attributes, $a_parent_attributes)
    {
        if (count($a_parent_attributes)) {
            $a_attr_ids = [];
            foreach ($a_attributes as $a_attribute) {
                $a_attr_ids[] = $a_attribute['OXID'];
            }
            foreach ($a_parent_attributes as $a_attribute) {
                if (!in_array($a_attribute['OXID'], $a_attr_ids)) {
                    $a_attributes[] = $a_attribute;
                }
            }
        }
        return $a_attributes;
    }
}