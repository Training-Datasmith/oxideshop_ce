<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin article main pricealarm manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Customer Info -> pricealarm -> Main.
 */
class Price_Alarm_Mail extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        parent::render();
        $shop_id = $config->get_shop_id();
        //articles price in subshop and baseshop can be different
        $this->_a_view_data['iAllCnt'] = 0;
        $query = "\n            SELECT oxprice, oxartid\n            FROM oxpricealarm\n            WHERE oxsended = '000-00-00 00:00:00' AND oxshopid = :oxshopid";
        $result = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->select($query, ['oxshopid' => $shop_id]);
        if ($result != false && $result->count() > 0) {
            $simple_cache = [];
            while (!$result->EOF) {
                $price = $result->fields['oxprice'];
                $article_id = $result->fields['oxartid'];
                if (isset($simple_cache[$article_id])) {
                    if ($simple_cache[$article_id] <= $price) {
                        $this->_a_view_data['iAllCnt'] += 1;
                    }
                } else {
                    $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                    if ($article->load($article_id)) {
                        $article_price = $simple_cache[$article_id] = $article->get_price()->get_brutto_price();
                        if ($article_price <= $price) {
                            $this->_a_view_data['iAllCnt'] += 1;
                        }
                    }
                }
                $result->fetch_row();
            }
        }
        return 'pricealarm_mail';
    }
}