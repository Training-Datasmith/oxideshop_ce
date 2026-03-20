<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Command;

use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Data_Object\Admin;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Email_Already_Taken_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Exception\Invalid_Email_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Service\Admin_User_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Interface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Argument;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
class Create_User_Command extends Command
{
    private const ADMIN_EMAIL = 'admin-email';
    private const ADMIN_PASSWORD = 'admin-password';
    public function __construct(private readonly Email_Validator_Service_Interface $email_validator_service, private readonly Admin_User_Service_Interface $admin_service, private readonly Basic_Context_Interface $basic_context)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->add_argument(self::ADMIN_EMAIL, Input_Argument::REQUIRED)->add_argument(self::ADMIN_PASSWORD, Input_Argument::REQUIRED);
        $this->set_description('Creates admin user');
    }
    /**
     * @throws InvalidEmailException
     * @throws EmailAlreadyTakenException
     */
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $this->validate_admin_email($input->get_argument(self::ADMIN_EMAIL));
        $output->writeln('<info>Creating administrator account...</info>');
        $this->create_admin($input);
        $output->writeln('<info>Administrator account has been created.</info>');
        return Command::SUCCESS;
    }
    /**
     * @throws InvalidEmailException
     */
    private function validate_admin_email(string $email): void
    {
        if (!$this->email_validator_service->is_email_valid($email)) {
            throw new Invalid_Email_Exception($email);
        }
    }
    private function create_admin(Input_Interface $input): void
    {
        $this->admin_service->create_admin($input->get_argument(self::ADMIN_EMAIL), $input->get_argument(self::ADMIN_PASSWORD), Admin::MALL_ADMIN, $this->basic_context->get_default_shop_id());
    }
}