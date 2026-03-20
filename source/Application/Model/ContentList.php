<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
/**
 * Content list manager.
 * Collects list of content
 */
class Content_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Information content type
     *
     * @var int
     */
    public const TYPE_INFORMATION_CONTENTS = 0;
    /**
     * Main menu list type
     *
     * @var int
     */
    public const TYPE_MAIN_MENU_LIST = 1;
    /**
     * Main menu list type
     *
     * @var int
     */
    public const TYPE_CATEGORY_MENU = 2;
    /**
     * Service list.
     *
     * @var int
     */
    public const TYPE_SERVICE_LIST = 3;
    /**
     * List of services.
     *
     * @var array
     */
    protected $_a_service_keys = ['oximpressum', 'oxagb', 'oxsecurityinfo', 'oxdeliveryinfo', 'oxrightofwithdrawal', 'oxorderinfo', 'oxcredits'];
    /**
     * Sets service keys.
     *
     * @param array $aServiceKeys
     */
    public function set_service_keys($a_service_keys): void
    {
        $this->_a_service_keys = $a_service_keys;
    }
    /**
     * Gets services keys.
     *
     * @return array
     */
    public function get_service_keys()
    {
        return $this->_a_service_keys;
    }
    /**
     * Class constructor, initiates parent constructor (parent::oxList()).
     */
    public function __construct()
    {
        parent::__construct('oxcontent');
    }
    /**
     * Loads main menue entries and generates list with links
     */
    public function load_main_menulist(): void
    {
        $this->load(self::TYPE_MAIN_MENU_LIST);
    }
    /**
     * Load Array of Menue items and change keys of aList to catid
     */
    public function load_cat_menues(): void
    {
        $this->load(self::TYPE_CATEGORY_MENU);
        $a_array = [];
        if ($this->count()) {
            foreach ($this as $o_content) {
                // add into category tree
                if (!isset($a_array[$o_content->get_category_id()])) {
                    $a_array[$o_content->get_category_id()] = [];
                }
                $a_array[$o_content->oxcontents__oxcatid->value][] = $o_content;
            }
        }
        $this->_a_array = $a_array;
    }
    /**
     * Get data from db
     *
     * @param integer $iType - type of content
     *
     * @return array
     */
    protected function load_from_db($i_type)
    {
        $s_sql = $this->get_sql_by_type($i_type);
        return Database_Provider::get_db()->get_all($s_sql);
    }
    /**
     * Load category list data
     *
     * @param integer $type - type of content
     */
    protected function load($type)
    {
        $data = $this->load_from_db($type);
        $this->assign_array($data);
    }
    /**
     * Load category list data.
     */
    public function load_services(): void
    {
        $this->load(self::TYPE_SERVICE_LIST);
        $this->extract_list_to_array();
    }
    /**
     * Extract oxContentList object to associative array with oxloadid as keys.
     */
    protected function extract_list_to_array()
    {
        $a_extracted_contents = [];
        foreach ($this as $o_content) {
            $a_extracted_contents[$o_content->get_load_id()] = $o_content;
        }
        $this->_a_array = $a_extracted_contents;
    }
    /**
     * Creates SQL by type.
     *
     * @param integer $iType type.
     *
     * @return string
     */
    protected function get_sql_by_type($i_type)
    {
        $s_sql_add = '';
        $o_db = Database_Provider::get_db();
        $s_sql_type = ' AND `oxtype` = ' . $o_db->quote($i_type);
        if ($i_type == self::TYPE_CATEGORY_MENU) {
            $s_sql_add = " AND `oxcatid` IS NOT NULL AND `oxsnippet` = '0'";
        }
        if ($i_type == self::TYPE_SERVICE_LIST) {
            $s_idents = implode(', ', Database_Provider::get_db()->quote_array($this->get_service_keys()));
            $s_sql_add = ' AND OXLOADID IN (' . $s_idents . ')';
            $s_sql_type = '';
        }
        $s_view_name = $this->get_base_object()->get_view_name();
        return "SELECT * FROM {$s_view_name} WHERE `oxactive` = '1' {$s_sql_type} AND `oxshopid` = " . $o_db->quote($this->_s_shop_id) . " {$s_sql_add} ORDER BY `oxloadid`";
    }
}