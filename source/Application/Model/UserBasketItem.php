<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Shopping basket item manager.
 * Manager class for shopping basket item (class may be overriden).
 */
class User_Basket_Item extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxuserbasketitem';
    /**
     * Article object assigned to userbasketitem
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_article;
    /**
     * Variant parent "buyable" status
     *
     * @var bool
     */
    protected $_bl_parent_buyable = false;
    /**
     * Basket item selection list
     *
     * @var array
     */
    protected $_a_sel_list;
    /**
     * Basket item persistent parameters
     *
     * @var array
     */
    protected $_a_pers_param;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        $this->set_variant_parent_buyable(\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVariantParentBuyable'));
        parent::__construct();
        $this->init('oxuserbasketitems');
    }
    /**
     * Variant parent "buyable" status setter
     *
     * @param bool $blBuyable parent "buyable" status
     */
    public function set_variant_parent_buyable($bl_buyable = false): void
    {
        $this->_bl_parent_buyable = $bl_buyable;
    }
    /**
     * Loads and returns the article for that basket item
     *
     * @param string $sItemKey the key that will be given to oxarticle setItemKey
     *
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleException
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_article($s_item_key)
    {
        if (!$this->oxuserbasketitems__oxartid->value) {
            //this exception may not be caught, anyhow this is a critical exception
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Article_Exception::class);
            $o_ex->set_message('EXCEPTION_ARTICLE_NOPRODUCTID');
            throw $o_ex;
        }
        if ($this->_o_article === null) {
            $this->_o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            // performance
            /* removed due to #4178
                if ( $this->_blParentBuyable ) {
                   $this->_oArticle->setNoVariantLoading( true );
               }
               */
            if (!$this->_o_article->load($this->oxuserbasketitems__oxartid->value)) {
                return false;
            }
            $a_sel_list = $this->get_sel_list();
            if (($a_selectlist = $this->_o_article->get_select_lists()) && is_array($a_sel_list)) {
                foreach ($a_sel_list as $i_key => $i_sel) {
                    if (isset($a_selectlist[$i_key][$i_sel])) {
                        // cloning select list information
                        $a_selectlist[$i_key][$i_sel] = clone $a_selectlist[$i_key][$i_sel];
                        $a_selectlist[$i_key][$i_sel]->selected = 1;
                    }
                }
                $this->_o_article->set_selectlist($a_selectlist);
            }
            // generating item key
            $this->_o_article->set_item_key($s_item_key);
        }
        return $this->_o_article;
    }
    /**
     * Does not return _oArticle var on serialisation
     *
     * @return array
     */
    public function __sleep()
    {
        $a_ret = [];
        foreach (get_object_vars($this) as $s_key => $s_var) {
            if ($s_key != '_oArticle') {
                $a_ret[] = $s_key;
            }
        }
        return $a_ret;
    }
    /**
     * Basket item selection list getter
     *
     * @return array
     */
    public function get_sel_list()
    {
        if ($this->_a_sel_list == null && $this->oxuserbasketitems__oxsellist->value) {
            $this->_a_sel_list = unserialize($this->oxuserbasketitems__oxsellist->value);
        }
        return $this->_a_sel_list;
    }
    /**
     * Basket item selection list setter
     *
     * @param array $aSelList selection list
     */
    public function set_sel_list($a_sel_list): void
    {
        $this->oxuserbasketitems__oxsellist = new \Oxid_Esales\Eshop\Core\Field(serialize($a_sel_list), \Oxid_Esales\Eshop\Core\Field::T_RAW);
    }
    /**
     * Basket item persistent parameters getter
     *
     * @return array
     */
    public function get_pers_params()
    {
        if ($this->_a_pers_param == null && $this->oxuserbasketitems__oxpersparam->value) {
            $this->_a_pers_param = unserialize($this->oxuserbasketitems__oxpersparam->value);
        }
        return $this->_a_pers_param;
    }
    /**
     * Basket item persistent parameters setter
     *
     * @param string $sPersParams persistent parameters
     */
    public function set_pers_params($s_pers_params): void
    {
        $this->oxuserbasketitems__oxpersparam = new \Oxid_Esales\Eshop\Core\Field(serialize($s_pers_params), \Oxid_Esales\Eshop\Core\Field::T_RAW);
    }
    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     */
    protected function set_field_data($s_field_name, $s_value, $i_data_type = \Oxid_Esales\Eshop\Core\Field::T_TEXT)
    {
        if ('oxsellist' === strtolower($s_field_name) || 'oxuserbasketitems__oxsellist' === strtolower($s_field_name) || 'oxpersparam' === strtolower($s_field_name) || 'oxuserbasketitems__oxpersparam' === strtolower($s_field_name)) {
            $i_data_type = \Oxid_Esales\Eshop\Core\Field::T_RAW;
        }
        return parent::set_field_data($s_field_name, $s_value, $i_data_type);
    }
}