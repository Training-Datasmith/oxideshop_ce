<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Bridge;

use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Exception\Unavailable_Source_Of_Randomness_Exception;
interface Random_Token_Generator_Bridge_Interface
{
    /**
     * @throws UnavailableSourceOfRandomnessException
     */
    public function get_alphanumeric_token(int $length): string;
    /**
     * @throws UnavailableSourceOfRandomnessException
     */
    public function get_hex_token(int $length): string;
}