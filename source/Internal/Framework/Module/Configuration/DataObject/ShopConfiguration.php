<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;

class ShopConfiguration
{
    /** @var ModuleConfiguration[] */
    private array $moduleConfigurations = [];

    private \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ClassExtensionsChain $classExtensionsChain;

    private \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleTemplateExtensionChain $moduleTemplateExtensionsChain;

    public function __construct()
    {
        $this->setClassExtensionsChain(new ClassExtensionsChain());
        $this->setModuleTemplateExtensionChain(new ModuleTemplateExtensionChain());
    }

    /**
     * @throws ModuleConfigurationNotFoundException
     */
    public function getModuleConfiguration(string $moduleId): ModuleConfiguration
    {
        if (\array_key_exists($moduleId, $this->moduleConfigurations)) {
            return $this->moduleConfigurations[$moduleId];
        }
        throw new ModuleConfigurationNotFoundException('There is no module configuration with id ' . $moduleId);
    }

    /**
     * @return ModuleConfiguration[]
     */
    public function getModuleConfigurations(): array
    {
        return $this->moduleConfigurations;
    }

    /**
     * @return $this
     */
    public function addModuleConfiguration(ModuleConfiguration $moduleConfiguration): static
    {
        $this->moduleConfigurations[$moduleConfiguration->getId()] = $moduleConfiguration;

        return $this;
    }

    /**
     * @deprecated use ModuleConfigurationDaoInterface::delete() instead
     *
     *
     * @throws ModuleConfigurationNotFoundException
     */
    public function deleteModuleConfiguration(string $moduleId): void
    {
        if (\array_key_exists($moduleId, $this->moduleConfigurations)) {
            $this->removeModuleExtensionFromClassChain($moduleId);
            unset($this->moduleConfigurations[$moduleId]);
        } else {
            throw new ModuleConfigurationNotFoundException('There is no module configuration with id ' . $moduleId);
        }
    }

    public function getModuleIdsOfModuleConfigurations(): array
    {
        return array_keys($this->moduleConfigurations);
    }

    public function setClassExtensionsChain(ClassExtensionsChain $chain): self
    {
        $this->classExtensionsChain = $chain;

        return $this;
    }

    public function getClassExtensionsChain(): ClassExtensionsChain
    {
        return $this->classExtensionsChain;
    }

    public function setModuleTemplateExtensionChain(ModuleTemplateExtensionChain $moduleTemplateExtensionsChain): void
    {
        $this->moduleTemplateExtensionsChain = $moduleTemplateExtensionsChain;
    }

    public function getModuleTemplateExtensionChain(): ModuleTemplateExtensionChain
    {
        return $this->moduleTemplateExtensionsChain;
    }

    public function hasModuleConfiguration(string $moduleId): bool
    {
        return isset($this->moduleConfigurations[$moduleId]);
    }

    private function removeModuleExtensionFromClassChain(string $moduleId): void
    {
        $moduleConfiguration = $this->moduleConfigurations[$moduleId];
        foreach ($moduleConfiguration->getClassExtensions() as $classExtension) {
            $this->getClassExtensionsChain()->removeExtension($classExtension);
        }
    }
}
