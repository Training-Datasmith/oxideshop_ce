<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Dao;

use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Data_Object\Admin;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Email_Already_Taken_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
class Admin_Dao implements Admin_Dao_Interface
{
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory)
    {
    }
    /**
     * @throws EmailAlreadyTakenException
     */
    public function create(Admin $admin): void
    {
        $this->check_email_not_taken($admin->get_email(), $admin->get_shop_id());
        $query_builder = $this->query_builder_factory->create();
        $query_builder->insert('oxuser')->values(['OXID' => ':OXID', 'OXUSERNAME' => ':OXUSERNAME', 'OXPASSWORD' => ':OXPASSWORD', 'OXRIGHTS' => ':OXRIGHTS', 'OXSHOPID' => ':OXSHOPID'])->set_parameters(['OXID' => $admin->get_id(), 'OXUSERNAME' => $admin->get_email(), 'OXPASSWORD' => $admin->get_password_hash(), 'OXRIGHTS' => $admin->get_rights(), 'OXSHOPID' => $admin->get_shop_id()]);
        $query_builder->execute_statement();
    }
    /**
     * @throws EmailAlreadyTakenException
     */
    private function check_email_not_taken(string $email, int $shop_id): void
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->select('1')->from('oxuser')->where('OXUSERNAME = :OXUSERNAME')->and_where('OXSHOPID = :OXSHOPID')->set_parameters(['OXUSERNAME' => $email, 'OXSHOPID' => $shop_id])->set_max_results(1);
        if ($query_builder->execute_query()->fetch_one()) {
            throw new Email_Already_Taken_Exception("Can not create an admin, the email '{$email}' is already in use.");
        }
    }
}