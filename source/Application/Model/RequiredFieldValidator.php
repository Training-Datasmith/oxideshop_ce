<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

/**
 * Class for validating address
 */
class RequiredFieldValidator
{
    /**
     * Validates field value.
     *
     * @param string $sFieldValue Field value
     *
     * @return bool
     */
    public function validateFieldValue($sFieldValue)
    {
        $blValid = true;
        if (is_array($sFieldValue)) {
            $blValid = $this->validateFieldValueArray($sFieldValue);
        } else {
            if (!trim((string) ($sFieldValue ?? ''))) {
                $blValid = false;
            }
        }

        return $blValid;
    }

    /**
     * Checks if all values are filled up
     *
     * @param array $aFieldValues field values
     *
     * @return bool
     */
    private function validateFieldValueArray(array $aFieldValues)
    {
        $blValid = true;
        foreach ($aFieldValues as $sValue) {
            if (!trim((string) $sValue)) {
                $blValid = false;
                break;
            }
        }

        return $blValid;
    }
}
