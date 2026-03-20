<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Class reserved for extending (for customization - you can add you own fields, etc.).
 */
class Article_Userdef extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $this->_a_view_data['edit'] = $o_article;
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            if ($o_article->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // load object
            $o_article->load($sox_id);
        }
        return 'article_userdef';
    }
}