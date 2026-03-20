<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Config\Utility;

interface Shop_Setting_Encoder_Interface
{
    /**
     * @param mixed  $value
     * @return mixed
     */
    public function encode(string $encoding_type, $value);
    /**
     * @param mixed  $value
     * @return mixed
     */
    public function decode(string $encoding_type, $value);
}