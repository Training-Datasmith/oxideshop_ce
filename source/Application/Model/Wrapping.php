<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Wrapping manager.
 * Performs Wrapping data/objects loading, deleting.
 */
class Wrapping extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Class name
     *
     * @var string name of current class
     */
    protected $_s_class_name = 'oxwrapping';
    /**
     * Wrapping oxprice object.
     *
     * @var \OxidEsales\Eshop\Core\Price
     */
    protected $_o_price;
    /**
     * Wrapping Vat
     *
     * @var double
     */
    protected $_d_vat = 0;
    /**
     * Wrapping VAT config
     *
     * @var bool
     */
    protected $_bl_wrapping_vat_on_top = false;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()), loads
     * base shop objects.
     */
    public function __construct()
    {
        $o_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $this->set_wrapping_vat($o_config->get_config_param('dDefaultVAT'));
        $this->set_wrapping_vat_on_top($o_config->get_config_param('blWrappingVatOnTop'));
        parent::__construct();
        $this->init('oxwrapping');
    }
    /**
     * Wrapping Vat setter
     *
     * @param double $dVat vat
     */
    public function set_wrapping_vat($d_vat): void
    {
        $this->_d_vat = $d_vat;
    }
    /**
     * Wrapping VAT config setter
     *
     * @param bool $blOnTop wrapping vat config
     */
    public function set_wrapping_vat_on_top($bl_on_top): void
    {
        $this->_bl_wrapping_vat_on_top = $bl_on_top;
    }
    /**
     * Returns oxprice object for wrapping
     *
     * @param int $dAmount article amount
     *
     * @return \OxidEsales\Eshop\Core\Price
     */
    public function get_wrapping_price($d_amount = 1)
    {
        if ($this->_o_price === null) {
            $this->_o_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
            if (!$this->_bl_wrapping_vat_on_top) {
                $this->_o_price->set_brutto_price_mode();
            } else {
                $this->_o_price->set_netto_price_mode();
            }
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $this->_o_price->set_price($this->oxwrapping__oxprice->value * $o_cur->rate, $this->_d_vat);
            $this->_o_price->multiply($d_amount);
        }
        return $this->_o_price;
    }
    /**
     * Loads wrapping list for specific wrap type
     *
     * @param string $sWrapType wrap type
     *
     * @return array $oEntries wrapping list
     */
    public function get_wrapping_list($s_wrap_type)
    {
        // load wrapping
        $o_entries = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_entries->init('oxwrapping');
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_wrapping_view_name = $table_view_name_generator->get_view_name('oxwrapping');
        $s_select = "select * from {$s_wrapping_view_name} \n            where {$s_wrapping_view_name}.oxactive = :oxactive\n              and {$s_wrapping_view_name}.oxtype = :oxtype";
        $o_entries->select_string($s_select, ['oxactive' => '1', 'oxtype' => $s_wrap_type]);
        return $o_entries;
    }
    /**
     * Counts amount of wrapping/card options
     *
     * @param string $sWrapType type - wrapping paper (WRAP) or card (CARD)
     *
     * @return int
     */
    public function get_wrapping_count($s_wrap_type)
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_wrapping_view_name = $table_view_name_generator->get_view_name('oxwrapping');
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = "select count(*) from {$s_wrapping_view_name} \n            where {$s_wrapping_view_name}.oxactive = :oxactive \n              and {$s_wrapping_view_name}.oxtype = :oxtype";
        return (int) $o_db->get_one($s_q, ['oxactive' => '1', 'oxtype' => $s_wrap_type]);
    }
    /**
     * Checks and return true if price view mode is netto
     *
     * @return bool
     */
    protected function is_price_view_mode_netto()
    {
        $bl_result = (bool) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowNetPrice');
        $o_user = $this->get_user();
        if ($o_user) {
            return $o_user->is_price_view_mode_netto();
        }
        return $bl_result;
    }
    /**
     * Returns formatted wrapping price
     *
     * @deprecated since v5.1 (2013-10-13); use oxPrice template engine plugin for formatting in templates
     *
     * @return string
     */
    public function get_f_price()
    {
        $d_price = $this->get_price();
        return \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($d_price, \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object());
    }
    /**
     * Gets price.
     *
     * @return double
     */
    public function get_price()
    {
        if ($this->is_price_view_mode_netto()) {
            return $this->get_wrapping_price()->get_netto_price();
        }
        return $this->get_wrapping_price()->get_brutto_price();
    }
    /**
     * Returns returns dyn image dir (not ssl)
     *
     * @return string
     */
    public function get_no_ssl_dyn_image_dir()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_picture_url(null, false, false, null, $this->oxwrapping__oxshopid->value);
    }
    /**
     * Returns returns dyn image dir
     *
     * @return string
     */
    public function get_picture_url()
    {
        if ($this->oxwrapping__oxpic->value) {
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_picture_url('master/wrapping/' . $this->oxwrapping__oxpic->value, false, \Oxid_Esales\Eshop\Core\Registry::get_config()->is_ssl(), null, $this->oxwrapping__oxshopid->value);
        }
    }
}