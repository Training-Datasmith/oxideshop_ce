<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Links manager.
 * Collects stored in DB links data (URL, description).
 */
class Links extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxlinks';
    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxlinks');
    }
    /**
     * Sets data field value
     *
     * @param string $sFieldName index OR name (eg. 'oxarticles__oxtitle') of a data field to set
     * @param string $sValue     value of data field
     * @param int    $iDataType  field type
     */
    protected function set_field_data($s_field_name, $s_value, $i_data_type = \Oxid_Esales\Eshop\Core\Field::T_TEXT)
    {
        if ('oxurldesc' === strtolower($s_field_name) || 'oxlinks__oxurldesc' === strtolower($s_field_name)) {
            $i_data_type = \Oxid_Esales\Eshop\Core\Field::T_RAW;
        }
        return parent::set_field_data($s_field_name, $s_value, $i_data_type);
    }
}