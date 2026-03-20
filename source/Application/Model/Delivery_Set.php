<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Order delivery set manager.
 */
class Delivery_Set extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current object class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxdeliveryset';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxdeliveryset');
    }
    /**
     * Delete this object from the database, returns true on success.
     *
     * @param string $sOxId Object ID(default null)
     *
     * @return bool
     */
    public function delete($s_ox_id = null)
    {
        if (!$s_ox_id) {
            $s_ox_id = $this->get_id();
        }
        if (!$s_ox_id) {
            return false;
        }
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_db->execute('delete from oxobject2payment where oxobjectid = :oxid', ['oxid' => $s_ox_id]);
        $o_db->execute('delete from oxobject2delivery where oxdeliveryid = :oxid', ['oxid' => $s_ox_id]);
        $o_db->execute('delete from oxdel2delset where oxdelsetid = :oxid', ['oxid' => $s_ox_id]);
        return parent::delete($s_ox_id);
    }
    /**
     * returns delivery set id
     *
     * @param string $sTitle delivery name
     *
     * @return string
     */
    public function get_id_by_name($s_title)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_q = 'SELECT `oxid` FROM `' . $table_view_name_generator->get_view_name('oxdeliveryset') . '` 
            WHERE  `oxtitle` = :oxtitle';
        return $o_db->get_one($s_q, ['oxtitle' => $s_title]);
    }
}