<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Generator;

use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Exception\Unavailable_Source_Of_Randomness_Exception;
interface Random_Token_Generator_Interface
{
    /**
     * Generates random string of alphanumeric characters
     * @throws UnavailableSourceOfRandomnessException
     */
    public function get_alphanumeric_token(int $length): string;
    /**
     * Generates random string of hex characters
     * @throws UnavailableSourceOfRandomnessException
     */
    public function get_hex_token(int $length): string;
}