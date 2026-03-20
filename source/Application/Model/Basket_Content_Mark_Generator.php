<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Class oxBasketContentMarkGenerator which forms explanation marks.
 */
class Basket_Content_Mark_Generator
{
    /**
     * Default value for explanation mark.
     */
    public const DEFAULT_EXPLANATION_MARK = '**';
    /**
     * Marks added to array by article type.
     */
    private ?array $_a_marks = null;
    /**
     * Basket that is used to get article type(downloadable, intangible etc..).
     *
     * @var \OxidEsales\Eshop\Application\Model\Basket
     */
    private $_o_basket;
    /**
     * Sets basket that is used to get article type(downloadable, intangible etc..).
     */
    public function __construct(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket)
    {
        $this->_o_basket = $o_basket;
    }
    /**
     * Returns explanation mark by given mark identification (skippedDiscount, downloadable, intangible).
     *
     * @param string $sMarkIdentification Mark identification.
     *
     * @return string
     */
    public function get_mark($s_mark_identification)
    {
        if (is_null($this->_a_marks)) {
            $s_current_mark = self::DEFAULT_EXPLANATION_MARK;
            $a_marks = $this->form_marks($s_current_mark);
            $this->_a_marks = $a_marks;
        }
        return $this->_a_marks[$s_mark_identification];
    }
    /**
     * Basket that is used to get article type(downloadable, intangible etc..).
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    private function get_basket()
    {
        return $this->_o_basket;
    }
    /**
     * Forms marks for articles.
     *
     * @param string $sCurrentMark Current mark.
     */
    private function form_marks(string $s_current_mark): array
    {
        $o_basket = $this->get_basket();
        $a_marks = [];
        if ($o_basket->has_skiped_discount()) {
            $a_marks['skippedDiscount'] = $s_current_mark;
            $s_current_mark .= '*';
        }
        if ($o_basket->has_articles_with_downloadable_agreement()) {
            $a_marks['downloadable'] = $s_current_mark;
            $s_current_mark .= '*';
        }
        if ($o_basket->has_articles_with_intangible_agreement()) {
            $a_marks['intangible'] = $s_current_mark;
        }
        return $a_marks;
    }
}