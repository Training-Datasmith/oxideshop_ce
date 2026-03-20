<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Factory;

use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Data_Object\Admin;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Email_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Rights_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Shop_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Shop_Adapter_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Hash\Service\Password_Hash_Service_Interface;
class Admin_Factory implements Admin_Factory_Interface
{
    public function __construct(private readonly Shop_Adapter_Interface $shop_adapter, private readonly Email_Validator_Service_Interface $email_validator_service, private readonly Password_Hash_Service_Interface $password_hash_service)
    {
    }
    /**
     * @throws InvalidEmailException
     * @throws InvalidRightsException
     * @throws InvalidShopException
     */
    public function create_admin(string $email, string $password, string $rights, int $shop_id): Admin
    {
        if (!$this->email_validator_service->is_email_valid($email)) {
            throw new Invalid_Email_Exception($email);
        }
        $this->check_rights($rights);
        if (!$this->shop_adapter->validate_shop_id($shop_id)) {
            throw new Invalid_Shop_Exception($shop_id);
        }
        return new Admin($this->shop_adapter->generate_unique_id(), $email, $this->password_hash_service->hash($password), $rights, $shop_id);
    }
    /**
     * @throws InvalidRightsException
     */
    private function check_rights(string $rights): void
    {
        if ($rights != Admin::MALL_ADMIN && !is_numeric($rights) && !$this->shop_adapter->validate_shop_id((int) $rights)) {
            throw new Invalid_Rights_Exception($rights);
        }
    }
}