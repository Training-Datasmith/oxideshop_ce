<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Class manages manufacturer assignment to articles
 */
class Manufacturer_Main_Ajax extends \Oxid_Esales\Eshop\Application\Controller\Admin\List_Component_Ajax
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
    protected $_a_columns = [
        // field , table, visible, multilanguage, id
        'container1' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxarticles', 0, 0, 1]],
        'container2' => [['oxartnum', 'oxarticles', 1, 0, 0], ['oxtitle', 'oxarticles', 1, 1, 0], ['oxean', 'oxarticles', 1, 0, 0], ['oxmpn', 'oxarticles', 0, 0, 0], ['oxprice', 'oxarticles', 0, 0, 0], ['oxstock', 'oxarticles', 0, 0, 0], ['oxid', 'oxarticles', 0, 0, 1]],
    ];
    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function get_query()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // looking for table/view
        $articles_view_name = $this->get_view_name('oxarticles');
        $object_to_category_view_name = $this->get_view_name('oxobject2category');
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $manufacturer_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        $synced_manufacturer_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        // Manufacturer selected or not ?
        if (!$manufacturer_id) {
            // performance
            $query = ' from ' . $articles_view_name . ' where ' . $articles_view_name . '.oxshopid="' . $config->get_shop_id() . '" and 1 ';
            $query .= $config->get_config_param('blVariantsSelection') ? '' : " and {$articles_view_name}.oxparentid = '' and {$articles_view_name}.oxmanufacturerid != " . $database->quote($synced_manufacturer_id);
        } elseif ($synced_manufacturer_id && $synced_manufacturer_id != $manufacturer_id) {
            // selected category ?
            $query = " from {$object_to_category_view_name} left join {$articles_view_name} on ";
            $query .= $config->get_config_param('blVariantsSelection') ? " ( {$articles_view_name}.oxid = {$object_to_category_view_name}.oxobjectid or {$articles_view_name}.oxparentid = {$object_to_category_view_name}.oxobjectid )" : " {$articles_view_name}.oxid = {$object_to_category_view_name}.oxobjectid ";
            $query .= 'where ' . $articles_view_name . '.oxshopid="' . $config->get_shop_id() . '" and ' . $object_to_category_view_name . '.oxcatnid = ' . $database->quote($manufacturer_id) . ' and ' . $articles_view_name . '.oxmanufacturerid != ' . $database->quote($synced_manufacturer_id);
            $query .= $config->get_config_param('blVariantsSelection') ? '' : " and {$articles_view_name}.oxparentid = '' ";
        } else {
            $query = " from {$articles_view_name} where {$articles_view_name}.oxmanufacturerid = " . $database->quote($manufacturer_id);
            $query .= $config->get_config_param('blVariantsSelection') ? '' : " and {$articles_view_name}.oxparentid = '' ";
        }
        return $query;
    }
    /**
     * Adds filter SQL to current query
     *
     * @param string $query query to add filter condition
     *
     * @return string
     */
    protected function add_filter($query)
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $article_view_name = $this->get_view_name('oxarticles');
        $query = parent::add_filter($query);
        // display variants or not ?
        $query .= $config->get_config_param('blVariantsSelection') ? ' group by ' . $article_view_name . '.oxid ' : '';
        return $query;
    }
    /**
     * Removes article from Manufacturer config
     */
    public function remove_manufacturer(): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $article_ids = $this->get_action_ids('oxarticles.oxid');
        $manufacturer_id = Registry::get_request()->get_request_escaped_parameter('oxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $article_view_table = $this->get_view_name('oxarticles');
            $article_ids = $this->get_all($this->add_filter("select {$article_view_table}.oxid " . $this->get_query()));
        }
        if (is_array($article_ids) && !empty($article_ids)) {
            $query = $this->form_manufacturer_removal_query($article_ids);
            \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute($query);
            $this->reset_counter('manufacturerArticle', $manufacturer_id);
        }
    }
    /**
     * Forms and returns query for manufacturers removal.
     *
     * @param array $articlesToRemove Ids of manufacturers which should be removed.
     *
     * @return string
     */
    protected function form_manufacturer_removal_query($articles_to_remove)
    {
        return '
          UPDATE oxarticles
          SET oxmanufacturerid = null
          WHERE oxid IN ( ' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($articles_to_remove)) . ') ';
    }
    /**
     * Adds article to Manufacturer config
     */
    public function add_manufacturer(): void
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        $article_ids = $this->get_action_ids('oxarticles.oxid');
        $manufacturer_id = Registry::get_request()->get_request_escaped_parameter('synchoxid');
        if (Registry::get_request()->get_request_escaped_parameter('all')) {
            $article_view_name = $this->get_view_name('oxarticles');
            $article_ids = $this->get_all($this->add_filter("select {$article_view_name}.oxid " . $this->get_query()));
        }
        if ($manufacturer_id && $manufacturer_id != '-1' && is_array($article_ids)) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $query = $this->form_article_to_manufacturer_addition_query($manufacturer_id, $article_ids);
            $database->execute($query);
            $this->reset_counter('manufacturerArticle', $manufacturer_id);
        }
    }
    /**
     * Forms and returns query for articles addition to manufacturer.
     *
     * @param string $manufacturerId Manufacturer id.
     * @param array  $articlesToAdd  Array of article ids to be added to manufacturer.
     *
     * @return string
     */
    protected function form_article_to_manufacturer_addition_query($manufacturer_id, $articles_to_add)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        return '
            UPDATE oxarticles
            SET oxmanufacturerid = ' . $database->quote($manufacturer_id) . '
            WHERE oxid IN ( ' . implode(', ', $database->quote_array($articles_to_add)) . ' )';
    }
}