<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database\Logger;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Exception\Admin_User_Not_Found_Exception;
readonly class Query_Log_Context_Extender implements Query_Log_Context_Extender_Interface
{
    public function __construct(private Context_Interface $shop_context)
    {
    }
    public function extend(array $query_context): array
    {
        $extended_context = ['adminUserId' => $this->get_admin_user_id_if_exists(), 'shopId' => $this->shop_context->get_current_shop_id(), 'trace' => debug_backtrace()];
        return array_merge($query_context, $extended_context);
    }
    private function get_admin_user_id_if_exists(): string
    {
        try {
            $admin_id = $this->shop_context->get_admin_user_id();
        } catch (Admin_User_Not_Found_Exception) {
            $admin_id = '';
        }
        return $admin_id;
    }
}