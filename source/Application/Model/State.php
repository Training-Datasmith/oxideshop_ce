<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

use oxDb;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

/**
 * State handler
 */
class State extends \OxidEsales\Eshop\Core\Model\MultiLanguageModel
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxstate';

    /**
     * Class constructor, initiates parent constructor (parent::oxI18n()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init("oxstates");
    }

    /**
     * Returns state id by code
     *
     * @param string $code      state code
     * @param string $countryId country id
     *
     * @return string
     */
    public function getIdByCode($code, $countryId)
    {
        return DatabaseProvider::getDb()->getOne(
            "SELECT oxid FROM oxstates WHERE oxid = :oxid AND oxcountryid = :oxcountryid",
            [
                'oxid' => $code,
                'oxcountryid' => $countryId
            ]
        );
    }

    /**
     * Get state title by id
     *
     * @param string $stateId
     *
     * @return string
     */
    public function getTitleById($stateId)
    {
        $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
        $query = "SELECT oxtitle FROM " . $tableViewNameGenerator->getViewName("oxstates") . " 
            WHERE oxid = :oxid";

        $stateTitle = DatabaseProvider::getDb()->getOne($query, [
            'oxid' => $stateId
        ]);

        return (string) $stateTitle;
    }
}
