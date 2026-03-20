<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Translarent shop manager (executed automatically), sets
 * registration information and current shop object.
 *
 * @subpackage oxcmp
 */
class Shop_Component extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Marking object as component
     *
     * @var bool
     */
    protected $_bl_is_component = true;
    /**
     * Executes parent::render() and returns active shop object.
     *
     * @return  object  $this->oActShop active shop object
     */
    public function render()
    {
        parent::render();
        $my_config = Registry::get_config();
        // is shop active?
        $o_shop = $my_config->get_active_shop();
        $s_active_field = 'oxshops__oxactive';
        $s_class_name = $my_config->get_active_view()->get_class_key();
        if (!$o_shop->{$s_active_field}->value && 'oxstart' != $s_class_name && !$this->is_admin()) {
            // redirect to offline if there is no active shop
            Registry::get_utils()->show_offline_page();
        }
        return $o_shop;
    }
}