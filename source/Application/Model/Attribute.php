<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Article attributes manager.
 * Collects and keeps attributes of chosen article.
 */
class Attribute extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxattribute';
    /**
     * Selected attribute value
     *
     * @var string
     */
    protected $_s_active_value;
    /**
     * Attribute values
     *
     * @var array
     */
    protected $_a_values;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxattribute');
    }
    /**
     * Removes attributes from articles, returns true on success.
     *
     * @param string $sOXID Object ID
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (!$this->can_delete_attribute($s_oxid)) {
            return false;
        }
        // remove attributes from articles also
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delete = 'delete from oxobject2attribute where oxattrid = :oxattrid';
        $o_db->execute($s_delete, ['oxattrid' => $s_oxid]);
        // #657 ADDITIONAL removes attribute connection to category
        $s_delete = 'delete from oxcategory2attribute where oxattrid = :oxattrid';
        $o_db->execute($s_delete, ['oxattrid' => $s_oxid]);
        return parent::delete($s_oxid);
    }
    /**
     * Assigns attribute to variant
     *
     * @param array $aMDVariants article ids with selectionlist values
     * @param array $aSelTitle   selection list titles
     */
    public function assign_var_to_attribute($a_md_variants, $a_sel_title): void
    {
        $my_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $a_conf_languages = $my_lang->get_language_ids();
        $s_attr_id = $this->get_attr_id($a_sel_title[0]);
        if (!$s_attr_id) {
            $s_attr_id = $this->create_attribute($a_sel_title);
        }
        foreach ($a_md_variants as $s_var_id => $o_value) {
            if (str_starts_with((string) $s_var_id, 'mdvar_')) {
                foreach ($o_value as $s_id) {
                    $s_var_id = substr((string) $s_var_id, 6);
                    $o_new_assign = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
                    $o_new_assign->init('oxobject2attribute');
                    $s_new_id = \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_uid();
                    if ($o_new_assign->load($s_id)) {
                        $o_new_assign->oxobject2attribute__oxobjectid = new Field($s_var_id);
                        $o_new_assign->set_id($s_new_id);
                        $o_new_assign->save();
                    }
                }
            } else {
                $o_new_assign = ox_new(\Oxid_Esales\Eshop\Core\Model\Multi_Language_Model::class);
                $o_new_assign->set_enable_multilang(false);
                $o_new_assign->init('oxobject2attribute');
                $o_new_assign->oxobject2attribute__oxobjectid = new Field($s_var_id);
                $o_new_assign->oxobject2attribute__oxattrid = new Field($s_attr_id);
                foreach ($a_conf_languages as $s_key => $s_lang) {
                    $s_prefix = $my_lang->get_language_tag($s_key);
                    $o_new_assign->{'oxobject2attribute__oxvalue' . $s_prefix} = new Field($o_value[$s_key]->name);
                }
                $o_new_assign->save();
            }
        }
    }
    /**
     * Searches for attribute by oxtitle. If exists returns attribute id
     *
     * @param string $sSelTitle selection list title
     *
     * @return mixed attribute id or false
     */
    protected function get_attr_id($s_sel_title)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_att_view_name = $table_view_name_generator->get_view_name('oxattribute');
        return $o_db->get_one("select oxid from {$s_att_view_name} where LOWER(oxtitle) = :oxtitle ", ['oxtitle' => Str::get_str()->strtolower($s_sel_title)]);
    }
    /**
     * Checks if attribute exists
     *
     * @param array $aSelTitle selection list title
     *
     * @return string attribute id
     */
    protected function create_attribute($a_sel_title)
    {
        $my_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $a_conf_languages = $my_lang->get_language_ids();
        $o_attr = ox_new(\Oxid_Esales\Eshop\Core\Model\Multi_Language_Model::class);
        $o_attr->set_enable_multilang(false);
        $o_attr->init('oxattribute');
        foreach ($a_conf_languages as $s_key => $s_lang) {
            $s_prefix = $my_lang->get_language_tag($s_key);
            $o_attr->{'oxattribute__oxtitle' . $s_prefix} = new Field($a_sel_title[$s_key]);
        }
        $o_attr->save();
        return $o_attr->get_id();
    }
    /**
     * Returns all oxobject2attribute Ids of article
     *
     * @param string $sArtId article ids
     */
    public function get_attribute_assigns($s_art_id)
    {
        if ($s_art_id) {
            $s_select = 'select o2a.oxid from oxobject2attribute as o2a ' . 'where o2a.oxobjectid = :oxobjectid order by o2a.oxpos';
            return Database_Provider::get_db()->get_col($s_select, ['oxobjectid' => $s_art_id]);
        }
    }
    /**
     * Set attribute title
     *
     * @param string $sTitle - attribute title
     */
    public function set_title($s_title): void
    {
        $this->set_field_data('oxtitle', Str::get_str()->htmlspecialchars($s_title));
    }
    /**
     * Get attribute Title
     *
     * @return String
     */
    public function get_title()
    {
        return $this->get_field_data('oxtitle');
    }
    /**
     * Add attribute value
     *
     * @param string $sValue - attribute value
     */
    public function add_value($s_value): void
    {
        $this->_a_values[] = Str::get_str()->htmlspecialchars($s_value);
    }
    /**
     * Set attribute selected value
     *
     * @param string $sValue - attribute value
     */
    public function set_active_value($s_value): void
    {
        $this->_s_active_value = Str::get_str()->htmlspecialchars($s_value);
    }
    /**
     * Get attribute Selected value
     *
     * @return String
     */
    public function get_active_value()
    {
        return $this->_s_active_value;
    }
    /**
     * Get attribute values
     *
     * @return Array
     */
    public function get_values()
    {
        return $this->_a_values;
    }
    /**
     * Checks if possible to delete attribute.
     *
     * @param string $oxId
     *
     * @return bool
     */
    protected function can_delete_attribute($ox_id)
    {
        if (!$ox_id) {
            return false;
        }
        return true;
    }
}