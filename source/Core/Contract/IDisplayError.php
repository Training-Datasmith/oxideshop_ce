<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * DisplayError interface
 */
interface I_Display_Error
{
    /**
     * This method should return a localized message for displaying
     *
     * @return string A string to display to the user
     */
    public function get_ox_message();
    /**
     * Returns a type of the error, e.g. the class of the exception or whatever class
     * implemented this interface
     *
     * @return string The error type
     */
    public function get_error_class_type();
    /**
     * Possibility to access additional values
     *
     * @param string $sName Value name
     *
     * @return string An additional value (string) by its name
     */
    public function get_value($s_name);
}