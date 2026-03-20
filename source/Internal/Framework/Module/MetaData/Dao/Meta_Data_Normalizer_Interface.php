<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao;

interface Meta_Data_Normalizer_Interface
{
    /**
     * Normalize the array aModule in metadata.php
     *
     *
     */
    public function normalize_data(array $data): array;
}