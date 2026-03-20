<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Generator;

use function base64_encode;
use function bin2hex;
use Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Authentication\Exception\Unavailable_Source_Of_Randomness_Exception;
use function random_bytes;
use function str_replace;
use function strlen;
use function substr;
class Random_Token_Generator implements Random_Token_Generator_Interface
{
    private const BASE_64_NON_ALPHANUMERIC_CHARACTERS = ['+', '/', '='];
    /** @inheritDoc */
    public function get_alphanumeric_token(int $length): string
    {
        $token = '';
        while (strlen($token) < $length) {
            $token .= $this->get_alphanumeric_string($length);
        }
        return substr($token, 0, $length);
    }
    /** @inheritDoc */
    public function get_hex_token(int $length): string
    {
        return substr($this->get_hex_string($length), 0, $length);
    }
    private function get_alphanumeric_string(int $length): string
    {
        $base64String = base64_encode($this->get_random_bytes($length));
        return $this->remove_non_alphanumeric_characters($base64String);
    }
    private function get_hex_string(int $length): string
    {
        return bin2hex($this->get_random_bytes($length));
    }
    private function remove_non_alphanumeric_characters(string $base64string): string
    {
        return str_replace(self::BASE_64_NON_ALPHANUMERIC_CHARACTERS, '', $base64string);
    }
    private function get_random_bytes(int $length): string
    {
        try {
            return random_bytes($length);
        } catch (Exception $exception) {
            throw new Unavailable_Source_Of_Randomness_Exception($exception);
        }
    }
}