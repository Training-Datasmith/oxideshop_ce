<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\ClassExtension;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Controller;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration\Event;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\InvalidModuleIdException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleSettingNotFountException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;

use function Symfony\Component\String\u;

class ModuleConfiguration
{
    private ?string $id = null;

    private ?string $moduleSource = null;

    private string $version = '';

    private bool $activated = false;

    private array $title = [];
    private array $description = [];
    private string $lang = '';
    private string $thumbnail = '';
    private string $author = '';
    private string $url = '';
    private string $email = '';

    /**
     * @var ClassExtension[]
     */
    private array $classExtensions = [];

    /**
     * @var Controller[]
     */
    private array $controllers = [];

    /**
     * @var Event[]
     */
    private array $events = [];

    /**
     * @var Setting[]
     */
    private array $moduleSettings = [];

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @throws InvalidModuleIdException
     */
    public function setId(string $id): ModuleConfiguration
    {
        if (u($id)->containsAny('/')) {
            throw new InvalidModuleIdException('Module ID (' . $id . ') must not contain "/".');
        }

        $this->id = $id;

        return $this;
    }

    public function getModuleSource(): string
    {
        return $this->moduleSource;
    }

    public function setModuleSource(string $moduleSource): ModuleConfiguration
    {
        $this->moduleSource = $moduleSource;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): ModuleConfiguration
    {
        $this->version = $version;

        return $this;
    }

    public function getTitle(): array
    {
        return $this->title;
    }

    public function setTitle(array $title): ModuleConfiguration
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function setDescription(array $description): ModuleConfiguration
    {
        $this->description = $description;

        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): ModuleConfiguration
    {
        $this->lang = $lang;

        return $this;
    }

    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): ModuleConfiguration
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): ModuleConfiguration
    {
        $this->activated = $activated;

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): ModuleConfiguration
    {
        $this->author = $author;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): ModuleConfiguration
    {
        $this->url = $url;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): ModuleConfiguration
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return ClassExtension[]
     */
    public function getClassExtensions(): array
    {
        return $this->classExtensions;
    }

    /**
     * @return $this
     */
    public function addClassExtension(ClassExtension $extension): static
    {
        $this->classExtensions[] = $extension;

        return $this;
    }

    public function hasClassExtensions(): bool
    {
        return !empty($this->classExtensions);
    }

    public function hasClassExtension(string $namespace): bool
    {
        foreach ($this->getClassExtensions() as $classExtension) {
            if ($classExtension->getModuleExtensionClassName() === $namespace) {
                return true;
            }
        }

        return false;
    }

    public function isExtendingShopClass(string $shopClassNamespace): bool
    {
        foreach ($this->getClassExtensions() as $classExtension) {
            if ($classExtension->getShopClassName() === $shopClassNamespace) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return $this
     */
    public function addController(Controller $controller): static
    {
        $this->controllers[] = $controller;

        return $this;
    }

    /**
     * @return Controller[]
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function hasControllers(): bool
    {
        return !empty($this->controllers);
    }

    /**
     * @return $this
     */
    public function addEvent(Event $event): static
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function hasEvents(): bool
    {
        return !empty($this->events);
    }

    /**
     * @return Setting[]
     */
    public function getModuleSettings(): array
    {
        return $this->moduleSettings;
    }

    public function hasModuleSetting(string $settingName): bool
    {
        foreach ($this->getModuleSettings() as $setting) {
            if ($setting->getName() === $settingName) {
                return true;
            }
        }

        return false;
    }

    public function hasModuleSettings(): bool
    {
        return !empty($this->moduleSettings);
    }

    /**
     * @throws ModuleSettingNotFountException
     */
    public function getModuleSetting(string $settingName): Setting
    {
        foreach ($this->getModuleSettings() as $setting) {
            if ($setting->getName() === $settingName) {
                return $setting;
            }
        }
        throw new ModuleSettingNotFountException("Module setting \"$settingName\" was not found in configuration.");
    }

    public function addModuleSetting(Setting $moduleSettings): ModuleConfiguration
    {
        $this->moduleSettings[] = $moduleSettings;
        return $this;
    }

    /**
     * @param Setting[] $moduleSettings
     */
    public function setModuleSettings(array $moduleSettings): ModuleConfiguration
    {
        $this->moduleSettings = $moduleSettings;
        return $this;
    }
}
