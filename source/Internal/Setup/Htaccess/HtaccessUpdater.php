<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

use Oxid_Esales\Eshop_Community\Internal\Utility\Url\Url_Parser_Interface;
class Htaccess_Updater implements Htaccess_Updater_Interface
{
    private const REWRITE_BASE_FOR_EMPTY_PATH = '/';
    public function __construct(private readonly Htaccess_Dao_Factory_Interface $htaccess_dao_factory, private readonly Url_Parser_Interface $url_parser)
    {
    }
    /** @inheritDoc */
    public function update_rewrite_base_directive(Shop_Base_Url $shop_base_url): void
    {
        $this->htaccess_dao_factory->create_root_htaccess_dao()->set_rewrite_base($this->get_rewrite_base($shop_base_url->get_url()));
    }
    private function get_rewrite_base(string $url): string
    {
        return $this->url_parser->get_path_without_trailing_slash($url) ?: self::REWRITE_BASE_FOR_EMPTY_PATH;
    }
}