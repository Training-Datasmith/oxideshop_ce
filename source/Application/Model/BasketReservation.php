<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Basket reservations handler class
 */
class Basket_Reservation extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Reservations list
     *
     * @var \OxidEsales\Eshop\Application\Model\UserBasket
     */
    protected $_o_reservations;
    /**
     * Currently reserved products array
     *
     * @var array
     */
    protected $_a_currently_reserved;
    /**
     * return the ID of active resevations user basket
     *
     * @return string
     */
    protected function get_reservations_id()
    {
        $s_id = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('basketReservationToken');
        if (!$s_id) {
            $utils_object = $this->get_utils_object_instance();
            $s_id = $utils_object->generate_u_id();
            \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('basketReservationToken', $s_id);
        }
        return $s_id;
    }
    /**
     * load reservation or create new reservation user basket
     *
     * @param string $sBasketId basket id for this user basket
     *
     * @return \OxidEsales\Eshop\Application\Model\UserBasket
     */
    protected function load_reservations($s_basket_id)
    {
        $o_reservations = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Basket::class);
        $a_where = ['oxuserbaskets.oxuserid' => $s_basket_id, 'oxuserbaskets.oxtitle' => 'reservations'];
        $query = $o_reservations->build_select_string($a_where);
        $record = Database_Provider::get_db()->select($query);
        if ($record && $record->count() > 0) {
            $o_reservations->assign($record->fields);
        } else {
            // creating if it does not exist
            $o_reservations->oxuserbaskets__oxtitle = new \Oxid_Esales\Eshop\Core\Field('reservations');
            $o_reservations->oxuserbaskets__oxuserid = new \Oxid_Esales\Eshop\Core\Field($s_basket_id);
            // marking basket as new (it will not be saved in DB yet)
            $o_reservations->set_is_new_basket();
        }
        return $o_reservations;
    }
    /**
     * get reservations collection
     *
     * @return \OxidEsales\Eshop\Application\Model\UserBasket
     */
    public function get_reservations()
    {
        if ($this->_o_reservations) {
            return $this->_o_reservations;
        }
        if (!$s_basket_id = $this->get_reservations_id()) {
            return null;
        }
        $this->_o_reservations = $this->load_reservations($s_basket_id);
        return $this->_o_reservations;
    }
    /**
     * return currently reserved items in an array format array (artId => amount)
     *
     * @return array
     */
    protected function get_reserved_items()
    {
        if (isset($this->_a_currently_reserved)) {
            return $this->_a_currently_reserved;
        }
        $o_reserved = $this->get_reservations();
        if (!$o_reserved) {
            return [];
        }
        $this->_a_currently_reserved = [];
        foreach ($o_reserved->get_items(false, false) as $o_item) {
            if (!isset($this->_a_currently_reserved[$o_item->oxuserbasketitems__oxartid->value])) {
                $this->_a_currently_reserved[$o_item->oxuserbasketitems__oxartid->value] = 0;
            }
            $this->_a_currently_reserved[$o_item->oxuserbasketitems__oxartid->value] += $o_item->oxuserbasketitems__oxamount->value;
        }
        return $this->_a_currently_reserved;
    }
    /**
     * return currently reserved amount for an article
     *
     * @param string $sArticleId article id
     *
     * @return double
     */
    public function get_reserved_amount($s_article_id)
    {
        $a_currently_reserved = $this->get_reserved_items();
        return $a_currently_reserved[$s_article_id] ?? 0;
    }
    /**
     * compute difference of reserved amounts vs basket items
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     *
     * @return array
     */
    protected function basket_difference(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket)
    {
        $a_diff = $this->get_reserved_items();
        // refreshing history
        foreach ($o_basket->get_contents() as $o_item) {
            $s_prod_id = $o_item->get_product_id();
            if (!isset($a_diff[$s_prod_id])) {
                $a_diff[$s_prod_id] = -$o_item->get_amount();
            } else {
                $a_diff[$s_prod_id] -= $o_item->get_amount();
            }
        }
        return $a_diff;
    }
    /**
     * reserve articles given the basket difference array
     *
     * @param array $aBasketDiff basket difference array
     *
     * @see oxBasketReservation::_basketDifference
     */
    protected function reserve_articles($a_basket_diff)
    {
        $bl_allow_negative_stock = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blAllowNegativeStock');
        $o_reserved = $this->get_reservations();
        foreach ($a_basket_diff as $s_id => $d_amount) {
            if ($d_amount != 0) {
                $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                if ($o_article->load($s_id)) {
                    $o_article->reduce_stock(-$d_amount, $bl_allow_negative_stock);
                    $o_reserved->add_item_to_basket($s_id, -$d_amount);
                }
            }
        }
        $this->_a_currently_reserved = null;
    }
    /**
     * reserve given basket items, only when not in admin mode
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     */
    public function reserve_basket(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket): void
    {
        if (!$this->is_admin()) {
            $this->reserve_articles($this->basket_difference($o_basket));
        }
    }
    /**
     * commit reservation of given article amount
     * deletes this amount from active reservations userBasket,
     * update sold amount
     *
     * @param string $sArticleId article id
     * @param double $dAmount    amount to use
     */
    public function commit_article_reservation($s_article_id, $d_amount): void
    {
        $d_reserved = $this->get_reserved_amount($s_article_id);
        if ($d_reserved < $d_amount) {
            $d_amount = $d_reserved;
        }
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->load($s_article_id);
        $this->get_reservations()->add_item_to_basket($s_article_id, -$d_amount);
        $o_article->before_update();
        $o_article->update_sold_amount($d_amount);
        $this->_a_currently_reserved = null;
    }
    /**
     * discard one article reservation
     * return the reserved stock to article
     *
     * @param string $sArticleId article id
     */
    public function discard_article_reservation($s_article_id): void
    {
        $d_reserved = $this->get_reserved_amount($s_article_id);
        if ($d_reserved) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($o_article->load($s_article_id)) {
                $o_article->reduce_stock(-$d_reserved, true);
                $this->get_reservations()->add_item_to_basket($s_article_id, 0, null, true);
                $this->_a_currently_reserved = null;
            }
        }
    }
    /**
     * discard all reserved articles
     */
    public function discard_reservations(): void
    {
        foreach (array_keys($this->get_reserved_items()) as $s_article_id) {
            $this->discard_article_reservation($s_article_id);
        }
        if ($this->_o_reservations) {
            $this->_o_reservations->delete();
            $this->_o_reservations = null;
            $this->_a_currently_reserved = null;
        }
    }
    /**
     * periodic cleanup: discards timed out reservations even if they are not
     * for the current user
     *
     * @param int $iLimit limit for discarding (performance related)
     *
     * @throws Exception
     */
    public function discard_unused_reservations($i_limit): void
    {
        $database = Database_Provider::get_master();
        $ps_basket_reservation_timeout = (int) Registry::get_config()->get_config_param('iPsBasketReservationTimeout');
        $start_time = Registry::get_utils_date()->get_time() - $ps_basket_reservation_timeout;
        $shop_id = Registry::get_config()->get_shop_id();
        $reservations = $database->select("SELECT oxid FROM oxuserbaskets\n            WHERE oxtitle = :oxtitle\n                AND oxupdate <= :oxupdate\n            LIMIT {$i_limit}", ['oxtitle' => 'reservations', 'oxupdate' => $start_time]);
        if ($reservations->EOF) {
            return;
        }
        $finished = [];
        while (!$reservations->EOF) {
            $finished[] = $database->quote($reservations->fields['oxid']);
            $reservations->fetch_row();
        }
        $finished = implode(',', $finished);
        $database->start_transaction();
        try {
            // Restock articles from selected reservation baskets only
            $items = $database->select("SELECT oxartid, oxamount FROM oxuserbasketitems \n                WHERE oxbasketid IN ({$finished})");
            while (!$items->EOF) {
                $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                if ($article->load($items->fields['oxartid'])) {
                    $article->reduce_stock(-$items->fields['oxamount'], true);
                }
                $items->fetch_row();
            }
            // Delete items of the selected reservations
            $database->execute("DELETE FROM oxuserbasketitems WHERE oxbasketid IN ({$finished})");
            // Delete items of expired savedbasket (shop-scoped) — no reservations here
            $database->execute("DELETE i FROM oxuserbasketitems i\n                JOIN oxuserbaskets b ON i.oxbasketid = b.oxid \n                WHERE b.oxupdate <= :startTime\n                    AND oxtitle = 'savedbasket'\n                    AND b.oxuserid IN (SELECT oxid FROM oxuser WHERE oxshopid = :oxshopid)", ['startTime' => $start_time, 'oxshopid' => $shop_id]);
            // Delete the selected reservation baskets
            $database->execute("DELETE FROM oxuserbaskets WHERE oxid IN ({$finished})");
            // Delete expired savedbaskets (shop scoped)
            $database->execute("DELETE FROM oxuserbaskets\n                WHERE oxupdate <= :startTime \n                    AND oxtitle = 'savedbasket'\n                    AND oxuserid IN (SELECT oxid FROM oxuser WHERE oxshopid = :oxshopid)", ['startTime' => $start_time, 'oxshopid' => $shop_id]);
            $database->commit_transaction();
        } catch (Exception $exception) {
            $database->rollback_transaction();
            throw $exception;
        }
        $this->_a_currently_reserved = null;
    }
    /**
     * return time left (in seconds) for basket before expiration
     *
     * @return int
     */
    public function get_time_left()
    {
        $i_timeout = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iPsBasketReservationTimeout');
        if ($i_timeout > 0) {
            $o_rev = $this->get_reservations();
            if ($o_rev && $o_rev->get_id()) {
                $i_timeout -= \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time() - (int) $o_rev->oxuserbaskets__oxupdate->value;
                \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('iBasketReservationTimeout', $o_rev->oxuserbaskets__oxupdate->value);
            } elseif ($i_session_timeout = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('iBasketReservationTimeout')) {
                $i_timeout -= \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time() - (int) $i_session_timeout;
            }
            return $i_timeout < 0 ? 0 : $i_timeout;
        }
        return 0;
    }
    /**
     * renews expiration timer to maximum value
     */
    public function renew_expiration(): void
    {
        if ($o_reserved = $this->get_reservations()) {
            $i_time = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
            $o_reserved->oxuserbaskets__oxupdate = new \Oxid_Esales\Eshop\Core\Field($i_time);
            $o_reserved->save();
            \Oxid_Esales\Eshop\Core\Registry::get_session()->delete_variable('iBasketReservationTimeout');
        }
    }
    /**
     * @return \OxidEsales\Eshop\Core\UtilsObject
     */
    protected function get_utils_object_instance()
    {
        return Registry::get_utils_object();
    }
}