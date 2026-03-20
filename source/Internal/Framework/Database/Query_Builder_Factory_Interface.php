<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Database;

use Doctrine\DBAL\Query\Query_Builder;
/**
 * @stable
 * @see OxidEsales/EshopCommunity/Internal/README.md
 */
interface Query_Builder_Factory_Interface
{
    public function create(): Query_Builder;
}