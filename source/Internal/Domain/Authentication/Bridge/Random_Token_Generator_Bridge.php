<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Generator\Random_Token_Generator_Interface;
class Random_Token_Generator_Bridge implements Random_Token_Generator_Bridge_Interface
{
    public function __construct(private readonly Random_Token_Generator_Interface $random_token_generator)
    {
    }
    /** @inheritdoc */
    public function get_alphanumeric_token(int $length): string
    {
        return $this->random_token_generator->get_alphanumeric_token($length);
    }
    /** @inheritdoc */
    public function get_hex_token(int $length): string
    {
        return $this->random_token_generator->get_hex_token($length);
    }
}