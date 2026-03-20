<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
require_once __DIR__ . '/../bootstrap.php';
// initializes singleton config class
$my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
// executing maintenance tasks..
ox_new(\Oxid_Esales\Eshop\Application\Model\Maintenance::class)->execute();
// closing page, writing cache and so on..
$my_config->page_close();