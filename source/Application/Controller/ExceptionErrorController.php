<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

/**
 * Displays exception errors
 */
class Exception_Error_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'message/exception';
    /** @var array Remove loading of components on exception handling. */
    protected $_a_component_names = [];
    /**
     * Sets exception errros to template
     */
    public function display_exception_error(): void
    {
        $a_view_data = $this->get_view_data();
        //add all exceptions to display
        $a_errors = $this->get_errors();
        if (is_array($a_errors) && count($a_errors)) {
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->pass_all_errors_to_view($a_view_data, $a_errors);
        }
        $this->add_tpl_param('Errors', $a_view_data['Errors']);
        // resetting errors from session
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('Errors', []);
    }
    /**
     * return page errors array
     *
     * @return array
     */
    protected function get_errors()
    {
        $a_errors = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('Errors');
        if (null === $a_errors) {
            return [];
        }
        return $a_errors;
    }
}