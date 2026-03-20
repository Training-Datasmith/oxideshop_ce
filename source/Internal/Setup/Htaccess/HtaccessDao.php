<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Setup\Htaccess;

class Htaccess_Dao implements Htaccess_Dao_Interface
{
    private const DIRECTIVE_REWRITE_BASE = 'RewriteBase';
    private string $contents;
    public function __construct(private readonly string $file_path)
    {
        $this->check_file_access();
    }
    public function set_rewrite_base(string $rewrite_base): void
    {
        $this->read_file();
        $this->update_directive(self::DIRECTIVE_REWRITE_BASE, $rewrite_base);
        $this->write_file();
    }
    private function read_file(): void
    {
        $this->contents = file_get_contents($this->file_path);
    }
    private function update_directive(string $key, string $value): void
    {
        $this->contents = preg_replace("/{$key}.*/", "{$key} {$value}", $this->contents);
    }
    private function write_file(): void
    {
        file_put_contents($this->file_path, $this->contents);
    }
    /** @throws HtaccessAccessException */
    private function check_file_access(): void
    {
        clearstatcache();
        if (!is_readable($this->file_path) || !is_writable($this->file_path)) {
            throw new Htaccess_Access_Exception("File not found or not accessible: `{$this->file_path}`");
        }
    }
}