<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Config\DataObject;

class ShopConfigurationSetting
{
    private ?int $shopId = null;

    private ?string $name = null;

    private ?string $type = null;

    /**
     * @var mixed
     */
    private $value;

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function setShopId(int $shopId): ShopConfigurationSetting
    {
        $this->shopId = $shopId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): ShopConfigurationSetting
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): ShopConfigurationSetting
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): ShopConfigurationSetting
    {
        $this->value = $value;
        return $this;
    }
}
