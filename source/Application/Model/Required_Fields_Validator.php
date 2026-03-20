<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Class for validating address
 */
class Required_Fields_Validator
{
    /**
     * Required fields array.
     *
     * @var array
     */
    private $_a_required_fields = [];
    /**
     * Invalid fields array.
     */
    private array $_a_invalid_fields = [];
    /**
     * Required Field validator.
     *
     * @var \OxidEsales\Eshop\Application\Model\RequiredFieldValidator
     */
    private $_o_field_validator = [];
    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Application\Model\RequiredFieldValidator $oFieldValidator
     */
    public function __construct($o_field_validator = null)
    {
        if (is_null($o_field_validator)) {
            $o_field_validator = ox_new(\Oxid_Esales\Eshop\Application\Model\Required_Field_Validator::class);
        }
        $this->set_field_validator($o_field_validator);
    }
    /**
     * Returns required fields for address.
     *
     * @return array
     */
    public function get_required_fields()
    {
        return $this->_a_required_fields;
    }
    /**
     * Sets required fields array
     *
     * @param array $aFields Fields
     */
    public function set_required_fields($a_fields): void
    {
        $this->_a_required_fields = $a_fields;
    }
    /**
     * Returns required fields for address.
     *
     * @return \OxidEsales\Eshop\Application\Model\RequiredFieldValidator
     */
    public function get_field_validator()
    {
        return $this->_o_field_validator;
    }
    /**
     * Sets required fields array
     *
     * @param \OxidEsales\Eshop\Application\Model\RequiredFieldValidator $oFieldValidator
     */
    public function set_field_validator($o_field_validator): void
    {
        $this->_o_field_validator = $o_field_validator;
    }
    /**
     * Gets invalid fields.
     */
    public function get_invalid_fields(): array
    {
        return $this->_a_invalid_fields;
    }
    /**
     * Checks if all required fields are filled.
     * Returns array of invalid fields or empty array if all fields are fine.
     *
     * @param \OxidEsales\Eshop\Core\Model\BaseModel $oObject Address fields with values.
     *
     * @return bool If any invalid field exist.
     */
    public function validate_fields($o_object): bool
    {
        $a_required_fields = $this->get_required_fields();
        $o_field_validator = $this->get_field_validator();
        $a_invalid_fields = [];
        foreach ($a_required_fields as $s_field_name) {
            if (!$o_field_validator->validate_field_value($o_object->get_field_data($s_field_name))) {
                $a_invalid_fields[] = $s_field_name;
            }
        }
        $this->set_invalid_fields($a_invalid_fields);
        return empty($a_invalid_fields);
    }
    /**
     * Add fields to invalid fields array.
     *
     * @param array $aFields Invalid field name.
     */
    private function set_invalid_fields(array $a_fields): void
    {
        $this->_a_invalid_fields = $a_fields;
    }
}