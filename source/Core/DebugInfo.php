<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Debug information formatter
 */
class Debug_Info
{
    /**
     * format template data for debug view
     *
     * @param array $viewData template data
     */
    public function format_template_data($view_data = []): string
    {
        $log = '';
        reset($view_data);
        foreach ($view_data as $view_name => $view_data_object) {
            // show debbuging information
            $log .= "TemplateData[{$view_name}] : <br />\n";
            $log .= print_r($view_data_object, 1);
        }
        return $log;
    }
    /**
     * format memory usage
     */
    public function format_memory_usage(): string
    {
        $log = '';
        if (function_exists('memory_get_usage')) {
            $kb = (int) (memory_get_usage() / 1024);
            $mb = round($kb / 1024, 3);
            $log .= 'Memory usage: ' . $mb . ' MB';
            if (function_exists('memory_get_peak_usage')) {
                $peak_kb = (int) (memory_get_peak_usage() / 1024);
                $peak_mb = round($peak_kb / 1024, 3);
                $log .= ' (peak: ' . $peak_mb . ' MB)';
            }
            $log .= '<br />';
            $kb = (int) (memory_get_usage(true) / 1024);
            $mb = round($kb / 1024, 3);
            $log .= 'System memory usage: ' . $mb . ' MB';
            if (function_exists('memory_get_peak_usage')) {
                $peak_kb = (int) (memory_get_peak_usage(true) / 1024);
                $peak_mb = round($peak_kb / 1024, 3);
                $log .= ' (peak: ' . $peak_mb . ' MB)';
            }
            $log .= '<br />';
        }
        return $log;
    }
    /**
     * format execution times
     *
     * @param double $dTotalTime total time
     */
    public function format_execution_time($d_total_time): string
    {
        $log = 'Execution time:' . round($d_total_time, 4) . '<br />';
        global $a_profile_times;
        global $execution_counts;
        if (is_array($a_profile_times)) {
            $log .= '----------------------------------------------------------<br>' . PHP_EOL;
            arsort($a_profile_times);
            $log .= "<table cellspacing='10px' style='border: 1px solid #000'>";
            foreach ($a_profile_times as $key => $val) {
                $log .= "<tr><td style='border-bottom: 1px dotted #000;min-width:300px;'>Profile {$key}: </td><td style='border-bottom: 1px dotted #000;min-width:100px;'>" . round($val, 5) . 's</td>';
                if ($d_total_time) {
                    $log .= "<td style='border-bottom: 1px dotted #000;min-width:100px;'>" . round($val * 100 / $d_total_time, 2) . '%</td>';
                }
                if ($execution_counts[$key]) {
                    $log .= " <td style='border-bottom: 1px dotted #000;min-width:50px;padding-right:30px;' align='right'>" . $execution_counts[$key] . '</td>' . "<td style='border-bottom: 1px dotted #000;min-width:15px; '>*</td>" . "<td style='border-bottom: 1px dotted #000;min-width:100px;'>" . round($val / $execution_counts[$key], 5) . 's</td>' . PHP_EOL;
                } else {
                    $log .= " <td colspan=3 style='border-bottom: 1px dotted #000;min-width:100px;'> not stopped correctly! </td>" . PHP_EOL;
                }
                $log .= '</tr>';
            }
            $log .= '</table>';
        }
        return $log;
    }
    /**
     * general info (debug title)
     */
    public function format_general_info(): string
    {
        $log = 'cl=' . \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view()->get_class_key();
        if ($fnc = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view()->get_fnc_name()) {
            $log .= " fnc={$fnc}";
        }
        return $log;
    }
    /**
     * Forms view name and timestamp to.
     */
    public function format_time_stamp(): string
    {
        $log = '';
        $class_name = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_view()->get_class_key();
        $log .= "<div id='" . $class_name . "_executed'>Executed: " . date('Y-m-d H:i:s') . '</div>';
        return $log . ("<div id='" . $class_name . "_timestamp'>Timestamp: " . microtime(true) . '</div>');
    }
}