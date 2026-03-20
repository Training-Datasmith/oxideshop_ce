<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

/**
 * Special page for Credits
 */
class Credits_Controller extends \Oxid_Esales\Eshop\Application\Controller\Content_Controller
{
    /**
     * Content id.
     *
     * @var string
     */
    protected $_s_content_id = 'oxcredits';
    /**
     * Returns active content id to load its seo meta info
     *
     * @return string
     */
    protected function get_seo_object_id()
    {
        return $this->get_content_id();
    }
    /**
     * Template variable getter. Returns active content
     *
     * @return object
     */
    public function get_content()
    {
        if ($this->_o_content === null) {
            $this->_o_content = false;
            $o_content = ox_new(\Oxid_Esales\Eshop\Application\Model\Content::class);
            if ($o_content->load_by_ident($this->get_content_id())) {
                $this->_o_content = $o_content;
            }
        }
        return $this->_o_content;
    }
}