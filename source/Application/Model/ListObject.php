<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Simple list object
 */
class List_Object
{
    /**
     * Class constructor
     *
     * @param string $_sTableName Table name
     */
    public function __construct(private $_s_table_name)
    {
    }
    /**
     * Assigns database record to object
     *
     * @param object $aData Database record
     */
    public function assign($a_data): void
    {
        if (!is_array($a_data)) {
            return;
        }
        foreach ($a_data as $s_key => $s_value) {
            $s_field_name = strtolower($this->_s_table_name . '__' . $s_key);
            $this->{$s_field_name} = new \Oxid_Esales\Eshop\Core\Field($s_value);
        }
    }
    /**
     * Returns object id
     *
     * @return int
     */
    public function get_id()
    {
        $s_field_name = strtolower($this->_s_table_name . '__oxid');
        return $this->{$s_field_name}->value;
    }
}