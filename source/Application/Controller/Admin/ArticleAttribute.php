<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article attributes/selections lists manager.
 * Collects available attributes/selections lists for chosen article, may add
 * or remove any of them to article, etc.
 * Admin Menu: Manage Products -> Articles -> Selection.
 */
class Article_Attribute extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_article->load($sox_id);
            if ($o_article->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        $i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
        if ($i_aoc == 1) {
            $o_article_attribute_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Article_Attribute_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_article_attribute_ajax->get_columns();
            return 'popups/article_attribute';
        }
        if ($i_aoc == 2) {
            $o_article_selection_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Article_Selection_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_article_selection_ajax->get_columns();
            return 'popups/article_selection';
        }
        return 'article_attribute';
    }
}