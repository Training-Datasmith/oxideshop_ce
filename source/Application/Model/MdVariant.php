<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Defines an element of multidimentional variant name tree structure. Contains article id, variant name, URL, price, price text, and a subset of MD variants.
 */
class Md_Variant extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * MD variant identifier
     *
     * @var string
     */
    protected $_s_id;
    /**
     * Parent ID
     *
     * @var string
     */
    protected $_s_parent_id;
    /**
     * Corresponding article id
     *
     * @var string
     */
    protected $_s_article_id;
    /**
     * Variant name
     *
     * @var string
     */
    protected $_s_name;
    /**
     * Variant URL
     *
     * @var string
     */
    protected $_s_url;
    /**
     * Variant price
     *
     * @var double
     */
    protected $_d_price;
    /**
     * Variant Price text represenatation. Eg. "10,00 EUR" or "from 8,00 EUR"
     *
     * @var string
     */
    protected $_s_f_price;
    /**
     * Subvariant array
     *
     * @var \OxidEsales\Eshop\Application\Model\MdVariant[]
     */
    protected $_a_subvariants = [];
    /**
     * Sets MD variant identifier
     *
     * @param string $sId New id
     */
    public function set_id($s_id): void
    {
        $this->_s_id = $s_id;
    }
    /**
     * Returns MD variant identifier
     *
     * @return string
     */
    public function get_id()
    {
        return $this->_s_id;
    }
    /**
     * Sets parent id
     *
     * @param string $sParentId Parent id
     */
    public function set_parent_id($s_parent_id): void
    {
        $this->_s_parent_id = $s_parent_id;
    }
    /**
     * Returns parent id
     *
     * @return string
     */
    public function get_parent_id()
    {
        return $this->_s_parent_id;
    }
    /**
     * Sets MD subvariants
     *
     * @param \OxidEsales\Eshop\Application\Model\MdVariant[] $aSubvariants Subvariants
     */
    public function set_md_subvariants($a_subvariants): void
    {
        $this->_a_subvariants = $a_subvariants;
    }
    /**
     * Returns full array of subvariants
     *
     * @return \OxidEsales\Eshop\Application\Model\MdVariant[]
     */
    public function get_md_subvariants()
    {
        return $this->_a_subvariants;
    }
    /**
     * Returns first MD subvariant from subvariant set or null in case variant has no subvariants.
     *
     * @return \OxidEsales\Eshop\Application\Model\MdVariant
     */
    public function get_first_md_subvariant()
    {
        $a_md_subvariants = $this->get_md_subvariants();
        if (count($a_md_subvariants)) {
            return reset($a_md_subvariants);
        }
        return null;
    }
    /**
     * Checks for existing MD subvariant by name. Returns existing one or in case $sName has not been found creates an empty OxMdVariant instance.
     *
     * @param string $sName Subvariant name
     *
     * @return \OxidEsales\Eshop\Application\Model\MdVariant
     */
    public function get_md_subvariant_by_name($s_name)
    {
        $a_subvariants = $this->get_md_subvariants();
        foreach ($a_subvariants as $o_md_subvariant) {
            if (strcasecmp((string) $o_md_subvariant->get_name(), $s_name) == 0) {
                return $o_md_subvariant;
            }
        }
        $o_new_subvariant = ox_new(\Oxid_Esales\Eshop\Application\Model\Md_Variant::class);
        $o_new_subvariant->set_name($s_name);
        $o_new_subvariant->set_id(md5($s_name . $this->get_id()));
        $o_new_subvariant->set_parent_id($this->get_id());
        $this->add_md_subvariant($o_new_subvariant);
        return $o_new_subvariant;
    }
    /**
     * Returns corresponding article URL or recusively first variant URL from subvariant set
     *
     * @return string
     */
    public function get_link()
    {
        $o_first_subvariant = $this->get_first_md_subvariant();
        if ($o_first_subvariant) {
            return $o_first_subvariant->get_link();
        }
        return $this->_s_url;
    }
    /**
     * Name setter
     *
     * @param string $sName New name
     */
    public function set_name($s_name): void
    {
        $this->_s_name = $s_name;
    }
    /**
     * Returns MD variant name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->_s_name;
    }
    /**
     * Returns price
     *
     * @return double
     */
    public function get_d_price()
    {
        return $this->_d_price;
    }
    /**
     * Returns min price recursively selected from full subvariant tree.
     *
     * @return double
     */
    public function get_min_d_price()
    {
        $d_min_price = $this->get_d_price();
        $a_variants = $this->get_md_subvariants();
        foreach ($a_variants as $o_variant) {
            $d_min_variant_price = $o_variant->get_min_d_price();
            if (is_null($d_min_price)) {
                $d_min_price = $d_min_variant_price;
            }
            if (!is_null($d_min_variant_price) && $d_min_variant_price < $d_min_price) {
                $d_min_price = $d_min_variant_price;
            }
        }
        return $d_min_price;
    }
    /**
     * Gets max subvariant depth. 0 means no deeper subvariants.
     *
     * @return int
     */
    public function get_max_depth()
    {
        $a_subvariants = $this->get_md_subvariants();
        if (!count($a_subvariants)) {
            return 0;
        }
        $i_max_depth = 0;
        foreach ($a_subvariants as $o_subvariant) {
            if ($o_subvariant->get_max_depth() > $i_max_depth) {
                $i_max_depth = $o_subvariant->get_max_depth();
            }
        }
        return $i_max_depth + 1;
    }
    /**
     * Returns MD variant price as a text.
     *
     * @return string
     */
    public function get_f_price()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        // 0002030 No need to return price if it disabled for better performance.
        if (!$my_config->get_config_param('bl_perfLoadPrice')) {
            return;
        }
        if ($this->_s_f_price) {
            return $this->_s_f_price;
        }
        $s_from_prefix = '';
        if (!$this->is_fixed_price()) {
            $s_from_prefix = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('PRICE_FROM') . ' ';
        }
        $d_min_price = $this->get_min_d_price();
        $s_f_min_price = \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($d_min_price);
        $s_currency = ' ' . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object()->sign;
        $this->_s_f_price = $s_from_prefix . $s_f_min_price . $s_currency;
        return $this->_s_f_price;
    }
    /**
     * Inits MD variant by name. In case $aNames parameter has more than one element addNames recursively adds names for subvariants.
     *
     * @param string $sArtId Article ID
     * @param array  $aNames Expected array of $sKey=>$sName pairs.
     * @param double $dPrice Price as double
     * @param string $sUrl   Article URL
     */
    public function add_names($s_art_id, $a_names, $d_price, $s_url): void
    {
        $i_count = count($a_names);
        $s_name = array_shift($a_names);
        if ($i_count) {
            //get required subvariant
            $o_variant = $this->get_md_subvariant_by_name($s_name);
            //add remaining names
            $o_variant->add_names($s_art_id, $a_names, $d_price, $s_url);
        } else {
            //means we have the deepest element and assign other attributes
            $this->_s_article_id = $s_art_id;
            $this->_d_price = $d_price;
            $this->_s_url = $s_url;
        }
    }
    /**
     * Returns corresponding article id or recusively first variant id from subvariant set
     *
     * @return string
     */
    public function get_article_id()
    {
        $o_first_subvariant = $this->get_first_md_subvariant();
        if ($o_first_subvariant) {
            return $o_first_subvariant->get_article_id();
        }
        return $this->_s_article_id;
    }
    /**
     * Checks whether $sArtId is one of subtree article ids.
     *
     * @param string $sArtId Article ID
     *
     * @return bool
     */
    public function has_article_id($s_art_id)
    {
        if ($this->get_article_id() == $s_art_id) {
            return true;
        }
        $a_subvariants = $this->get_md_subvariants();
        foreach ($a_subvariants as $o_subvariant) {
            if ($o_subvariant->has_article_id($s_art_id)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Adds one subvariant to subvariant set
     *
     * @param \OxidEsales\Eshop\Application\Model\MdVariant $oSubvariant Subvariant
     */
    protected function add_md_subvariant($o_subvariant)
    {
        $this->_a_subvariants[$o_subvariant->get_id()] = $o_subvariant;
    }
    /**
     * Checks if variant price is fixed or not ("from" price)
     *
     * @return bool
     */
    protected function is_fixed_price()
    {
        $d_price = $this->get_d_price();
        $a_variants = $this->get_md_subvariants();
        foreach ($a_variants as $o_variant) {
            $d_variant_price = $o_variant->get_d_price();
            if (is_null($d_price)) {
                $d_price = $d_variant_price;
            }
            if (!is_null($d_variant_price) && $d_variant_price != $d_price) {
                return false;
            }
            if (!$o_variant->is_fixed_price()) {
                return false;
            }
        }
        return true;
    }
}