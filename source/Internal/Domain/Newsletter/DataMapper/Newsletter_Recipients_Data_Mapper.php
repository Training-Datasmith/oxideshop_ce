<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Object\Newsletter_Recipient;
/**
 * Class NewsletterRecipientsDataMapper
 */
class Newsletter_Recipients_Data_Mapper implements Newsletter_Recipients_Data_Mapper_Interface
{
    public const SALUTATION = 'Salutation';
    public const FIRST_NAME = 'Firstname';
    public const LAST_NAME = 'LastName';
    public const EMAIL = 'Email';
    public const OPT_IN_STATE = 'Opt-In state';
    public const COUNTRY = 'Country';
    public const ASSIGNED_USER_GROUPS = 'Assigned user groups';
    /**
     * @param NewsletterRecipient[] $newsletterRecipient
     */
    public function map_recipient_list_data_to_array(array $newsletter_recipient): array
    {
        $result = [[self::SALUTATION, self::FIRST_NAME, self::LAST_NAME, self::EMAIL, self::OPT_IN_STATE, self::COUNTRY, self::ASSIGNED_USER_GROUPS]];
        foreach ($newsletter_recipient as $value) {
            $result[] = [$value->get_salutation(), $value->get_fist_name(), $value->get_last_name(), $value->get_email(), $value->get_otp_in_state(), $value->get_country(), $value->get_user_groups()];
        }
        return $result;
    }
}