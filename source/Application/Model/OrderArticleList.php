<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Order article list manager.
 */
class Order_Article_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Class constructor, initiates class constructor (parent::oxbase()).
     */
    public function __construct()
    {
        parent::__construct('oxorderarticle');
    }
    /**
     * Copies passed to method product into $this.
     *
     * @param string $sOxId object id
     */
    public function load_order_articles_for_user($s_ox_id): void
    {
        if (!$s_ox_id) {
            $this->clear();
            return;
        }
        $s_select = 'SELECT oxorderarticles.* FROM oxorder ';
        $s_select .= 'left join oxorderarticles on oxorderarticles.oxorderid = oxorder.oxid ';
        $s_select .= 'left join oxarticles on oxorderarticles.oxartid = oxarticles.oxid ';
        $s_select .= 'WHERE oxorder.oxuserid = :oxuserid';
        $this->select_string($s_select, ['oxuserid' => $s_ox_id]);
    }
}