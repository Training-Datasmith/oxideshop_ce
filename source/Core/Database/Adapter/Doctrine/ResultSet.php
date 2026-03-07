<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use OxidEsales\Eshop\Core\Database\Adapter\ResultSetInterface;
use Traversable;

/**
 * The doctrine statement wrapper, to support the old adodblite interface.
 *
 * @package OxidEsales\EshopCommunity\Core\Database\Adapter
 *
 * @deprecated since v6.5.0 (2019-09-24); Use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface
 */
class ResultSet implements \IteratorAggregate, ResultSetInterface
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
        $this->result = $this->statement->executeQuery();

        if ($this->count() === 0) {
            $this->setToEmptyState();
        }

        $this->fetchRow();
    }

    public function close(): void
    {
        $this->result->free();
        $this->fields = [];
    }

    public function fetchRow()
    {
        $this->fields = $this->result->fetchAssociative();

        if (false === $this->fields) {
            $this->EOF = true;
        }

        return $this->fields;
    }

    public function fetchAll()
    {
        $this->close();
        $this->statement->executeQuery();

        return $this->result->fetchAllAssociative();
    }

    public function fieldCount()
    {
        return $this->result->columnCount();
    }
    public function getIterator(): Traversable
    {
        $this->close();
        $this->statement->executeQuery();

        return $this->result->iterateAssociative();
    }

    public function getFields()
    {
        return $this->fields;
    }

    protected function getStatement(): \Doctrine\DBAL\Statement
    {
        return $this->statement;
    }

    protected function setStatement(Statement $statement)
    {
        $this->statement = $statement;
    }

    protected function setToEmptyState()
    {
        $this->EOF = true;
    }

    public function count(): int
    {
        return $this->result->rowCount();
    }
}
