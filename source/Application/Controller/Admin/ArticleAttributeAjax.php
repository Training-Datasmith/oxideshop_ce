<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class controls article assignment to attributes
 */
class Article_Attribute_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
{
    /**
     * Columns array
     *
     * @var array
     */
    protected $_a_columns = ['container1' => [
        // field , table,         visible, multilanguage, ident
        ['oxtitle', 'oxattribute', 1, 1, 0],
        ['oxid', 'oxattribute', 0, 0, 1],
    ], 'container2' => [['oxtitle', 'oxattribute', 1, 1, 0], ['oxid', 'oxobject2attribute', 0, 0, 1], ['oxvalue', 'oxobject2attribute', 0, 1, 1], ['oxattrid', 'oxobject2attribute', 0, 0, 1]]];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $o_db = Database_Provider::get_db();
        $s_art_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_synch_art_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        $s_attr_view_name = $this->get_view_name('oxattribute');
        $s_o2a_view_name = $this->get_view_name('oxobject2attribute');
        if ($s_art_id) {
            // all categories article is in
            return " from {$s_o2a_view_name} left join {$s_attr_view_name} " . "on {$s_attr_view_name}.oxid={$s_o2a_view_name}.oxattrid " . " where {$s_o2a_view_name}.oxobjectid = " . $o_db->quote($s_art_id) . ' ';
        }
        return " from {$s_attr_view_name} where {$s_attr_view_name}.oxid not in ( select {$s_o2a_view_name}.oxattrid " . "from {$s_o2a_view_name} left join {$s_attr_view_name} " . "on {$s_attr_view_name}.oxid={$s_o2a_view_name}.oxattrid " . " where {$s_o2a_view_name}.oxobjectid = " . $o_db->quote($s_synch_art_id) . ' ) ';
    }
    /**
     * Removes article attributes.
     */
    public function remove_attr(): void
    {
        $a_chosen_art = $this->get_action_ids('oxobject2attribute.oxid');
        $s_oxid = Registry::get_request()->get_request_escaped_parameter('oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_o2a_view_name = $this->get_view_name('oxobject2attribute');
            $s_q = $this->add_filter("delete {$s_o2a_view_name}.* " . $this->get_query());
            Database_Provider::get_db()->Execute($s_q);
        } elseif (is_array($a_chosen_art)) {
            $s_chosen_articles = implode(', ', Database_Provider::get_db()->quote_array($a_chosen_art));
            $s_q = "delete from oxobject2attribute where oxobject2attribute.oxid in ({$s_chosen_articles}) ";
            Database_Provider::get_db()->Execute($s_q);
        }
        $this->on_article_attribute_relation_change($s_oxid);
    }
    /**
     * Adds attributes to article.
     */
    public function add_attr(): void
    {
        $a_add_cat = $this->get_action_ids('oxattribute.oxid');
        $sox_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $s_attr_view_name = $this->get_view_name('oxattribute');
            $a_add_cat = $this->get_all($this->add_filter("select {$s_attr_view_name}.oxid " . $this->get_query()));
        }
        if ($sox_id && $sox_id != '-1' && is_array($a_add_cat)) {
            foreach ($a_add_cat as $s_add) {
                $o_new = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                $o_new->init('oxobject2attribute');
                $o_new->oxobject2attribute__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($sox_id);
                $o_new->oxobject2attribute__oxattrid = new \Oxid_Esales\Eshop\Core\Field($s_add);
                $o_new->save();
            }
            $this->on_article_attribute_relation_change($sox_id);
        }
    }
    /**
     * Saves attribute value
     */
    public function save_attribute_value(): void
    {
        $database = Database_Provider::get_db();
        $this->reset_content_cache();
        $article_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $attribute_id = Registry::get_request()->get_request_escaped_parameter('attr_oxid');
        $attribute_value = Registry::get_request()->get_request_escaped_parameter('attr_value');
        $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        if ($article->load($article_id)) {
            if ($article->is_derived()) {
                return;
            }
            $this->on_attribute_value_change($article);
            if (isset($attribute_id) && '' != $attribute_id) {
                $view_name = $this->get_view_name('oxobject2attribute');
                $quoted_article_id = $database->quote($article->oxarticles__oxid->value);
                $select = "select * from {$view_name} where {$view_name}.oxobjectid= {$quoted_article_id} and\n                            {$view_name}.oxattrid= " . $database->quote($attribute_id);
                $object_to_attribute = ox_new(\Oxid_Esales\Eshop\Core\Model\Multi_Language_Model::class);
                $object_to_attribute->set_language(Registry::get_request()->get_request_escaped_parameter('editlanguage'));
                $object_to_attribute->init('oxobject2attribute');
                $record = Database_Provider::get_db()->select($select);
                if ($record && $record->count() > 0) {
                    $object_to_attribute->assign($record->fields);
                    $object_to_attribute->oxobject2attribute__oxvalue->set_value($attribute_value);
                    $object_to_attribute->save();
                }
            }
        }
    }
    /**
     * Method is used to bind to attribute and article relation change action.
     *
     * @param string $articleId
     */
    protected function on_article_attribute_relation_change($article_id)
    {
    }
    /**
     * Method is used to bind to attribute value change.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     */
    protected function on_attribute_value_change($article)
    {
    }
}