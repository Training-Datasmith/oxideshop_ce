<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Data_Object;

/**
 * Class used as entity for server node information.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Application_Server
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
    private $last_frontend_usage;
    /**
     * Flag which stores timestamp.
     *
     * @var int
     */
    private $last_admin_usage;
    /**
     * Sets id.
     *
     * @param string $id
     */
    public function set_id($id): void
    {
        $this->id = $id;
    }
    /**
     * Gets id
     *
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }
    /**
     * Sets ip.
     *
     * @param string $ip
     */
    public function set_ip($ip): void
    {
        $this->ip = $ip;
    }
    /**
     * Gets ip.
     *
     * @return string
     */
    public function get_ip()
    {
        return $this->ip;
    }
    /**
     * Sets timestamp.
     *
     * @param int $timestamp
     */
    public function set_timestamp($timestamp): void
    {
        $this->timestamp = $timestamp;
    }
    /**
     * Gets timestamp.
     *
     * @return int
     */
    public function get_timestamp()
    {
        return $this->timestamp;
    }
    /**
     * Sets last admin usage.
     *
     * @param int|null $lastAdminUsage
     */
    public function set_last_admin_usage($last_admin_usage): void
    {
        $this->last_admin_usage = $last_admin_usage;
    }
    /**
     * Gets last admin usage.
     *
     * @return int|null
     */
    public function get_last_admin_usage()
    {
        return $this->last_admin_usage;
    }
    /**
     * Sets last frontend usage.
     *
     * @param int|null $lastFrontendUsage Admin server flag which stores timestamp.
     */
    public function set_last_frontend_usage($last_frontend_usage): void
    {
        $this->last_frontend_usage = $last_frontend_usage;
    }
    /**
     * Gets last frontend usage.
     *
     * @return int|null Frontend server flag which stores timestamp.
     */
    public function get_last_frontend_usage()
    {
        return $this->last_frontend_usage;
    }
    /**
     * Check if application server was in use during 24h period.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    public function is_in_use($current_timestamp): bool
    {
        return !$this->has_lifetime_expired($current_timestamp, self::SERVER_INFORMATION_TIME_LIFE);
    }
    /**
     * Check if application server availability check period is over.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    public function need_to_delete($current_timestamp): bool
    {
        return $this->has_lifetime_expired($current_timestamp, self::INACTIVE_SERVER_STORAGE_PERIOD);
    }
    /**
     * Check if application server information must be updated.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    public function need_to_update($current_timestamp): bool
    {
        if ($this->has_lifetime_expired($current_timestamp, self::SERVER_INFO_UPDATE_PERIOD)) {
            return true;
        }
        return !$this->is_server_time_valid($current_timestamp);
    }
    /**
     * Method checks if the hardware time was not rolled back.
     *
     * @param int $currentTimestamp The current timestamp.
     */
    private function is_server_time_valid($current_timestamp): bool
    {
        $timestamp = $this->get_timestamp();
        return $current_timestamp - $timestamp >= 0;
    }
    /**
     * Compare if the application server lifetime has exceeded given period.
     *
     * @param int $currentTimestamp The current timestamp.
     * @param int $periodTimestamp  The timestamp of period to check.
     */
    private function has_lifetime_expired($current_timestamp, int $period_timestamp): bool
    {
        $timestamp = $this->get_timestamp();
        return $current_timestamp - $timestamp >= $period_timestamp;
    }
}