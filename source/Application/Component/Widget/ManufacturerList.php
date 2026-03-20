<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Manufacturer list widget.
 * Forms Manufacturer list.
 */
class Manufacturer_List extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Names of components (classes) that are initiated and executed
     * before any other regular operation.
     * Cartegory component used in template.
     *
     * @var array
     */
    protected $_a_component_names = ['oxcmp_categories' => 1];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/footer/manufacturerlist';
}