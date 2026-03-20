<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Dao;

use Doctrine\Common\Collections\Array_Collection;
use Doctrine\DBAL\Query\Query_Builder;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Mapper\Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Sorting;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Connection_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
use function sprintf;
readonly class Product_Media_Dao implements Product_Media_Dao_Interface
{
    private const MEDIA_TABLE = 'oxmedia';
    private const PRODUCT_MEDIA_TABLE = 'oxproduct_media';
    private const PRODUCT_MEDIA_ROLES_TABLE = 'oxproduct_media_roles';
    public function __construct(private Query_Builder_Factory_Interface $query_builder_factory, private Connection_Factory_Interface $connection_factory, private Data_Mapper_Interface $product_media_data_mapper)
    {
    }
    public function add(Product_Media $product_media): void
    {
        if (!$product_media->has_position()) {
            $product_media->set_position($this->get_next_position($product_media->get_product_id()));
        }
        $data = $this->product_media_data_mapper->to_data($product_media);
        $this->query_builder_factory->create()->insert(self::PRODUCT_MEDIA_TABLE)->values(['id' => ':id', 'product_id' => ':product_id', 'media_id' => ':media_id', 'position' => ':position', 'active' => ':active'])->set_parameters($data)->execute_statement();
        $this->add_roles($product_media->get_id(), $data['roles']);
    }
    public function update(Product_Media $product_media): void
    {
        $this->get($product_media->get_id());
        $data = $this->product_media_data_mapper->to_data($product_media);
        $this->query_builder_factory->create()->update(self::PRODUCT_MEDIA_TABLE)->set('product_id', ':product_id')->set('media_id', ':media_id')->set('position', ':position')->set('active', ':active')->where('id = :id')->set_parameters($data)->execute_statement();
        $this->replace_roles($product_media->get_id(), $data['roles']);
    }
    public function delete(Id $id): void
    {
        $this->remove_roles($id);
        $this->query_builder_factory->create()->delete(self::PRODUCT_MEDIA_TABLE)->where('id = :id')->set_parameter('id', $id)->execute_statement();
    }
    public function sort(Product_Media_Sorting $sorting): void
    {
        $case_clauses = '';
        $parameters = [];
        $in_clause_placeholders = [];
        foreach ($sorting->get_sorting() as $position => $id) {
            $id_param_name = 'id_' . $position;
            $position_param_name = 'position_' . $position;
            $case_clauses .= sprintf(' WHEN :%s THEN :%s ', $id_param_name, $position_param_name);
            $parameters[$id_param_name] = (string) $id;
            $parameters[$position_param_name] = $position;
            $in_clause_placeholders[] = ':' . $id_param_name;
        }
        $query = sprintf('UPDATE `%s` SET `position` = CASE `id` %s END WHERE `id` IN (%s)', self::PRODUCT_MEDIA_TABLE, $case_clauses, implode(', ', $in_clause_placeholders));
        $this->connection_factory->create()->execute_statement($query, $parameters);
    }
    public function get(Id $id): Product_Media
    {
        $row = $this->prepare_select_with_join()->where('pm.id = :id')->set_parameter('id', $id)->execute_query()->fetch_associative();
        if (!isset($row['id'])) {
            throw new Entry_Does_Not_Exist_Dao_Exception(sprintf('Product media with ID %s was not found.', $id));
        }
        return $this->product_media_data_mapper->from_data($row);
    }
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all(Id $product_id): Array_Collection
    {
        return $this->get_all_by_active($product_id, false);
    }
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all_active(Id $product_id): Array_Collection
    {
        return $this->get_all_by_active($product_id, true);
    }
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all_by_role(Id $product_id, Product_Media_Role $role): Array_Collection
    {
        return $this->get_all_by_role_and_active($product_id, $role, false);
    }
    /** @return ArrayCollection<int, ProductMedia> */
    public function get_all_active_by_role(Id $product_id, Product_Media_Role $role): Array_Collection
    {
        return $this->get_all_by_role_and_active($product_id, $role, true);
    }
    public function get_by_role(Id $product_id, Product_Media_Role $role): ?Product_Media
    {
        return $this->get_by_role_and_active($product_id, $role, false);
    }
    public function get_active_by_role(Id $product_id, Product_Media_Role $role): ?Product_Media
    {
        return $this->get_by_role_and_active($product_id, $role, true);
    }
    public function get_active_by_position(Id $product_id, int $position): ?Product_Media
    {
        $row = $this->prepare_select_with_join()->where('pm.product_id = :productId')->and_where('pm.position = :position')->and_where('pm.active = 1')->set_parameter('productId', $product_id)->set_parameter('position', $position)->set_max_results(1)->execute_query()->fetch_associative();
        return $row ? $this->product_media_data_mapper->from_data($row) : null;
    }
    public function get_first_active(Id $product_id): ?Product_Media
    {
        $row = $this->prepare_select_with_join()->where('pm.product_id = :productId')->and_where('pm.active = 1')->set_parameter('productId', $product_id)->order_by('pm.position', 'ASC')->set_max_results(1)->execute_query()->fetch_associative();
        return $row ? $this->product_media_data_mapper->from_data($row) : null;
    }
    private function prepare_select_with_join(): Query_Builder
    {
        return $this->query_builder_factory->create()->select('pm.id as id', 'pm.product_id as product_id', 'pm.position as position', 'pm.active as active', 'm.id as media_id', 'm.path as media_path', 'm.type as media_mime_type', 'GROUP_CONCAT(pmr.role) as roles')->from(self::PRODUCT_MEDIA_TABLE, 'pm')->join('pm', self::MEDIA_TABLE, 'm', 'pm.media_id = m.id')->left_join('pm', self::PRODUCT_MEDIA_ROLES_TABLE, 'pmr', 'pm.id = pmr.product_media_id')->group_by('pm.id');
    }
    /** @return ArrayCollection<int, ProductMedia> */
    private function get_all_by_active(Id $product_id, bool $filter_active): Array_Collection
    {
        $collection = new Array_Collection();
        $query_builder = $this->prepare_select_with_join()->where('pm.product_id = :productId')->set_parameter('productId', $product_id)->order_by('pm.position', 'ASC');
        if ($filter_active) {
            $query_builder->and_where('pm.active = :active')->set_parameter('active', 1);
        }
        $rows = $query_builder->execute_query()->fetch_all_associative();
        foreach ($rows as $row) {
            $collection->add($this->product_media_data_mapper->from_data($row));
        }
        return $collection;
    }
    /** @return ArrayCollection<int, ProductMedia> */
    private function get_all_by_role_and_active(Id $product_id, Product_Media_Role $role, bool $only_active): Array_Collection
    {
        $collection = new Array_Collection();
        $query_builder = $this->prepare_select_with_join()->where('pm.product_id = :productId')->and_where('pmr.role = :role')->set_parameter('productId', $product_id)->set_parameter('role', $role->value())->order_by('pm.position', 'ASC');
        if ($only_active) {
            $query_builder->and_where('pm.active = :active')->set_parameter('active', 1);
        }
        $rows = $query_builder->execute_query()->fetch_all_associative();
        foreach ($rows as $row) {
            $collection->add($this->product_media_data_mapper->from_data($row));
        }
        return $collection;
    }
    private function get_by_role_and_active(Id $product_id, Product_Media_Role $role, bool $only_active): ?Product_Media
    {
        $query_builder = $this->prepare_select_with_join()->where('pm.product_id = :productId')->and_where('pmr.role = :role')->set_parameter('productId', $product_id)->set_parameter('role', $role->value())->order_by('pm.position', 'ASC')->set_max_results(1);
        if ($only_active) {
            $query_builder->and_where('pm.active = :active')->set_parameter('active', 1);
        }
        $row = $query_builder->execute_query()->fetch_associative();
        return $row ? $this->product_media_data_mapper->from_data($row) : null;
    }
    private function get_next_position(Id $product_id): int
    {
        $max_position = $this->query_builder_factory->create()->select('MAX(pm.position) as maxPosition')->from(self::PRODUCT_MEDIA_TABLE, 'pm')->where('pm.product_id = :productId')->set_parameter('productId', $product_id)->execute_query()->fetch_one();
        return $max_position === null ? 0 : ++$max_position;
    }
    private function remove_roles(Id $product_media_id): void
    {
        $this->query_builder_factory->create()->delete(self::PRODUCT_MEDIA_ROLES_TABLE)->where('product_media_id = :id')->set_parameter('id', $product_media_id)->execute_statement();
    }
    private function add_roles(Id $product_media_id, array $roles): void
    {
        if (empty($roles)) {
            return;
        }
        $insert_query = $this->query_builder_factory->create()->insert(self::PRODUCT_MEDIA_ROLES_TABLE)->values(['product_media_id' => ':product_media_id', 'role' => ':role']);
        foreach ($roles as $role) {
            $insert_query->set_parameters(['product_media_id' => $product_media_id, 'role' => $role])->execute_statement();
        }
    }
    private function replace_roles(Id $product_media_id, array $roles): void
    {
        $this->remove_roles($product_media_id);
        $this->add_roles($product_media_id, $roles);
    }
}