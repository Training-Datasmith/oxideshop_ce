<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
class Htaccess_Dao_Factory implements Htaccess_Dao_Factory_Interface
{
    private const FILENAME = '.htaccess';
    public function __construct(private readonly Basic_Context_Interface $basic_context)
    {
    }
    /**
     * @throws HtaccessAccessException
     */
    public function create_root_htaccess_dao(): Htaccess_Dao_Interface
    {
        return new Htaccess_Dao($this->get_root_htaccess_path());
    }
    /**
     * @throws HtaccessAccessException
     */
    private function get_root_htaccess_path(): string
    {
        clearstatcache();
        $path = realpath($this->basic_context->get_source_path() . DIRECTORY_SEPARATOR . self::FILENAME);
        if (!$path || !is_file($path)) {
            throw new Htaccess_Access_Exception(sprintf('Root %s file not found or not accessible', self::FILENAME));
        }
        return $path;
    }
}