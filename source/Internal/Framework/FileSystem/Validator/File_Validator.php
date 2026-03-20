<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Validator;

use Symfony\Component\Mime\Mime_Types_Interface;
class File_Validator implements File_Validator_Interface
{
    public function __construct(private readonly Mime_Types_Interface $mime_types_service)
    {
    }
    public function validate_image(string $file_path): bool
    {
        try {
            if (!empty($file_path) && !str_starts_with(strtoupper($this->mime_types_service->guess_mime_type($file_path)), 'IMAGE/')) {
                return false;
            }
        } catch (\Exception) {
            throw new Image_Validation_Exception('Unable to get MimeType of file');
        }
        return true;
    }
}