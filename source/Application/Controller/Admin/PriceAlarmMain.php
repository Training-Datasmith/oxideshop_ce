<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article main pricealarm manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Customer Info -> pricealarm -> Main.
 */
class Price_Alarm_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $this->_a_view_data['iAllCnt'] = $this->get_active_price_alarms_count();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_pricealarm = ox_new(\Oxid_Esales\Eshop\Application\Model\Price_Alarm::class);
            $o_pricealarm->load($sox_id);
            // customer info
            $o_user = null;
            if ($o_pricealarm->oxpricealarm__oxuserid->value) {
                $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                $o_user->load($o_pricealarm->oxpricealarm__oxuserid->value);
                $o_pricealarm->o_user = $o_user;
            }
            $o_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
            $o_shop->load($config->get_shop_id());
            $this->add_global_params($o_shop);
            if (!$i_lang = $o_pricealarm->oxpricealarm__oxlang->value) {
                $i_lang = 0;
            }
            $o_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
            $a_languages = $o_lang->get_language_names();
            $this->_a_view_data['edit_lang'] = $a_languages[$i_lang];
            // rendering mail message text
            $o_letter = new stdClass();
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
            if (isset($a_params['oxpricealarm__oxlongdesc']) && $a_params['oxpricealarm__oxlongdesc']) {
                $o_letter->oxpricealarm__oxlongdesc = new \Oxid_Esales\Eshop\Core\Field(stripslashes((string) $a_params['oxpricealarm__oxlongdesc']), \Oxid_Esales\Eshop\Core\Field::T_RAW);
            } else {
                $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
                $s_desc = $o_email->send_pricealarm_to_customer($o_pricealarm->oxpricealarm__oxemail->value, $o_pricealarm, null, true);
                $i_old_lang = $o_lang->get_tpl_language();
                $o_lang->set_tpl_language($i_lang);
                $o_letter->oxpricealarm__oxlongdesc = new \Oxid_Esales\Eshop\Core\Field($s_desc, \Oxid_Esales\Eshop\Core\Field::T_RAW);
                $o_lang->set_tpl_language($i_old_lang);
            }
            $this->_a_view_data['editor'] = $this->generate_text_editor('100%', 300, $o_letter, 'oxpricealarm__oxlongdesc', 'details.css');
            $this->_a_view_data['edit'] = $o_pricealarm;
            $this->_a_view_data['actshop'] = $config->get_shop_id();
        }
        parent::render();
        return 'pricealarm_main';
    }
    /**
     * Sending email to selected customer
     */
    public function send(): void
    {
        $bl_error = true;
        // error
        if ($s_oxid = $this->get_edit_object_id()) {
            $o_pricealarm = ox_new(\Oxid_Esales\Eshop\Application\Model\Price_Alarm::class);
            $o_pricealarm->load($s_oxid);
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
            $s_mail_body = isset($a_params['oxpricealarm__oxlongdesc']) ? stripslashes($a_params['oxpricealarm__oxlongdesc']) : '';
            $s_recipient = $o_pricealarm->oxpricealarm__oxemail->value;
            $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
            $bl_success = (int) $o_email->send_pricealarm_to_customer($s_recipient, $o_pricealarm, $s_mail_body);
            // setting result message
            if ($bl_success) {
                $o_pricealarm->oxpricealarm__oxsended->set_value(date('Y-m-d H:i:s'));
                $o_pricealarm->save();
                $bl_error = false;
            }
        }
        if (!$bl_error) {
            $this->_a_view_data['mail_succ'] = 1;
        } else {
            $this->_a_view_data['mail_err'] = 1;
        }
    }
    /**
     * Returns number of active price alarms.
     *
     * @return int
     */
    protected function get_active_price_alarms_count()
    {
        // #1140 R - price must be checked from the object.
        $query = "\n            SELECT oxarticles.oxid as oxid, oxpricealarm.oxprice as oxprice\n            FROM oxpricealarm, oxarticles\n            WHERE oxarticles.oxid = oxpricealarm.oxartid AND oxpricealarm.oxsended = '000-00-00 00:00:00'";
        $result = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->select($query);
        $count = 0;
        if ($result != false && $result->count() > 0) {
            while (!$result->EOF) {
                $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                $article->load($result->fields['oxid']);
                if ($article->get_price()->get_brutto_price() <= $result->fields['oxprice']) {
                    $count++;
                }
                $result->fetch_row();
            }
        }
        return $count;
    }
}