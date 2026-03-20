<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Bridge\Newsletter_Recipients_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Newsletter\Data_Mapper\Newsletter_Recipients_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\File_Generator\Bridge\File_Generator_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Header\Bridge\Header_Generator_Bridge_Interface;
/**
 * Admin newsletter manager.
 * Returns template, that arranges template ("newsletter") to frame.
 * Admin Menu: Customer Info -> Newsletter.
 */
class Admin_Newsletter extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'newsletter';
    public function export(): void
    {
        $newsletter_recipients_list = $this->get_news_letter_recipients_list();
        $this->set_csv_header();
        $this->generate_csv($newsletter_recipients_list);
        $o_utils = Registry::get_utils();
        $o_utils->show_message_and_exit('');
    }
    private function get_news_letter_recipients_list(): array
    {
        return Container_Facade::get(Newsletter_Recipients_Dao_Bridge_Interface::class)->get_newsletter_recipients(Container_Facade::get(Context_Interface::class)->get_current_shop_id());
    }
    private function set_csv_header(): void
    {
        Container_Facade::get(Header_Generator_Bridge_Interface::class)->generate('Export_user_recipient_status_' . date('Y-m-d') . '.csv');
    }
    private function generate_csv(array $data): void
    {
        Container_Facade::get(File_Generator_Bridge_Interface::class)->generate('php://output', Container_Facade::get(Newsletter_Recipients_Data_Mapper_Interface::class)->map_recipient_list_data_to_array($data));
    }
}