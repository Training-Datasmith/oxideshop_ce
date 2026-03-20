<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Newsletter Subscriptions manager
 * Performs user managing function
 * information, deletion and other.
 */
class News_Subscribed extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Subscription marker
     *
     * @var bool
     */
    protected $_bl_was_subscribed = false;
    /**
     * Subscription marker. Marks that newsletter was subscribed but wasn't confirmed.
     *
     * @var bool
     */
    protected $_bl_was_pre_subscribed = false;
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxnewssubscribed';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxnewssubscribed');
    }
    /**
     * Loads object (newssubscription) details from DB. Returns true on success.
     *
     * @param string $oxId oxnewssubscribed ID
     *
     * @return bool
     */
    public function load($ox_id)
    {
        $bl_ret = parent::load($ox_id);
        if ($this->get_field_data('oxnewssubscribed__oxdboptin') == 1) {
            $this->_bl_was_subscribed = true;
        } elseif ($this->get_field_data('oxnewssubscribed__oxdboptin') == 2) {
            $this->_bl_was_pre_subscribed = true;
        }
        return $bl_ret;
    }
    /**
     * Loader which loads news subscription according to subscribers email address
     *
     * @param string $sEmailAddress subscribers email address
     *
     * @return bool
     */
    public function load_from_email($s_email_address)
    {
        $user_oxid = $this->get_subscribed_user_id_by_email($s_email_address);
        return $this->load($user_oxid);
    }
    /**
     * Get subscribed user id by email.
     *
     * @param string $email
     *
     * @return string
     */
    protected function get_subscribed_user_id_by_email($email)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $params = ['oxemail' => (string) $email];
        return $database->get_one('select oxid from oxnewssubscribed 
            where oxemail = :oxemail ', $params);
    }
    /**
     * Loader which loads news subscription according to subscribers oxid
     *
     * @param string $sOxUserId subscribers oxid
     *
     * @return bool
     */
    public function load_from_user_id($s_ox_user_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $params = ['oxuserid' => $s_ox_user_id, 'oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()];
        $s_ox_id = $o_db->get_one('select oxid from oxnewssubscribed 
            where oxuserid = :oxuserid and oxshopid = :oxshopid', $params);
        return $this->load($s_ox_id);
    }
    /**
     * Inserts nbews object data to DB. Returns true on success.
     *
     * @return mixed oxid on success or false on failure
     */
    protected function insert()
    {
        // set subscription date
        $this->oxnewssubscribed__oxsubscribed = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s'), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        return parent::insert();
    }
    /**
     * We need to check if we unsubscribe here
     *
     * @return mixed oxid on success or false on failure
     */
    protected function update()
    {
        if (($this->_bl_was_subscribed || $this->_bl_was_pre_subscribed) && !$this->oxnewssubscribed__oxdboptin->value) {
            // set unsubscription date
            $this->oxnewssubscribed__oxunsubscribed->set_value(date('Y-m-d H:i:s'));
            // 0001974 Same object can be called many times without requiring to renew date.
            // If so happens, it would have _aSkipSaveFields set to skip date field. So need to check and
            // release if _aSkipSaveFields are set for field oxunsubscribed.
            $a_skip_save_fields_keys = array_keys($this->_a_skip_save_fields, 'oxunsubscribed');
            foreach ($a_skip_save_fields_keys as $i_skip_save_field_key) {
                unset($this->_a_skip_save_fields[$i_skip_save_field_key]);
            }
        } else {
            // don't update date
            $this->_a_skip_save_fields[] = 'oxunsubscribed';
        }
        return parent::update();
    }
    /**
     * Newsletter subscription status getter
     *
     * @return int
     */
    public function get_opt_in_status()
    {
        return (int) $this->get_field_data('oxdboptin');
    }
    /**
     * Newsletter subscription status setter
     *
     * @param int $iStatus subscription status
     */
    public function set_opt_in_status($i_status): void
    {
        $this->oxnewssubscribed__oxdboptin = new \Oxid_Esales\Eshop\Core\Field($i_status, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->save();
    }
    /**
     * Newsletter subscription email sending status getter
     *
     * @return int
     */
    public function get_opt_in_email_status()
    {
        return $this->oxnewssubscribed__oxemailfailed->value;
    }
    /**
     * Newsletter subscription email sending status setter
     *
     * @param int $iStatus subscription status
     */
    public function set_opt_in_email_status($i_status): void
    {
        $this->oxnewssubscribed__oxemailfailed = new \Oxid_Esales\Eshop\Core\Field($i_status, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->save();
    }
    /**
     * Check if was ever unsubscribed by unsubscribed field.
     *
     * @return bool
     */
    public function was_unsubscribed()
    {
        if ('0000-00-00 00:00:00' != $this->oxnewssubscribed__oxunsubscribed->value) {
            return true;
        }
        return false;
    }
    /**
     * This method is called from \OxidEsales\Eshop\Application\Model\User::update. Currently it updates user
     * information kept in db
     *
     * @param \OxidEsales\Eshop\Application\Model\User $oUser subscription user object
     *
     * @return bool
     */
    public function update_subscription($o_user)
    {
        // user email changed ?
        if ($o_user->oxuser__oxusername->value && $this->oxnewssubscribed__oxemail->value != $o_user->oxuser__oxusername->value) {
            $this->oxnewssubscribed__oxemail = new \Oxid_Esales\Eshop\Core\Field($o_user->oxuser__oxusername->value, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        }
        // updating some other fields
        $this->oxnewssubscribed__oxsal = new \Oxid_Esales\Eshop\Core\Field($o_user->get_field_data('oxsal'), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxnewssubscribed__oxfname = new \Oxid_Esales\Eshop\Core\Field($o_user->get_field_data('oxfname'), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxnewssubscribed__oxlname = new \Oxid_Esales\Eshop\Core\Field($o_user->get_field_data('oxlname'), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        return (bool) $this->save();
    }
}