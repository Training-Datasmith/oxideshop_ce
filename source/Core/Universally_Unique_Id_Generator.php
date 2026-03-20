<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Core\Open_Ssl_Functionality_Checker;
/**
 * Class oxUniversallyUniqueIdGenerator used as universally unique id generator.
 */
class Universally_Unique_Id_Generator
{
    /**
     * @var OpenSSLFunctionalityChecker
     */
    private $_open_ssl_checker;
    /**
     * Sets dependencies.
     */
    public function __construct(?Open_Ssl_Functionality_Checker $open_ssl_checker = null)
    {
        if (is_null($open_ssl_checker)) {
            $open_ssl_checker = ox_new(Open_Ssl_Functionality_Checker::class);
        }
        $this->_open_ssl_checker = $open_ssl_checker;
    }
    /**
     * Generates UUID based on either openSSL's openssl_random_pseudo_bytes or mt_rand.
     */
    public function generate(): string
    {
        $s_seed = $this->generate_v4();
        return $this->generate_v5($s_seed, php_uname('n'));
    }
    /**
     * Generates version 4 UUID.
     */
    public function generate_v4(): string
    {
        if ($this->get_open_ssl_checker()->is_open_ssl_random_bytes_generator_available()) {
            return $this->generate_based_on_open_ssl();
        }
        return $this->generate_based_on_mt_rand();
    }
    /**
     * Generates version 5 UUID.
     *
     * @param string $sSeed
     *
     */
    public function generate_v5($s_seed, string $s_salt): string
    {
        $s_seed = str_replace(['-', '{', '}'], '', $s_seed);
        $s_binary_seed = '';
        for ($i = 0; $i < strlen($s_seed); $i += 2) {
            $s_binary_seed .= chr(hexdec($s_seed[$i] . $s_seed[$i + 1]));
        }
        $s_hash = sha1($s_binary_seed . $s_salt);
        return sprintf('%08s-%04s-%04x-%04x-%12s', substr($s_hash, 0, 8), substr($s_hash, 8, 4), hexdec(substr($s_hash, 12, 4)) & 0xfff | 0x3000, hexdec(substr($s_hash, 16, 4)) & 0x3fff | 0x8000, substr($s_hash, 20, 12));
    }
    /**
     * gets open SSL checker.
     *
     * @return OpenSSLFunctionalityChecker
     */
    protected function get_open_ssl_checker()
    {
        return $this->_open_ssl_checker;
    }
    /**
     * Generates UUID based on OpenSSL's openssl_random_pseudo_bytes.
     */
    protected function generate_based_on_open_ssl(): string
    {
        $s_random_data = openssl_random_pseudo_bytes(16);
        $s_random_data[6] = chr(ord($s_random_data[6]) & 0xf | 0x40);
        // set version to 0100
        $s_random_data[8] = chr(ord($s_random_data[8]) & 0x3f | 0x80);
        // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($s_random_data), 4));
    }
    /**
     * Generates UUID based on mt_rand.
     */
    protected function generate_based_on_mt_rand(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xfff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}