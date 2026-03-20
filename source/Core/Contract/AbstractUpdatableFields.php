<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * Abstraction for handling fields which could be modified by shop customer.
 */
abstract class Abstract_Updatable_Fields
{
    /** @var string */
    protected $table_name;
    /**
     * Return list of fields which could be updated by shop customer.
     */
    abstract public function get_updatable_fields();
    /**
     * Get table name of a model.
     * Table name could be used to form full name together with field.
     *
     * @return string
     */
    public function get_table_name()
    {
        return $this->table_name;
    }
}