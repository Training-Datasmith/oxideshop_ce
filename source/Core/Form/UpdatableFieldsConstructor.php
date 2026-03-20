<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Form;

use Oxid_Esales\Eshop\Core\Contract\Abstract_Updatable_Fields;
use Oxid_Esales\Eshop\Core\Model\Field_Name_Helper;
/**
 * Provides creators for cleaners of fields which could be updated by a customer.
 */
class Updatable_Fields_Constructor
{
    /**
     * Get cleaner for field list which are allowed to be submitted in a form.
     *
     *
     * @return FormFieldsCleaner
     */
    public function get_allowed_fields_cleaner(Abstract_Updatable_Fields $updatable_fields)
    {
        $helper = ox_new(Field_Name_Helper::class);
        $allowed_fields = $helper->get_full_field_names($updatable_fields->get_table_name(), $updatable_fields->get_updatable_fields());
        $updatable_fields = ox_new(\Oxid_Esales\Eshop\Core\Form\Form_Fields::class, $allowed_fields);
        return ox_new(\Oxid_Esales\Eshop\Core\Form\Form_Fields_Cleaner::class, $updatable_fields);
    }
}