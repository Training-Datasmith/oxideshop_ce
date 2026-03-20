<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception;

use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Template_Logic\File_Size_Logic;
class File_Size_Too_Large_Exception extends Media_Validation_Exception
{
    public function __construct(private readonly int $actual_bytes, private readonly int $max_kb)
    {
        parent::__construct('File too large: ' . $actual_bytes . ' bytes, max ' . $max_kb . ' KB');
    }
    public function get_actual_bytes(): int
    {
        return $this->actual_bytes;
    }
    public function get_max_kb(): int
    {
        return $this->max_kb;
    }
    public function get_actual_formatted(): string
    {
        return (new File_Size_Logic())->get_file_size($this->actual_bytes);
    }
    public function get_max_formatted(): string
    {
        return (new File_Size_Logic())->get_file_size($this->max_kb * 1024);
    }
}