<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Factory class responsible for redirecting string handling functions to specific
 * string handling class. String handler basically is intended for dealing with multibyte string
 * and is NOT supposed to replace all string handling functions.
 * We use the handler for shop data and user input, but prefer not to use it for ascii strings
 * (eg. field or file names).
 */
class Str
{
    /**
     * Specific string handler
     *
     * @var \OxidEsales\Eshop\Core\StrMb|\OxidEsales\Eshop\Core\StrRegular
     */
    protected static $_o_handler;
    /**
     * Static method initializing new string handler or returning the existing one.
     *
     * @return \OxidEsales\Eshop\Core\StrMb|\OxidEsales\Eshop\Core\StrRegular
     */
    public static function get_str()
    {
        if (!isset(self::$_o_handler)) {
            //let's init now non-static instance of oxStr to get the instance of str handler
            self::$_o_handler = ox_new(\Oxid_Esales\Eshop\Core\Str::class)->get_str_handler();
        }
        return self::$_o_handler;
    }
    /**
     * Non static getter returning str handler. The sense of getStr() and _getStrHandler() is
     * to be possible to call this method statically ( \OxidEsales\Eshop\Core\Str::getStr() ), yet leaving the
     * possibility to extend it in modules by overriding _getStrHandler() method.
     *
     * @return \OxidEsales\Eshop\Core\StrMb|\OxidEsales\Eshop\Core\StrRegular
     */
    protected function get_str_handler()
    {
        if (function_exists('mb_strlen')) {
            return ox_new(\Oxid_Esales\Eshop\Core\Str_Mb::class);
        }
        return ox_new(\Oxid_Esales\Eshop\Core\Str_Regular::class);
    }
}