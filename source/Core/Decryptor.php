<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class oxDecryptor
 */
class Decryptor
{
    /**
     * Decrypts string with given key.
     *
     * @param string $string string
     * @param string $key    key
     */
    public function decrypt($string, $key): string
    {
        $key = $this->form_key($key, $string);
        $string = substr($string, 3);
        $string = str_replace('!', '=', $string);
        $string = base64_decode($string);
        $string = $string ^ $key;
        return substr($string, 2, -2);
    }
    /**
     * Forms key for use in encoding.
     *
     * @param string $key
     * @param string $string
     */
    protected function form_key($key, $string): string
    {
        $key = '_' . $key;
        $key_length = (int) (strlen($string) / strlen($key)) + 5;
        return str_repeat($key, $key_length);
    }
}