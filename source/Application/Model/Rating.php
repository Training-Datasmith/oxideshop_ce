<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge\Product_Rating_Bridge_Interface;
/**
 * Article rate manager.
 * Performs loading, updating, inserting of article rates.
 */
class Rating extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Shop control variable
     *
     * @var string
     */
    protected $_bl_disable_shop_check = true;
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxrating';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxratings');
    }
    /**
     * Checks if user can rate product.
     *
     * @param string $sUserId   user id
     * @param string $sType     object type
     * @param string $sObjectId object id
     *
     * @return bool
     */
    public function allow_rating($s_user_id, $s_type, $s_object_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($i_rating_logs_timeout = $my_config->get_config_param('iRatingLogsTimeout')) {
            $s_exp_date = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time() - $i_rating_logs_timeout * 24 * 60 * 60);
            $o_db->execute('delete from oxratings where oxtimestamp < :expDate', ['expDate' => $s_exp_date]);
        }
        $s_select = 'select oxid from oxratings 
            where oxuserid = :oxuserid 
                and oxtype = :oxtype 
                and oxobjectid = :oxobjectid';
        $params = ['oxuserid' => $s_user_id, 'oxtype' => $s_type, 'oxobjectid' => $s_object_id];
        if ($o_db->get_one($s_select, $params)) {
            return false;
        }
        return true;
    }
    /**
     * calculates and return objects rating
     *
     * @param string $sObjectId           object id
     * @param string $sType               object type
     * @param array  $aIncludedObjectsIds array of ids
     *
     * @return float
     */
    public function get_rating_average($s_object_id, $s_type, $a_included_objects_ids = null)
    {
        $s_query_snipet = ' AND `oxobjectid` = :oxobjectid';
        if (is_array($a_included_objects_ids) && count($a_included_objects_ids) > 0) {
            $s_query_snipet = " AND ( `oxobjectid` = :oxobjectid OR `oxobjectid` in ('" . implode("', '", $a_included_objects_ids) . "') )";
        }
        $s_select = '
            SELECT
                AVG(`oxrating`)
            FROM `oxreviews`
            WHERE `oxrating` > 0
                 AND `oxtype` = :oxtype' . $s_query_snipet . '
            LIMIT 1';
        $params = ['oxobjectid' => $s_object_id, 'oxtype' => $s_type];
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        if ($f_rating = $database->get_one($s_select, $params)) {
            return round($f_rating, 1);
        }
        return $f_rating;
    }
    /**
     * calculates and return objects rating count
     *
     * @param string $sObjectId           object id
     * @param string $sType               object type
     * @param array  $aIncludedObjectsIds array of ids
     *
     * @return integer
     */
    public function get_rating_count($s_object_id, $s_type, $a_included_objects_ids = null)
    {
        $s_query_snipet = ' AND `oxobjectid` = :oxobjectid';
        if (is_array($a_included_objects_ids) && count($a_included_objects_ids) > 0) {
            $s_query_snipet = " AND ( `oxobjectid` = :oxobjectid OR `oxobjectid` in ('" . implode("', '", $a_included_objects_ids) . "') )";
        }
        $s_select = '
            SELECT
                COUNT(*)
            FROM `oxreviews`
            WHERE `oxrating` > 0
                AND `oxtype` = :oxtype' . $s_query_snipet . '
            LIMIT 1';
        // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804).
        $master_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
        return $master_db->get_one($s_select, ['oxobjectid' => $s_object_id, 'oxtype' => $s_type]);
    }
    /**
     * Retuns review object type
     *
     * @return string
     */
    public function get_object_type()
    {
        return $this->oxratings__oxtype->value;
    }
    /**
     * Retuns review object id
     *
     * @return string
     */
    public function get_object_id()
    {
        return $this->oxratings__oxobjectid->value;
    }
    /**
     * Delete this object from the database, returns true if entry was deleted.
     *
     * @param string $oxid Object ID(default null)
     *
     * @return bool
     */
    public function delete($oxid = null)
    {
        $is_product_rating = $this->is_product_object_type();
        $is_deleted = parent::delete($oxid);
        if ($is_product_rating) {
            $this->update_product_rating();
        }
        return $is_deleted;
    }
    /**
     * Returns true if Rating belongs to Product.
     */
    private function is_product_object_type(): bool
    {
        return $this->get_object_type() === 'oxarticle';
    }
    /**
     * Updates Product rating.
     */
    private function update_product_rating(): void
    {
        Container_Facade::get(Product_Rating_Bridge_Interface::class)->update_product_rating($this->get_object_id());
    }
}