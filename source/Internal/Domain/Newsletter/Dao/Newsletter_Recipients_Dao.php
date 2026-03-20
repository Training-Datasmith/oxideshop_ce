<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Dao;

use Doctrine\DBAL\Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Object\Newsletter_Recipient;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
class Newsletter_Recipients_Dao implements Newsletter_Recipients_Dao_Interface
{
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory)
    {
    }
    /**
     *
     * @return NewsletterRecipient[]
     * @throws Exception
     */
    public function get_newsletter_recipients(int $shop_id): array
    {
        $recipient_list = [];
        $subscribers_list = $this->get_subscribers_list($shop_id);
        foreach ($subscribers_list as $row) {
            $recipient = new Newsletter_Recipient();
            $recipient->set_salutation(trim((string) $row['Salutation']));
            $recipient->set_fist_name($this->decode_html_entities($row['Firstname']));
            $recipient->set_last_name($this->decode_html_entities($row['Lastname']));
            $recipient->set_email($row['Email']);
            $recipient->set_otp_in_state((string) $row['OptInState']);
            $recipient->set_country($row['Country']);
            $recipient->set_user_groups($this->decode_html_entities((string) $row['UserGroups']));
            $recipient_list[] = $recipient;
        }
        return $recipient_list;
    }
    private function decode_html_entities(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES, 'utf-8');
    }
    /**
     * @throws Exception
     */
    private function get_subscribers_list(int $shop_id): array
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->select('n.oxsal AS Salutation', 'n.oxfname AS Firstname', 'n.oxlname AS Lastname', 'u.oxusername AS Email', 'n.oxdboptin AS OptInState', 'c.oxtitle AS Country', 'GROUP_CONCAT(g.oxtitle ORDER BY g.oxtitle ASC) AS UserGroups')->from('oxnewssubscribed', 'n')->join('n', 'oxuser', 'u', 'u.oxid=n.oxuserid')->join('u', 'oxcountry', 'c', 'u.oxcountryid=c.oxid')->left_join('u', 'oxobject2group', 'o2g', 'u.oxid=o2g.oxobjectid')->left_join('o2g', 'oxgroups', 'g', 'o2g.oxgroupsid=g.oxid')->where('n.oxshopid = :shopId')->set_parameters(['shopId' => $shop_id])->group_by('n.oxsal, n.oxfname, n.oxlname, u.oxusername, n.oxdboptin, c.oxtitle, u.oxcreate')->add_order_by('u.oxcreate', 'ASC');
        return $query_builder->execute_query()->fetch_all_associative();
    }
}