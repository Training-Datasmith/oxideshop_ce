<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * Exception base class for an article
 */
class Article_Exception extends \Oxid_Esales\Eshop\Core\Exception\Standard_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxArticleException';
    /**
     * Article number who caused this exception
     *
     * @var string
     */
    protected $_s_article_nr;
    /**
     * Id of product which caused this exception
     *
     * @var string
     */
    protected $_s_product_id;
    /**
     * Sets the article number of the article which caused the exception
     *
     * @param string $sArticleNr Article who causes the exception
     */
    public function set_article_nr($s_article_nr): void
    {
        $this->_s_article_nr = $s_article_nr;
    }
    /**
     * The article number of the faulty article
     *
     * @return string
     */
    public function get_article_nr()
    {
        return $this->_s_article_nr;
    }
    /**
     * Sets the product id of the article which caused the exception
     *
     * @param string $sProductId id of product who causes the exception
     */
    public function set_product_id($s_product_id): void
    {
        $this->_s_product_id = $s_product_id;
    }
    /**
     * Faulty product id
     *
     * @return string
     */
    public function get_product_id()
    {
        return $this->_s_product_id;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Faulty Article --> ' . $this->_s_article_nr . "\n";
    }
    /**
     * Override of oxException::getValues()
     *
     * @return array
     */
    public function get_values()
    {
        $a_res = parent::get_values();
        $a_res['articleNr'] = $this->get_article_nr();
        $a_res['productId'] = $this->get_product_id();
        return $a_res;
    }
}