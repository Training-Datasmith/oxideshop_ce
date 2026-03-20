<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use stdClass;
/**
 * Admin article RDFa deliveryset manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Shop Settings -> Shipping & Handling -> RDFa.
 */
class Delivery_Set_Rdfa extends \Oxid_Esales\Eshop\Application\Controller\Admin\Payment_Rdfa
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'deliveryset_rdfa';
    /**
     * Predefined delivery methods
     *
     * @var array
     */
    protected $_a_rd_fa_deliveries = ['DeliveryModeDirectDownload' => 0, 'DeliveryModeFreight' => 0, 'DeliveryModeMail' => 0, 'DeliveryModeOwnFleet' => 0, 'DeliveryModePickUp' => 0, 'DHL' => 1, 'FederalExpress' => 1, 'UPS' => 1];
    /**
     * Saves changed mapping configurations
     */
    public function save(): void
    {
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $a_rd_fa_deliveries = (array) Registry::get_request()->get_request_escaped_parameter('ardfadeliveries');
        // Delete old mappings
        $o_db = Database_Provider::get_db();
        $s_ox_id_parameter = Registry::get_request()->get_request_escaped_parameter('oxid');
        $s_sql = "DELETE FROM oxobject2delivery WHERE oxdeliveryid = :oxdeliveryid AND OXTYPE = 'rdfadeliveryset'";
        $o_db->execute($s_sql, ['oxdeliveryid' => $s_ox_id_parameter]);
        // Save new mappings
        foreach ($a_rd_fa_deliveries as $s_delivery) {
            $o_mapping = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
            $o_mapping->init('oxobject2delivery');
            $o_mapping->assign($a_params);
            $o_mapping->oxobject2delivery__oxobjectid = new \Oxid_Esales\Eshop\Core\Field($s_delivery);
            $o_mapping->save();
        }
    }
    /**
     * Returns an array including all available RDFa deliveries.
     *
     * @return array
     */
    public function get_all_rd_fa_deliveries()
    {
        $a_rd_fa_deliveries = [];
        $a_assigned_rd_fa_deliveries = $this->get_assigned_rd_fa_deliveries();
        foreach ($this->_a_rd_fa_deliveries as $s_name => $i_type) {
            $o_delivery = new stdClass();
            $o_delivery->name = $s_name;
            $o_delivery->type = $i_type;
            $o_delivery->checked = in_array($s_name, $a_assigned_rd_fa_deliveries);
            $a_rd_fa_deliveries[] = $o_delivery;
        }
        return $a_rd_fa_deliveries;
    }
    /**
     * Returns array of RDFa deliveries which are assigned to current delivery
     *
     * @return array
     */
    public function get_assigned_rd_fa_deliveries()
    {
        return Database_Provider::get_db()->get_col('select oxobjectid from oxobject2delivery where oxdeliveryid = :oxdeliveryid' . ' and oxtype = "rdfadeliveryset" ', ['oxdeliveryid' => Registry::get_request()->get_request_escaped_parameter('oxid')]);
    }
}