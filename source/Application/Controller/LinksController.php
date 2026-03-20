<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

/**
 * Interesting, useful links window.
 * Arranges interesting links window (contents may be changed in
 * administrator GUI) with short link description and URL. OXID
 * eShop -> LINKS.
 */
class Links_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/info/links';
    /**
     * Links list.
     *
     * @var object
     */
    protected $_o_links_list;
    /**
     * Template variable getter. Returns links list
     *
     * @return object
     */
    public function get_links_list()
    {
        if ($this->_o_links_list === null) {
            $this->_o_links_list = false;
            // Load links
            $o_links_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_links_list->init('oxlinks');
            $o_links_list->get_list();
            $this->_o_links_list = $o_links_list;
        }
        return $this->_o_links_list;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $a_paths = [];
        $a_path = [];
        $i_base_language = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        $a_path['title'] = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('LINKS', $i_base_language, false);
        $a_path['link'] = $this->get_link();
        $a_paths[] = $a_path;
        return $a_paths;
    }
}