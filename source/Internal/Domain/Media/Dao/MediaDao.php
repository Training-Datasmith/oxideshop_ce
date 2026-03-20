<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Dao;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Mapper\Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
use function sprintf;
readonly class Media_Dao implements Media_Dao_Interface
{
    private const MEDIA_TABLE = 'oxmedia';
    public function __construct(private Query_Builder_Factory_Interface $query_builder_factory, private Data_Mapper_Interface $data_mapper)
    {
    }
    public function get(Id $id): Media
    {
        $result = $this->query_builder_factory->create()->select('id', 'path', 'type')->from(self::MEDIA_TABLE)->where('id = :id')->set_parameter('id', $id)->execute_query()->fetch_associative();
        if (!$result) {
            throw new Entry_Does_Not_Exist_Dao_Exception(sprintf('Media entry with ID "%s" does not exist.', $id));
        }
        return $this->data_mapper->from_data($result);
    }
    public function add(Media $media): void
    {
        $this->query_builder_factory->create()->insert(self::MEDIA_TABLE)->values(['id' => ':id', 'path' => ':path', 'type' => ':type'])->set_parameters($this->data_mapper->to_data($media))->execute_statement();
    }
    public function delete(Id $id): void
    {
        $this->query_builder_factory->create()->delete(self::MEDIA_TABLE)->where('id = :id')->set_parameter('id', $id)->execute_statement();
    }
}