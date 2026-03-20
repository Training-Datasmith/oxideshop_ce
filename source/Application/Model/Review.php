<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Model\Base_Model;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge\User_Review_And_Rating_Bridge_Interface;
class Review extends Base_Model
{
    /**
     * @var string
     */
    protected $_bl_disable_shop_check = true;
    /**
     * @var string
     */
    protected $_s_class_name = 'oxreview';
    public function __construct()
    {
        parent::__construct();
        $this->init('oxreviews');
    }
    /**
     * Calls parent::assign and assigns review writer data
     *
     * @param array $dbRecord database record
     *
     * @return bool
     */
    public function assign($db_record)
    {
        $bl_ret = parent::assign($db_record);
        if (isset($this->oxreviews__oxuserid) && $this->oxreviews__oxuserid->value) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $params = ['oxid' => $this->oxreviews__oxuserid->value];
            $first_name = $o_db->get_one('SELECT oxfname FROM oxuser 
                WHERE oxid = :oxid', $params);
            $this->oxuser__oxfname = new \Oxid_Esales\Eshop\Core\Field($first_name);
        }
        return $bl_ret;
    }
    /**
     * Loads object review information. Returns true on success.
     *
     * @param string $oxId ID of object to load
     *
     * @return bool
     */
    public function load($ox_id)
    {
        if ($bl_ret = parent::load($ox_id)) {
            // convert date's to international format
            $this->oxreviews__oxcreate->set_value(Registry::get_utils_date()->format_db_date($this->oxreviews__oxcreate->value));
        }
        return $bl_ret;
    }
    /**
     * Inserts object data fiels in DB. Returns true on success.
     *
     * @return bool
     */
    protected function insert()
    {
        // set oxcreate
        $this->oxreviews__oxcreate = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', Registry::get_utils_date()->get_time()));
        return parent::insert();
    }
    /**
     * get oxList of reviews for given object ids and type
     *
     * @param string  $sType       type of given ids
     * @param mixed   $aIds        given object ids to load, can be array or just one id, given as string
     * @param boolean $blLoadEmpty true if want to load empty text reviews
     * @param int     $iLoadInLang language to select for loading
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function load_list($s_type, $a_ids, $bl_load_empty = false, $i_load_in_lang = null)
    {
        $reviews = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $reviews->init('oxreview');
        $params = ['oxtype' => $s_type, 'oxlang' => is_null($i_load_in_lang) ? (int) Registry::get_lang()->get_base_language() : (int) $i_load_in_lang];
        if (is_array($a_ids) && count($a_ids)) {
            $s_object_id_where = 'oxreviews.oxobjectid in ( ' . implode(', ', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array($a_ids)) . ' )';
        } elseif (is_string($a_ids) && $a_ids) {
            $s_object_id_where = 'oxreviews.oxobjectid = :oxobjectid';
            $params['oxobjectid'] = $a_ids;
        } else {
            return $reviews;
        }
        $s_select = "select oxreviews.* from oxreviews where oxreviews.oxtype = :oxtype and {$s_object_id_where} and oxreviews.oxlang = :oxlang";
        if (!$bl_load_empty) {
            $s_select .= ' and oxreviews.oxtext != "" ';
        }
        if (Registry::get_config()->get_config_param('blGBModerate')) {
            $s_select .= ' and ( oxreviews.oxactive = "1" ';
            if ($o_user = $this->get_user()) {
                $s_select .= 'or  oxreviews.oxuserid = :oxuserid ';
                $params['oxuserid'] = $o_user->get_id();
            }
            $s_select .= ')';
        }
        $s_select .= ' order by oxreviews.oxcreate desc ';
        $reviews->select_string($s_select, $params);
        foreach ($reviews as $review) {
            $review_creation_date = $review->oxreviews__oxcreate->get_raw_value();
            $review->oxreviews__oxcreate->set_value(Registry::get_utils_date()->format_db_date($review_creation_date), Field::T_RAW);
            $review_text = (string) $review->oxreviews__oxtext->value;
            $review->oxreviews__oxtext->set_value($review_text, Field::T_RAW);
        }
        return $reviews;
    }
    /**
     * Retuns review object type
     *
     * @return string
     */
    public function get_object_type()
    {
        return is_object($this->oxreviews__oxtype) ? $this->oxreviews__oxtype->value : $this->oxreviews__oxtype;
    }
    /**
     * Retuns review object id
     *
     * @return string
     */
    public function get_object_id()
    {
        return is_object($this->oxreviews__oxobjectid) ? $this->oxreviews__oxobjectid->value : $this->oxreviews__oxobjectid;
    }
    /**
     * Returns ReviewAndRating list by User id.
     *
     * @param string $userId
     *
     * @return array
     */
    public function get_review_and_rating_list_by_user_id($user_id)
    {
        return Container_Facade::get(User_Review_And_Rating_Bridge_Interface::class)->get_review_and_rating_list($user_id);
    }
}