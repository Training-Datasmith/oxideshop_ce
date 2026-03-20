<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin user articles setting manager.
 * Collects user articles settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> Articles.
 */
class User_Article extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Executes parent method parent::render(), creates oxlist object and returns name
     * of template file "user_article".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if ($sox_id && $sox_id != '-1') {
            // load object
            $o_articlelist = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_Article_List::class);
            $o_articlelist->load_order_articles_for_user($sox_id);
            $this->_a_view_data['oArticlelist'] = $o_articlelist;
        }
        return 'user_article';
    }
}