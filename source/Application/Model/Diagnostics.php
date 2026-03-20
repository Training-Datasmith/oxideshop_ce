<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Database_Provider;
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
    protected $_s_edition = '';
    /**
     * Version of THIS OXID eShop
     *
     * @var string
     */
    protected $_s_version = '';
    /**
     * Revision of THIS OXID eShop
     *
     * @var string
     */
    protected $_s_shop_link = '';
    /**
     * Version setter
     *
     * @param string $sVersion Version.
     */
    public function set_version($s_version): void
    {
        if (!empty($s_version)) {
            $this->_s_version = $s_version;
        }
    }
    /**
     * Version getter
     *
     * @return string
     */
    public function get_version()
    {
        return $this->_s_version;
    }
    /**
     * Edition setter
     *
     * @param string $sEdition Edition
     */
    public function set_edition($s_edition): void
    {
        if (!empty($s_edition)) {
            $this->_s_edition = $s_edition;
        }
    }
    /**
     * Edition getter
     *
     * @return string
     */
    public function get_edition()
    {
        return $this->_s_edition;
    }
    /**
     * ShopLink setter
     *
     * @param string $sShopLink Shop link.
     */
    public function set_shop_link($s_shop_link): void
    {
        if (!empty($s_shop_link)) {
            $this->_s_shop_link = $s_shop_link;
        }
    }
    /**
     * ShopLink getter
     *
     * @return string
     */
    public function get_shop_link()
    {
        return $this->_s_shop_link;
    }
    /**
     * Collects information on the shop, like amount of categories, articles, users
     */
    public function get_shop_details(): array
    {
        return ['Date' => date(\Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('fullDateFormat'), time()), 'URL' => $this->get_shop_link(), 'Edition' => $this->get_edition(), 'Version' => $this->get_version(), 'Subshops (Total)' => $this->count_rows('oxshops', true), 'Subshops (Active)' => $this->count_rows('oxshops', false), 'Categories (Total)' => $this->count_rows('oxcategories', true), 'Categories (Active)' => $this->count_rows('oxcategories', false), 'Articles (Total)' => $this->count_rows('oxarticles', true), 'Articles (Active)' => $this->count_rows('oxarticles', false), 'Users (Total)' => $this->count_rows('oxuser', true)];
    }
    /**
     * counts result Rows
     *
     * @param string  $sTable table
     * @param boolean $blMode mode
     */
    protected function count_rows(string $table, $mode): int
    {
        $query = sprintf('SELECT COUNT(*) FROM %s', $table);
        if ($mode == false) {
            $query .= ' WHERE oxactive = 1';
        }
        return (int) Database_Provider::get_db()->get_one($query);
    }
    /**
     * Picks some pre-selected PHP configuration settings and returns them.
     */
    public function get_php_selection(): array
    {
        $a_php_ini_params = ['allow_url_fopen', 'display_errors', 'file_uploads', 'max_execution_time', 'memory_limit', 'post_max_size', 'register_globals', 'upload_max_filesize'];
        $a_php_ini_conf = [];
        foreach ($a_php_ini_params as $s_param) {
            $s_value = ini_get($s_param);
            $a_php_ini_conf[$s_param] = $s_value;
        }
        return $a_php_ini_conf;
    }
    /**
     * Returns the installed PHP devoder (like Zend Optimizer, Guard Loader)
     */
    public function get_php_decoder(): string
    {
        $s_return = 'Zend ';
        if (function_exists('zend_optimizer_version')) {
            $s_return .= 'Optimizer';
        }
        if (function_exists('zend_loader_enabled')) {
            $s_return .= 'Guard Loader';
        }
        return $s_return;
    }
    /**
     * General server information
     * We will use the exec command here several times. In order tro prevent stop on failure, use $this->isExecAllowed().
     */
    public function get_server_info(): array
    {
        // init empty variables (can be filled if exec is allowed)
        $i_mem_total = $i_mem_free = $s_cpu_model_name = $s_cpu_model = $s_cpu_freq = $i_cpu_cores = null;
        // fill, if exec is allowed
        if ($this->is_exec_allowed()) {
            $i_cpu_amnt = $this->get_cpu_amount();
            $i_cpu_mhz = $this->get_cpu_mhz();
            $i_bogo = $this->get_bogo_mips();
            $i_mem_total = $this->get_memory_total();
            $i_mem_free = $this->get_memory_free();
            $s_cpu_model_name = $this->get_cpu_model();
            $s_cpu_model = $i_cpu_amnt . 'x ' . $s_cpu_model_name;
            $s_cpu_freq = $i_cpu_mhz . ' MHz';
            // prevent "division by zero" error
            if ($i_bogo && $i_cpu_mhz) {
                $i_cpu_cores = $i_bogo / $i_cpu_mhz;
            }
        }
        return ['Server OS' => @php_uname('s'), 'VM' => $this->get_virtualization_system(), 'PHP' => $this->get_php_version(), 'MySQL' => $this->get_my_sql_server_info(), 'Apache' => $this->get_apache_version(), 'Disk total' => $this->get_disk_total_space(), 'Disk free' => $this->get_disk_free_space(), 'Memory total' => $i_mem_total, 'Memory free' => $i_mem_free, 'CPU Model' => $s_cpu_model, 'CPU frequency' => $s_cpu_freq, 'CPU cores' => round($i_cpu_cores, 0)];
    }
    /**
     * Returns Apache version
     *
     * @return string
     */
    protected function get_apache_version()
    {
        if (function_exists('apache_get_version')) {
            return apache_get_version();
        }
        return $_SERVER['SERVER_SOFTWARE'];
    }
    /**
     * Tries to find out which VM is used
     */
    protected function get_virtualization_system(): string
    {
        $s_system_type = '';
        if ($this->is_exec_allowed()) {
            //VMWare
            @$s_device_list = $this->get_device_list('vmware');
            if ($s_device_list) {
                $s_system_type = 'VMWare';
                unset($s_device_list);
            }
            //VirtualBox
            @$s_device_list = $this->get_device_list('VirtualBox');
            if ($s_device_list) {
                $s_system_type = 'VirtualBox';
                unset($s_device_list);
            }
        }
        return $s_system_type;
    }
    /**
     * Determines, whether the exec() command is allowed or not.
     */
    public function is_exec_allowed(): bool
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
    protected function get_device_list(string $s_system_type): string|false
    {
        return exec('lspci | grep -i ' . $s_system_type);
    }
    /**
     * Returns amount of CPU units.
     *
     * @return string
     */
    protected function get_cpu_amount(): string|false
    {
        // cat /proc/cpuinfo | grep "processor" | sort -u | cut -d: -f2');
        return exec('cat /proc/cpuinfo | grep "physical id" | sort | uniq | wc -l');
    }
    /**
     * Returns CPU speed in Mhz
     */
    protected function get_cpu_mhz(): float
    {
        return round(exec('cat /proc/cpuinfo | grep "MHz" | sort -u | cut -d: -f2'), 0);
    }
    /**
     * Returns BogoMIPS evaluation of processor
     *
     * @return string
     */
    protected function get_bogo_mips(): string|false
    {
        return exec('cat /proc/cpuinfo | grep "bogomips" | sort -u | cut -d: -f2');
    }
    /**
     * Returns total amount of memory
     *
     * @return string
     */
    protected function get_memory_total(): string|false
    {
        return exec('cat /proc/meminfo | grep "MemTotal" | sort -u | cut -d: -f2');
    }
    /**
     * Returns amount of free memory
     *
     * @return string
     */
    protected function get_memory_free(): string|false
    {
        return exec('cat /proc/meminfo | grep "MemFree" | sort -u | cut -d: -f2');
    }
    /**
     * Returns CPU model information
     *
     * @return string
     */
    protected function get_cpu_model(): string|false
    {
        return exec('cat /proc/cpuinfo | grep "model name" | sort -u | cut -d: -f2');
    }
    /**
     * Returns total disk space
     */
    protected function get_disk_total_space(): string
    {
        return round(disk_total_space('/') / 1024 / 1024, 0) . ' GiB';
    }
    /**
     * Returns free disk space
     */
    protected function get_disk_free_space(): string
    {
        return round(disk_free_space('/') / 1024 / 1024, 0) . ' GiB';
    }
    /**
     * Returns PHP version
     */
    protected function get_php_version(): string
    {
        return phpversion();
    }
    /**
     * Returns MySQL server Information
     *
     * @return string
     */
    protected function get_my_sql_server_info()
    {
        $a_result = Database_Provider::get_db()->get_row("SHOW VARIABLES LIKE 'version'");
        return $a_result['Value'];
    }
}