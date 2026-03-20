<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Database\Adapter\Doctrine;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Oxid_Esales\Eshop\Core\Database\Adapter\Result_Set_Interface;
use Traversable;
/**
 * The doctrine statement wrapper, to support the old adodblite interface.
 *
 * @package OxidEsales\EshopCommunity\Core\Database\Adapter
 *
 * @deprecated since v6.5.0 (2019-09-24); Use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
 */
class Result_Set implements \IteratorAggregate, Result_Set_Interface
{
    /**
     * @var array{}
     */
    public $fields;
    /**
     * @var false
     */
    public $EOF;
    private readonly Result $result;
    public function __construct(private Statement $statement)
    {
        $this->fields = [];
        $this->EOF = false;
        $this->result = $this->statement->execute_query();
        if ($this->count() === 0) {
            $this->set_to_empty_state();
        }
        $this->fetch_row();
    }
    public function close(): void
    {
        $this->result->free();
        $this->fields = [];
    }
    public function fetch_row()
    {
        $this->fields = $this->result->fetch_associative();
        if (false === $this->fields) {
            $this->EOF = true;
        }
        return $this->fields;
    }
    public function fetch_all()
    {
        $this->close();
        $this->statement->execute_query();
        return $this->result->fetch_all_associative();
    }
    public function field_count()
    {
        return $this->result->column_count();
    }
    public function getIterator(): Traversable
    {
        $this->close();
        $this->statement->execute_query();
        return $this->result->iterate_associative();
    }
    public function get_fields()
    {
        return $this->fields;
    }
    protected function get_statement(): \Doctrine\DBAL\Statement
    {
        return $this->statement;
    }
    protected function set_statement(Statement $statement)
    {
        $this->statement = $statement;
    }
    protected function set_to_empty_state()
    {
        $this->EOF = true;
    }
    public function count(): int
    {
        return $this->result->row_count();
    }
}