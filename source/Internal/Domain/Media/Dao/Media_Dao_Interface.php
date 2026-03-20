<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Media\Dao;

use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Data_Object\Media;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
interface Media_Dao_Interface
{
    /** @throws EntryDoesNotExistDaoException */
    public function get(Id $id): Media;
    public function add(Media $media): void;
    public function delete(Id $id): void;
}