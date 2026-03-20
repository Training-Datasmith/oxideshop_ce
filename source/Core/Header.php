<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * HTTP headers formator.
 * Collects HTTP headers and form HTTP header.
 */
class Header
{
    protected $_a_header = [];
    /**
     * Sets header.
     *
     * @param string $header header value.
     */
    public function set_header($header): void
    {
        $header = str_replace(["\n", "\r"], '', $header);
        $this->_a_header[] = $header . "\r\n";
    }
    /**
     * Return header.
     *
     * @return array
     */
    public function get_header()
    {
        return $this->_a_header;
    }
    /**
     * Outputs HTTP header.
     */
    public function send_header(): void
    {
        foreach ($this->_a_header as $header) {
            if (isset($header)) {
                header($header);
            }
        }
    }
    /**
     * Set to not cacheable.
     *
     * @todo check browser for different no-cache signs.
     */
    public function set_non_cacheable(): void
    {
        $header = 'Cache-Control: no-cache;';
        $this->set_header($header);
    }
}