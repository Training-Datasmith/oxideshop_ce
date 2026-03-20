<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Static class mostly containing static methods which are supposed to be called before the full framework initialization
 */
class Oxid
{
    /**
     * Executes main shop controller
     *
     * @static
     *
     * @return void
     */
    public static function run()
    {
        /** @var ShopControl $shopControl */
        $shop_control = ox_new(\Oxid_Esales\Eshop\Core\Shop_Control::class);
        return $shop_control->start();
    }
    /**
     * Executes shop widget controller
     *
     * @static
     *
     * @return void
     */
    public static function run_widget()
    {
        /** @var WidgetControl $widgetControl */
        $widget_control = ox_new(\Oxid_Esales\Eshop\Core\Widget_Control::class);
        return $widget_control->start();
    }
}