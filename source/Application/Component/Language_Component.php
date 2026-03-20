<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

/**
 * Shop language manager.
 * Performs language manager function: changes template settings, modifies URL's.
 *
 * @subpackage oxcmp
 */
class Language_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Executes parent::render() and returns array with languages.
     *
     * @return array $this->aLanguages languages
     */
    public function render()
    {
        parent::render();
        // Performance
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadLanguages')) {
            $a_languages = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_language_array(null, true, true);
            reset($a_languages);
            foreach ($a_languages as $o_val) {
                $o_val->link = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_top_active_view()->get_link($o_val->id);
            }
            return $a_languages;
        }
    }
}