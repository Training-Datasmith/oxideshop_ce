<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin article categories thumbnail manager.
 * Category thumbnail manager (Previews assigned pictures).
 * Admin Menu: Manage Products -> Categories -> Thumbnail.
 */
class Category_Pictures extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_category->load($sox_id);
        }
        return 'category_pictures';
    }
}