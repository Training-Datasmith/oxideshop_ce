<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Defines and returns delivery and billing required fields.
 */
class Required_Address_Fields
{
    /**
     * Default required fields for use when not set in config.
     */
    private array $_a_default_required_fields = ['oxuser__oxfname', 'oxuser__oxlname', 'oxuser__oxstreetnr', 'oxuser__oxstreet', 'oxuser__oxzip', 'oxuser__oxcity'];
    /**
     * Required fields.
     *
     * @var array
     */
    private $_a_required_fields = [];
    /**
     * Sets default required fields either from config or from _aDefaultRequiredFields.
     */
    public function __construct()
    {
        $this->set_required_fields($this->_a_default_required_fields);
        $a_required_fields = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('aMustFillFields');
        if (is_array($a_required_fields)) {
            $this->set_required_fields($a_required_fields);
        }
    }
    /**
     * Sets all required fields.
     *
     * @param array $aRequiredFields
     */
    public function set_required_fields($a_required_fields): void
    {
        $this->_a_required_fields = $a_required_fields;
    }
    /**
     * Returns all required fields.
     *
     * @return array
     */
    public function get_required_fields()
    {
        return $this->_a_required_fields;
    }
    /**
     * Returns required fields for user address validation.
     */
    public function get_billing_fields(): array
    {
        $a_required_fields = $this->get_required_fields();
        return $this->filter_fields($a_required_fields, 'oxuser__');
    }
    /**
     * Returns required fields for delivery address validation.
     */
    public function get_delivery_fields(): array
    {
        $a_required_fields = $this->get_required_fields();
        return $this->filter_fields($a_required_fields, 'oxaddress__');
    }
    /**
     * Removes delivery fields from fields list.
     *
     *
     * @return mixed[]
     */
    private function filter_fields(array $a_fields, string $s_prefix): array
    {
        $a_allowed = [];
        foreach ($a_fields as $s_key => $s_value) {
            if (str_starts_with((string) $s_value, $s_prefix)) {
                $a_allowed[] = $a_fields[$s_key];
            }
        }
        return $a_allowed;
    }
}