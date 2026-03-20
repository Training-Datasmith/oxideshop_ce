<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Article amount price list
 */
class Amount_Price_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxprice2article';
    /**
     * oxArticle object
     *
     * @var \OxidEsales\Eshop\Application\Model\Article
     */
    protected $_o_article;
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('oxbase');
        $this->init('oxbase', 'oxprice2article');
    }
    /**
     *  Article getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Article $_oArticle
     */
    public function get_article()
    {
        return $this->_o_article;
    }
    /**
     * Article setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article
     */
    public function set_article($o_article): void
    {
        $this->_o_article = $o_article;
    }
    /**
     * Load category list data
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article Article
     */
    public function load($article): void
    {
        $this->set_article($article);
        $a_data = $this->load_from_db();
        $this->assign_array($a_data);
    }
    /**
     * Get data from db
     *
     * @return array
     */
    protected function load_from_db()
    {
        $s_article_id = $this->get_article()->get_id();
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blVariantInheritAmountPrice') && $this->get_article()->get_parent_id()) {
            $s_article_id = $this->get_article()->get_parent_id();
        }
        $params = ['oxartid' => $s_article_id];
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blMallInterchangeArticles')) {
            $s_shop_select = '1';
        } else {
            $s_shop_select = ' `oxshopid` = :oxshopid ';
            $params['oxshopid'] = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        }
        $s_sql = "SELECT * FROM `oxprice2article` \n            WHERE `oxartid` = :oxartid AND {$s_shop_select} ORDER BY `oxamount` ";
        return $db->get_all($s_sql, $params);
    }
}