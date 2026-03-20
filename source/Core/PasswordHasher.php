<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Hash password together with salt, using set hash algorithm
 *
 * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
 *                                        was added as the new default for hashing passwords. Hashing passwords with
 *                                        MD5 and SHA512 is still supported in order support login with older
 *                                        password hashes. Therefor this class might not be
 *                                        compatible with the current passhword hash any more.
 */
class Password_Hasher
{
    /**
     * @var \oxHasher
     */
    private $_ohasher;
    /**
     * Gets hasher.
     *
     * @return \OxidEsales\Eshop\Core\Hasher
     */
    protected function get_hasher()
    {
        return $this->_ohasher;
    }
    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Core\Hasher $oHasher hasher.
     */
    public function __construct($o_hasher)
    {
        $this->_ohasher = $o_hasher;
    }
    /**
     * Hash password with a salt.
     *
     * @param string $sPassword not hashed password.
     * @param string $sSalt     salt string.
     *
     * @return string
     */
    public function hash(string $s_password, string $s_salt)
    {
        return $this->get_hasher()->hash($s_password . $s_salt);
    }
}