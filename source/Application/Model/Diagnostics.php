<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Diagnostic tool model
 * Stores configuration and public diagnostic methods for shop diagnostics
 */
class Diagnostics
{
    /**
     * Edition of THIS OXID eShop
     *
     * @var string
     */
    protected $_sEdition = '';

    /**
     * Version of THIS OXID eShop
     *
     * @var string
     */
    protected $_sVersion = '';

    /**
     * Revision of THIS OXID eShop
     *
     * @var string
     */
    protected $_sShopLink = '';

    /**
     * Version setter
     *
     * @param string $sVersion Version.
     */
    public function setVersion($sVersion): void
    {
        if (!empty($sVersion)) {
            $this->_sVersion = $sVersion;
        }
    }

    /**
     * Version getter
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_sVersion;
    }

    /**
     * Edition setter
     *
     * @param string $sEdition Edition
     */
    public function setEdition($sEdition): void
    {
        if (!empty($sEdition)) {
            $this->_sEdition = $sEdition;
        }
    }

    /**
     * Edition getter
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->_sEdition;
    }

    /**
     * ShopLink setter
     *
     * @param string $sShopLink Shop link.
     */
    public function setShopLink($sShopLink): void
    {
        if (!empty($sShopLink)) {
            $this->_sShopLink = $sShopLink;
        }
    }

    /**
     * ShopLink getter
     *
     * @return string
     */
    public function getShopLink()
    {
        return $this->_sShopLink;
    }

    /**
     * Collects information on the shop, like amount of categories, articles, users
     */
    public function getShopDetails(): array
    {
        return [
            'Date'                => date(\OxidEsales\Eshop\Core\Registry::getLang()->translateString('fullDateFormat'), time()),
            'URL'                 => $this->getShopLink(),
            'Edition'             => $this->getEdition(),
            'Version'             => $this->getVersion(),
            'Subshops (Total)'    => $this->countRows('oxshops', true),
            'Subshops (Active)'   => $this->countRows('oxshops', false),
            'Categories (Total)'  => $this->countRows('oxcategories', true),
            'Categories (Active)' => $this->countRows('oxcategories', false),
            'Articles (Total)'    => $this->countRows('oxarticles', true),
            'Articles (Active)'   => $this->countRows('oxarticles', false),
            'Users (Total)'       => $this->countRows('oxuser', true),
        ];
    }

    /**
     * counts result Rows
     *
     * @param string  $sTable table
     * @param boolean $blMode mode
     */
    protected function countRows(string $table, $mode): int
    {
        $query = sprintf('SELECT COUNT(*) FROM %s', $table);

        if ($mode == false) {
            $query .= ' WHERE oxactive = 1';
        }

        return (int) DatabaseProvider::getDb()->getOne($query);
    }

    /**
     * Picks some pre-selected PHP configuration settings and returns them.
     */
    public function getPhpSelection(): array
    {
        $aPhpIniParams = [
            'allow_url_fopen',
            'display_errors',
            'file_uploads',
            'max_execution_time',
            'memory_limit',
            'post_max_size',
            'register_globals',
            'upload_max_filesize',
        ];

        $aPhpIniConf = [];

        foreach ($aPhpIniParams as $sParam) {
            $sValue = ini_get($sParam);
            $aPhpIniConf[$sParam] = $sValue;
        }

        return $aPhpIniConf;
    }

    /**
     * Returns the installed PHP devoder (like Zend Optimizer, Guard Loader)
     */
    public function getPhpDecoder(): string
    {
        $sReturn = 'Zend ';

        if (function_exists('zend_optimizer_version')) {
            $sReturn .= 'Optimizer';
        }

        if (function_exists('zend_loader_enabled')) {
            $sReturn .= 'Guard Loader';
        }

        return $sReturn;
    }

    /**
     * General server information
     * We will use the exec command here several times. In order tro prevent stop on failure, use $this->isExecAllowed().
     */
    public function getServerInfo(): array
    {
        // init empty variables (can be filled if exec is allowed)
        $iMemTotal = $iMemFree = $sCpuModelName = $sCpuModel = $sCpuFreq = $iCpuCores = null;

        // fill, if exec is allowed
        if ($this->isExecAllowed()) {
            $iCpuAmnt = $this->getCpuAmount();
            $iCpuMhz = $this->getCpuMhz();
            $iBogo = $this->getBogoMips();
            $iMemTotal = $this->getMemoryTotal();
            $iMemFree = $this->getMemoryFree();
            $sCpuModelName = $this->getCpuModel();
            $sCpuModel = $iCpuAmnt . 'x ' . $sCpuModelName;
            $sCpuFreq = $iCpuMhz . ' MHz';

            // prevent "division by zero" error
            if ($iBogo && $iCpuMhz) {
                $iCpuCores = $iBogo / $iCpuMhz;
            }
        }

        return [
            'Server OS'     => @php_uname('s'),
            'VM'            => $this->getVirtualizationSystem(),
            'PHP'           => $this->getPhpVersion(),
            'MySQL'         => $this->getMySqlServerInfo(),
            'Apache'        => $this->getApacheVersion(),
            'Disk total'    => $this->getDiskTotalSpace(),
            'Disk free'     => $this->getDiskFreeSpace(),
            'Memory total'  => $iMemTotal,
            'Memory free'   => $iMemFree,
            'CPU Model'     => $sCpuModel,
            'CPU frequency' => $sCpuFreq,
            'CPU cores'     => round($iCpuCores, 0),
        ];
    }

    /**
     * Returns Apache version
     *
     * @return string
     */
    protected function getApacheVersion()
    {
        if (function_exists('apache_get_version')) {
            return apache_get_version();
        }

        return $_SERVER['SERVER_SOFTWARE'];
    }

    /**
     * Tries to find out which VM is used
     */
    protected function getVirtualizationSystem(): string
    {
        $sSystemType = '';

        if ($this->isExecAllowed()) {
            //VMWare
            @$sDeviceList = $this->getDeviceList('vmware');
            if ($sDeviceList) {
                $sSystemType = 'VMWare';
                unset($sDeviceList);
            }

            //VirtualBox
            @$sDeviceList = $this->getDeviceList('VirtualBox');
            if ($sDeviceList) {
                $sSystemType = 'VirtualBox';
                unset($sDeviceList);
            }
        }

        return $sSystemType;
    }

    /**
     * Determines, whether the exec() command is allowed or not.
     */
    public function isExecAllowed(): bool
    {
        return function_exists('exec');
    }

    /**
     * Finds the list of system devices for given system type
     *
     * @param string $sSystemType System type.
     *
     * @return string
     */
    protected function getDeviceList(string $sSystemType): string|false
    {
        return exec('lspci | grep -i ' . $sSystemType);
    }

    /**
     * Returns amount of CPU units.
     *
     * @return string
     */
    protected function getCpuAmount(): string|false
    {
        // cat /proc/cpuinfo | grep "processor" | sort -u | cut -d: -f2');
        return exec('cat /proc/cpuinfo | grep "physical id" | sort | uniq | wc -l');
    }

    /**
     * Returns CPU speed in Mhz
     */
    protected function getCpuMhz(): float
    {
        return round(exec('cat /proc/cpuinfo | grep "MHz" | sort -u | cut -d: -f2'), 0);
    }

    /**
     * Returns BogoMIPS evaluation of processor
     *
     * @return string
     */
    protected function getBogoMips(): string|false
    {
        return exec('cat /proc/cpuinfo | grep "bogomips" | sort -u | cut -d: -f2');
    }

    /**
     * Returns total amount of memory
     *
     * @return string
     */
    protected function getMemoryTotal(): string|false
    {
        return exec('cat /proc/meminfo | grep "MemTotal" | sort -u | cut -d: -f2');
    }

    /**
     * Returns amount of free memory
     *
     * @return string
     */
    protected function getMemoryFree(): string|false
    {
        return exec('cat /proc/meminfo | grep "MemFree" | sort -u | cut -d: -f2');
    }

    /**
     * Returns CPU model information
     *
     * @return string
     */
    protected function getCpuModel(): string|false
    {
        return exec('cat /proc/cpuinfo | grep "model name" | sort -u | cut -d: -f2');
    }

    /**
     * Returns total disk space
     */
    protected function getDiskTotalSpace(): string
    {
        return round(disk_total_space('/') / 1024 / 1024, 0) . ' GiB';
    }

    /**
     * Returns free disk space
     */
    protected function getDiskFreeSpace(): string
    {
        return round(disk_free_space('/') / 1024 / 1024, 0) . ' GiB';
    }

    /**
     * Returns PHP version
     */
    protected function getPhpVersion(): string
    {
        return phpversion();
    }

    /**
     * Returns MySQL server Information
     *
     * @return string
     */
    protected function getMySqlServerInfo()
    {
        $aResult = DatabaseProvider::getDb()->getRow("SHOW VARIABLES LIKE 'version'");

        return $aResult['Value'];
    }
}
