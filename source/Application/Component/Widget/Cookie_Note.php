<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * Cookie note widget
 */
class Cookie_Note extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'widget/header/cookienote';
    /**
     * Executes parent::render(). Check if need to hide cookie note.
     * Returns name of template file to render.
     *
     * @return  string  current template file name
     */
    public function render()
    {
        parent::render();
        return $this->_s_this_template;
    }
    /**
     * Return if cookie notification is enabled by config.
     *
     * @return boolean
     */
    public function is_enabled()
    {
        return (bool) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blShowCookiesNotification');
    }
}