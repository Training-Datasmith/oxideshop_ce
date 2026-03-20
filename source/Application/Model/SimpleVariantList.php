<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Simple variant list.
 */
class Simple_Variant_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Parent article for list variants
     */
    protected $_o_parent;
    /**
     * List Object class name
     *
     * @var string
     */
    protected $_s_objects_in_list_name = 'oxsimplevariant';
    /**
     * Sets parent variant
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oParent Parent article
     */
    public function set_parent($o_parent): void
    {
        $this->_o_parent = $o_parent;
    }
    /**
     * Sets parent for variant. This method is invoked for each element in oxList::assign() loop.
     *
     * @param \OxidEsales\Eshop\Application\Model\SimpleVariant $oListObject Simple variant
     * @param array          $aDbFields   Array of available
     */
    protected function assign_element($o_list_object, $a_db_fields)
    {
        $o_list_object->set_parent($this->_o_parent);
        parent::assign_element($o_list_object, $a_db_fields);
    }
}