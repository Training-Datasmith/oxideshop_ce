<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Api;

use Oxid_Esales\Eshop\Core\Shop_Id_Calculator;
use Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Logger_Service_Factory;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Response;
use Throwable;
class Exception_Handler
{
    public function handle(Throwable $throwable): void
    {
        (new Logger_Service_Factory(new Context(Shop_Id_Calculator::BASE_SHOP_ID)))->get_logger()->error($throwable);
        $error = filter_var(getenv('OXID_DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN) ? $throwable->get_message() : 'An error occurred';
        (new Json_Response(['error' => $error], Response::HTTP_INTERNAL_SERVER_ERROR))->send();
    }
}