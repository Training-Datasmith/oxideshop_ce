<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core\DataObject;

/**
 * Class used as entity for server node information.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class ApplicationServer
{
    /**
     * Time in seconds, active server information life time.
     */
    public const SERVER_INFORMATION_TIME_LIFE = 86400;

    /**
     * Time in seconds, how long inactive server information will be stored.
     */
    public const INACTIVE_SERVER_STORAGE_PERIOD = 259200;

    /**
     * Time in seconds, how often server information must be updated.
     */
    public const SERVER_INFO_UPDATE_PERIOD = 86400;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * Flag which stores timestamp.
     *
     * @var int
     */
    private $lastFrontendUsage;

    /**
     * Flag which stores timestamp.
     *
     * @var int
     */
    private $lastAdminUsage;

    /**
     * Sets id.
     *
     * @param string $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Gets id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets ip.
     *
     * @param string $ip
     */
    public function setIp($ip): void
    {
        $this->ip = $ip;
    }

    /**
     * Gets ip.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Sets timestamp.
     *
     * @param int $timestamp
     */
    public function setTimestamp($timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Gets timestamp.
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Sets last admin usage.
     *
     * @param int|null $lastAdminUsage
     */
    public function setLastAdminUsage($lastAdminUsage): void
    {
        $this->lastAdminUsage = $lastAdminUsage;
    }

    /**
     * Gets last admin usage.
     *
     * @return int|null
     */
    public function getLastAdminUsage()
    {
        return $this->lastAdminUsage;
    }

    /**
     * Sets last frontend usage.
     *
     * @param int|null $lastFrontendUsage Admin server flag which stores timestamp.
     */
    public function setLastFrontendUsage($lastFrontendUsage): void
    {
        $this->lastFrontendUsage = $lastFrontendUsage;
    }

    /**
     * Gets last frontend usage.
     *
     * @return int|null Frontend server flag which stores timestamp.
     */
    public function getLastFrontendUsage()
    {
        return $this->lastFrontendUsage;
    }

    /**
     * Check if application server was in use during 24h period.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    public function isInUse($currentTimestamp): bool
    {
        return !$this->hasLifetimeExpired($currentTimestamp, self::SERVER_INFORMATION_TIME_LIFE);
    }

    /**
     * Check if application server availability check period is over.
     *
     * @param int $currentTimestamp The current timestamp.
     *
     * @return bool
     */
    public function needToDelete($currentTimestamp)
    {
        return $this->hasLifetimeExpired($currentTimestamp, self::INACTIVE_SERVER_STORAGE_PERIOD);
    }

    /**
     * Check if application server information must be updated.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    public function needToUpdate($currentTimestamp): bool
    {
        if ($this->hasLifetimeExpired($currentTimestamp, self::SERVER_INFO_UPDATE_PERIOD)) {
            return true;
        }
        return !$this->isServerTimeValid($currentTimestamp);
    }

    /**
     * Method checks if the hardware time was not rolled back.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    private function isServerTimeValid($currentTimestamp): bool
    {
        $timestamp = $this->getTimestamp();
        return ($currentTimestamp - $timestamp) >= 0;
    }

    /**
     * Compare if the application server lifetime has exceeded given period.
     *
     * @param int $currentTimestamp The current timestamp.
     * @param int $periodTimestamp  The timestamp of period to check.
     */
    private function hasLifetimeExpired($currentTimestamp, int $periodTimestamp): bool
    {
        $timestamp = $this->getTimestamp();
        return $currentTimestamp - $timestamp >= $periodTimestamp;
    }
}
