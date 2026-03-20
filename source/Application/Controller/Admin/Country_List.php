<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin selectlist list manager.
 */
class Country_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxcountry';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxactive';
    /**
     * Default second SQL sorting parameter.
     *
     * @var string
     */
    protected $s_second_def_sort_field = 'oxtitle';
    /**
     * Enable/disable sorting by DESC (SQL) (default false - disable).
     *
     * @var bool
     */
    protected $_bl_desc = false;
    /**
     * Executes parent method parent::render() and returns name of template
     * file "selectlist_list".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        return 'country_list';
    }
    /**
     * Returns sorting fields array. We extend this method for getting a second order by, which will give us not the
     * undefined order behind the "active" countries.
     *
     * @return array
     */
    public function get_list_sorting()
    {
        $a_list_sorting = parent::get_list_sorting();
        if (array_keys($a_list_sorting['oxcountry']) === ['oxactive']) {
            $a_list_sorting['oxcountry'][$this->get_second_sort_field_name()] = 'asc';
        }
        return $a_list_sorting;
    }
    /**
     * Getter for the second sort field name (for getting the expected order out of the database).
     *
     * @return string The name of the field we want to be the second order by argument.
     */
    protected function get_second_sort_field_name()
    {
        return $this->s_second_def_sort_field;
    }
}