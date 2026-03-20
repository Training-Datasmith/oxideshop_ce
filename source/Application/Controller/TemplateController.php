<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Template preparation class.
 * Used only in some specific cases (usually when you need to outpt just template
 * having text information).
 */
class Template_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        // security fix so that you cant access files from outside template dir
        $s_tpl_name = basename((string) Registry::get_request()->get_request_escaped_parameter('tpl'));
        if ($s_tpl_name) {
            return 'custom/' . $s_tpl_name;
        }
        return $s_tpl_name;
    }
}