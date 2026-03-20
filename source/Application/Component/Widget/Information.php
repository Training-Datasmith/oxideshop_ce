<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Component\Widget;

/**
 * List of additional shop information links widget.
 * Forms info link list.
 */
class Information extends \Oxid_Esales\Eshop\Application\Component\Widget\Widget_Controller
{
    /**
     * Current class template name
     *
     * @var string
     */
    protected $_s_this_template = 'widget/footer/info';
    /**
     * @var \OxidEsales\Eshop\Application\Model\ContentList
     */
    protected $_o_content_list;
    /**
     * Returns service keys.
     *
     * @return array
     */
    public function get_services_keys()
    {
        $o_content_list = $this->get_content_list();
        return $o_content_list->get_service_keys();
    }
    /**
     * Get services content list
     *
     * @return \OxidEsales\Eshop\Application\Model\ContentList
     */
    public function get_services_list()
    {
        $o_content_list = $this->get_content_list();
        $o_content_list->load_services();
        return $o_content_list;
    }
    /**
     * Returns content list object.
     *
     * @return \OxidEsales\Eshop\Application\Model\ContentList
     */
    protected function get_content_list()
    {
        if (!$this->_o_content_list) {
            $this->_o_content_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Content_List::class);
        }
        return $this->_o_content_list;
    }
}