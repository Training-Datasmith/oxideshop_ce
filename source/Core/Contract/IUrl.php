<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * Interface for object URLs getters
 */
interface I_Url
{
    /**
     * Returns object URL. If SEO if ON returned link will be in SEO form,
     * else URL will have dynamic form
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function get_link($i_lang = null);
    /**
     * Returns standard (dynamic) object URL
     *
     * @param int   $iLang   language id [optional]
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function get_std_link($i_lang = null, $a_params = []);
    /**
     * Returns base dynamic url: e.g. shopurl/index.php?cl=details&anid=artid
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function get_base_std_link($i_lang, $bl_add_id = true, $bl_full = true);
}