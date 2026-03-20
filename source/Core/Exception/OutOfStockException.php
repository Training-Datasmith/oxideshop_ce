<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Exception;

/**
 * exception class for an article which is out of stock
 */
class Out_Of_Stock_Exception extends \Oxid_Esales\Eshop\Core\Exception\Article_Exception
{
    /**
     * Exception type, currently old class name is used.
     *
     * @var string
     */
    protected $type = 'oxOutOfStockException';
    /**
     * Maximal possible amount (e.g. 2 if two items of the article are left).
     */
    private int $_i_remaining_amount = 0;
    /**
     * Basket index value
     *
     * @var string
     */
    private $_s_basket_index;
    /**
     * Sets the amount of the article remaining in stock.
     *
     * @param integer $iRemainingAmount Articles remaining in stock
     */
    public function set_remaining_amount($i_remaining_amount): void
    {
        $this->_i_remaining_amount = (int) $i_remaining_amount;
    }
    /**
     * Amount of articles left
     *
     * @return integer
     */
    public function get_remaining_amount()
    {
        return $this->_i_remaining_amount;
    }
    /**
     * Sets the basket index for the article
     *
     * @param string $sBasketIndex Basket index for the faulty article
     */
    public function set_basket_index($s_basket_index): void
    {
        $this->_s_basket_index = $s_basket_index;
    }
    /**
     * The basketindex of the faulty article
     *
     * @return string
     */
    public function get_basket_index()
    {
        return $this->_s_basket_index;
    }
    /**
     * Get string dump
     * Overrides oxException::getString()
     *
     * @return string
     */
    public function get_string()
    {
        return self::class . '-' . parent::get_string() . ' Remaining Amount --> ' . $this->_i_remaining_amount;
    }
    /**
     * Creates an array of field name => field value of the object.
     * To make a easy conversion of exceptions to error messages possible.
     * Should be extended when additional fields are used!
     * Overrides oxException::getValues()
     *
     * @return array
     */
    public function get_values()
    {
        $a_res = parent::get_values();
        $a_res['remainingAmount'] = $this->get_remaining_amount();
        $a_res['basketIndex'] = $this->get_basket_index();
        return $a_res;
    }
    /**
     * Defines a name of the view variable containing the messages.
     * Currently it checks if destination value is set, and if
     * not - overrides default error message with:
     *
     *    $this->getMessage(). $this->getRemainingAmount()
     *
     * It is necessary to display correct stock error message on
     * any view (except basket).
     *
     * @param string $sDestination name of the view variable
     */
    public function set_destination($s_destination): void
    {
        // in case destination not set, overriding default error message
        if (!$s_destination) {
            $this->message = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string($this->get_message()) . ': ' . $this->get_remaining_amount();
        } else {
            $this->message = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string($this->get_message()) . ': ';
        }
    }
}