<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
class Project_Configuration_Generator implements Project_Configuration_Generator_Interface
{
    public function __construct(private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Basic_Context_Interface $context)
    {
    }
    /**
     * Generates default project configuration.
     */
    public function generate(): void
    {
        $all_shop_ids = $this->context->get_all_shop_ids();
        $this->shop_configuration_dao->delete_all();
        foreach ($all_shop_ids as $shop_id) {
            $this->shop_configuration_dao->save(new Shop_Configuration(), $shop_id);
        }
    }
}