<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article crosselling/accesories manager.
 * Creates list of available articles, there is ability to assign or remove
 * assigning of article to crosselling/accesories with other products.
 * Admin Menu: Manage Products -> Articles -> Crosssell.
 */
class Article_Crossselling extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        // crossselling
        $this->create_category_tree('artcattree');
        // accessoires
        $this->create_category_tree('artcattree2');
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
            $o_article_crosselling_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Article_Crossselling_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_article_crosselling_ajax->get_columns();
            return 'popups/article_crossselling';
        }
        if ($i_aoc == 2) {
            $o_article_accessories_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Article_Accessories_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_article_accessories_ajax->get_columns();
            return 'popups/article_accessories';
        }
        return 'article_crossselling';
    }
}