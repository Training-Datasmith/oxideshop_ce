<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Mini basket widget
 */
class Mini_Basket extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * User component used in template.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_cur' => 1, 'oxcmp_basket' => 1, 'oxcmp_user' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/header/minibasket';
}