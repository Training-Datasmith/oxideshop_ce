<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Shop list manager.
 * Organizes list of shop objects.
 */
class Shop_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Calls parent constructor
     */
    public function __construct()
    {
        parent::__construct('oxshop');
    }
    /**
     * Loads all shops to list
     */
    public function get_all(): void
    {
        $this->select_string('SELECT `oxshops`.* FROM `oxshops`');
    }
    /**
     * Gets shop list into object
     */
    public function get_id_title_list(): void
    {
        $this->set_base_object(ox_new('oxListObject', 'oxshops'));
        $this->select_string('SELECT `OXID`, `OXNAME` FROM `oxshops`');
    }
}