<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use function array_fill_keys;
use function array_filter;
use function array_merge;
use Doctrine\DBAL\Driver_Manager;
use function is_array;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Connection_Parameter_Provider;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context;
use function unserialize;
class Shop_Id_Calculator
{
    public const BASE_SHOP_ID = 1;
    private array $url_map;
    public function __construct(private readonly \Oxid_Esales\Eshop\Core\Utils_Server $utils_server)
    {
    }
    public function get_shop_id(): int
    {
        return self::BASE_SHOP_ID;
    }
    protected function get_shop_url_map(): array
    {
        if (isset($this->url_map)) {
            return $this->url_map;
        }
        $url_map = [];
        foreach ($this->fetch_urls_from_config_table() as $row) {
            $shop_id = (int) $row['oxshopid'];
            $variable_name = $row['oxvarname'];
            $url_values = $row['oxvarvalue'];
            if ($variable_name === 'aLanguageURLs' || $variable_name === 'aLanguageSSLURLs') {
                $urls = unserialize($url_values, ['allowed_classes' => false]);
                if (is_array($urls) && count($urls)) {
                    $urls = array_filter($urls);
                    $urls = array_fill_keys($urls, $shop_id);
                    $url_map = array_merge($url_map, $urls);
                }
            } elseif ($url_values) {
                $url_map[$url_values] = $shop_id;
            }
        }
        $this->url_map = $url_map;
        return $url_map;
    }
    private function fetch_urls_from_config_table(): array
    {
        $connection = Driver_Manager::get_connection((new Connection_Parameter_Provider(new Basic_Context()))->get_parameters());
        $statement = $connection->prepare("SELECT oxshopid, oxvarname, oxvarvalue\n                FROM oxconfig\n                WHERE oxvarname IN ('aLanguageURLs', 'aLanguageSSLURLs', 'sMallShopURL','sMallSSLShopURL')");
        return $statement->execute_query()->fetch_all_associative();
    }
    protected function get_utils_server(): \Oxid_Esales\Eshop\Core\Utils_Server
    {
        return $this->utils_server;
    }
}