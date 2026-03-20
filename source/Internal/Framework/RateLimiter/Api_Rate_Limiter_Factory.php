<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Rate_Limiter;

use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Cache\Adapter\Filesystem_Adapter;
use Symfony\Component\Lock\Lock_Factory;
use Symfony\Component\Lock\Store\Flock_Store;
use Symfony\Component\Rate_Limiter\Limiter_Interface;
use Symfony\Component\Rate_Limiter\Rate_Limiter_Factory;
use Symfony\Component\Rate_Limiter\Storage\Cache_Storage;
class Api_Rate_Limiter_Factory implements Api_Rate_Limiter_Factory_Interface
{
    private ?Rate_Limiter_Factory $factory = null;
    public function __construct(private readonly int $limit, private readonly int $interval, private readonly string $policy, private readonly Basic_Context_Interface $context)
    {
    }
    public function create(string $client_identifier): Limiter_Interface
    {
        return $this->get_factory()->create($client_identifier);
    }
    private function get_factory(): Rate_Limiter_Factory
    {
        if ($this->factory === null) {
            $this->factory = $this->create_factory();
        }
        return $this->factory;
    }
    private function create_factory(): Rate_Limiter_Factory
    {
        $config = ['id' => 'api', 'policy' => $this->policy, 'limit' => $this->limit];
        if ($this->policy === 'token_bucket') {
            $config['rate'] = ['interval' => sprintf('%d seconds', $this->interval), 'amount' => $this->limit];
        } else {
            $config['interval'] = sprintf('%d seconds', $this->interval);
        }
        return new Rate_Limiter_Factory($config, $this->create_storage(), new Lock_Factory(new Flock_Store($this->context->get_cache_directory())));
    }
    private function create_storage(): Cache_Storage
    {
        $cache = new Filesystem_Adapter(namespace: 'rate_limiter', defaultLifetime: $this->interval * 2, directory: $this->context->get_cache_directory());
        return new Cache_Storage($cache);
    }
}