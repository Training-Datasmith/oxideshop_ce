<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class oxEncryptor
 */
class Encryptor
{
    /**
     * Encrypts string with given key.
     *
     * @param string $string
     * @param string $key
     */
    public function encrypt($string, $key): string
    {
        $string = "ox{$string}id";
        $key = $this->form_key($key, $string);
        $string = $string ^ $key;
        $string = base64_encode($string);
        $string = str_replace('=', '!', $string);
        return "ox_{$string}";
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