<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Core\Exception;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Shop_Id_Calculator;
use Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Logger_Service_Factory;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context;
use function Ox_Trigger_Offline_Page_Display;
use Throwable;
class Exception_Handler
{
    public function __construct(private readonly bool $is_debug_mode = false)
    {
    }
    /**
     * @throws Throwable
     */
    public function handle_uncaught_exception(Throwable $exception): void
    {
        try {
            Registry::get_logger()->error($exception->get_message(), [$exception]);
        } catch (Throwable) {
            (new Logger_Service_Factory(new Context(Shop_Id_Calculator::BASE_SHOP_ID)))->get_logger()->error($exception);
        }
        if ($this->is_debug_mode || PHP_SAPI === 'cli') {
            throw $exception;
        }
        ox_trigger_offline_page_display();
        exit(1);
    }
}