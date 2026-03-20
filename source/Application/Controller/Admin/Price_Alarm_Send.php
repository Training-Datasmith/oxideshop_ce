<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * pricealarm sending manager.
 * Performs sending of pricealarm to selected iAllCnt groups.
 */
class Price_Alarm_Send extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Default tab number
     *
     * @var int
     */
    protected $_i_def_edit = 1;
    /**
     * Executes parent method parent::render(), creates oxpricealarm object,
     * sends pricealarm to iAllCnts of chosen groups and returns name of template
     * file "pricealarm_send"/"pricealarm_done".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        ini_set('session.gc_maxlifetime', 36000);
        $start = (int) Registry::get_request()->get_request_escaped_parameter('iStart');
        $limit = $config->get_config_param('iCntofMails');
        $active_alerts_amount = Registry::get_request()->get_request_escaped_parameter('iAllCnt');
        if (!isset($active_alerts_amount)) {
            $active_alerts_amount = $this->count_active_price_alerts();
        }
        $this->send_price_change_notifications($start, $limit);
        // Advance mail pointer and set parameter
        $start += $limit;
        $this->_a_view_data['iStart'] = $start;
        $this->_a_view_data['iAllCnt'] = $active_alerts_amount;
        $this->_a_view_data['actlang'] = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        if ($start < $active_alerts_amount) {
            return 'pricealarm_send';
        }
        return 'pricealarm_done';
    }
    /**
     * Overrides parent method to pass referred id.
     *
     * @param string $sId Class name
     */
    protected function setup_navigation($s_id)
    {
        parent::setup_navigation('pricealarm_list');
    }
    /**
     * Counts active price alerts and returns this number.
     *
     * @return int
     */
    protected function count_active_price_alerts()
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $shop_id = $config->get_shop_id();
        $active_alarms_query = "SELECT oxprice, oxartid FROM oxpricealarm\n                    WHERE oxsended = '000-00-00 00:00:00' AND oxshopid = :oxshopid";
        $result = $database->select($active_alarms_query, ['oxshopid' => $shop_id]);
        $count = 0;
        while ($result != false && !$result->EOF) {
            $alarm_price = $result->fields['oxprice'];
            $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $article->load($result->fields['oxartid']);
            if ($article->get_price()->get_brutto_price() <= $alarm_price) {
                $count++;
            }
            $result->fetch_row();
        }
        return $count;
    }
    /**
     * Sends price alert notifications about changed article prices.
     *
     * @param int $start How much price alerts was already sent.
     * @param int $limit How much price alerts to send.
     */
    protected function send_price_change_notifications($start, $limit)
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $shop_id = $config->get_shop_id();
        $alarms_query = "SELECT oxid, oxemail, oxartid, oxprice FROM oxpricealarm\n            WHERE oxsended = '000-00-00 00:00:00' AND oxshopid = :oxshopid";
        $result = $database->select_limit($alarms_query, $limit, $start, ['oxshopid' => $shop_id]);
        while ($result != false && !$result->EOF) {
            $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $article->load($result->fields['oxartid']);
            if ($article->get_price()->get_brutto_price() <= $result->fields['oxprice']) {
                $this->sende_mail($result->fields['oxemail'], $result->fields['oxartid'], $result->fields['oxid'], $result->fields['oxprice']);
            }
            $result->fetch_row();
        }
    }
    /**
     * Creates and sends email with price alarm information.
     *
     * @param string $emailAddress Email address
     * @param string $productID    Product id
     * @param string $priceAlarmId Price alarm id
     * @param string $bidPrice     Bid price
     */
    public function sende_mail($email_address, $product_id, $price_alarm_id, $bid_price): void
    {
        $alarm = ox_new(\Oxid_Esales\Eshop\Application\Model\Price_Alarm::class);
        $alarm->load($price_alarm_id);
        $language = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $language_id = (int) $alarm->oxpricealarm__oxlang->value;
        $old_language_id = $language->get_tpl_language();
        $language->set_tpl_language($language_id);
        $email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
        $success = (int) $email->send_pricealarm_to_customer($email_address, $alarm);
        $language->set_tpl_language($old_language_id);
        if ($success) {
            $alarm->oxpricealarm__oxsended = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s'));
            $alarm->save();
        }
    }
}