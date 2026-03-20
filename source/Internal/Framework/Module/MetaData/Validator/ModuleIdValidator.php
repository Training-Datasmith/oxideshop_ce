<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Provider;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Exception\Module_Id_Not_Valid_Exception;
class Module_Id_Validator implements Meta_Data_Validator_Interface
{
    /**
     * @throws ModuleIdNotValidException
     */
    public function validate(array $meta_data): void
    {
        $meta_data_id = $meta_data[Meta_Data_Provider::METADATA_ID] ?? '';
        if ($meta_data_id === '') {
            throw new Module_Id_Not_Valid_Exception('Module ID is not provided in metadata file.');
        }
    }
}