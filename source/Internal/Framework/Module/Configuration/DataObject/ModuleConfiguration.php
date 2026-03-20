<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Class_Extension;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Controller;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Invalid_Module_Id_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Setting_Not_Fount_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Setting;
use function Symfony\Component\String\u;
class Module_Configuration
{
    private ?string $id = null;
    private ?string $module_source = null;
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
    private array $class_extensions = [];
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
    private array $module_settings = [];
    public function get_id(): string
    {
        return $this->id;
    }
    /**
     * @throws InvalidModuleIdException
     */
    public function set_id(string $id): Module_Configuration
    {
        if (u($id)->contains_any('/')) {
            throw new Invalid_Module_Id_Exception('Module ID (' . $id . ') must not contain "/".');
        }
        $this->id = $id;
        return $this;
    }
    public function get_module_source(): string
    {
        return $this->module_source;
    }
    public function set_module_source(string $module_source): Module_Configuration
    {
        $this->module_source = $module_source;
        return $this;
    }
    public function get_version(): string
    {
        return $this->version;
    }
    public function set_version(string $version): Module_Configuration
    {
        $this->version = $version;
        return $this;
    }
    public function get_title(): array
    {
        return $this->title;
    }
    public function set_title(array $title): Module_Configuration
    {
        $this->title = $title;
        return $this;
    }
    public function get_description(): array
    {
        return $this->description;
    }
    public function set_description(array $description): Module_Configuration
    {
        $this->description = $description;
        return $this;
    }
    public function get_lang(): string
    {
        return $this->lang;
    }
    public function set_lang(string $lang): Module_Configuration
    {
        $this->lang = $lang;
        return $this;
    }
    public function get_thumbnail(): string
    {
        return $this->thumbnail;
    }
    public function set_thumbnail(string $thumbnail): Module_Configuration
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }
    public function is_activated(): bool
    {
        return $this->activated;
    }
    public function set_activated(bool $activated): Module_Configuration
    {
        $this->activated = $activated;
        return $this;
    }
    public function get_author(): string
    {
        return $this->author;
    }
    public function set_author(string $author): Module_Configuration
    {
        $this->author = $author;
        return $this;
    }
    public function get_url(): string
    {
        return $this->url;
    }
    public function set_url(string $url): Module_Configuration
    {
        $this->url = $url;
        return $this;
    }
    public function get_email(): string
    {
        return $this->email;
    }
    public function set_email(string $email): Module_Configuration
    {
        $this->email = $email;
        return $this;
    }
    /**
     * @return ClassExtension[]
     */
    public function get_class_extensions(): array
    {
        return $this->class_extensions;
    }
    /**
     * @return $this
     */
    public function add_class_extension(Class_Extension $extension): static
    {
        $this->class_extensions[] = $extension;
        return $this;
    }
    public function has_class_extensions(): bool
    {
        return !empty($this->class_extensions);
    }
    public function has_class_extension(string $namespace): bool
    {
        foreach ($this->get_class_extensions() as $class_extension) {
            if ($class_extension->get_module_extension_class_name() === $namespace) {
                return true;
            }
        }
        return false;
    }
    public function is_extending_shop_class(string $shop_class_namespace): bool
    {
        foreach ($this->get_class_extensions() as $class_extension) {
            if ($class_extension->get_shop_class_name() === $shop_class_namespace) {
                return true;
            }
        }
        return false;
    }
    /**
     * @return $this
     */
    public function add_controller(Controller $controller): static
    {
        $this->controllers[] = $controller;
        return $this;
    }
    /**
     * @return Controller[]
     */
    public function get_controllers(): array
    {
        return $this->controllers;
    }
    public function has_controllers(): bool
    {
        return !empty($this->controllers);
    }
    /**
     * @return $this
     */
    public function add_event(Event $event): static
    {
        $this->events[] = $event;
        return $this;
    }
    /**
     * @return Event[]
     */
    public function get_events(): array
    {
        return $this->events;
    }
    public function has_events(): bool
    {
        return !empty($this->events);
    }
    /**
     * @return Setting[]
     */
    public function get_module_settings(): array
    {
        return $this->module_settings;
    }
    public function has_module_setting(string $setting_name): bool
    {
        foreach ($this->get_module_settings() as $setting) {
            if ($setting->get_name() === $setting_name) {
                return true;
            }
        }
        return false;
    }
    public function has_module_settings(): bool
    {
        return !empty($this->module_settings);
    }
    /**
     * @throws ModuleSettingNotFountException
     */
    public function get_module_setting(string $setting_name): Setting
    {
        foreach ($this->get_module_settings() as $setting) {
            if ($setting->get_name() === $setting_name) {
                return $setting;
            }
        }
        throw new Module_Setting_Not_Fount_Exception("Module setting \"{$setting_name}\" was not found in configuration.");
    }
    public function add_module_setting(Setting $module_settings): Module_Configuration
    {
        $this->module_settings[] = $module_settings;
        return $this;
    }
    /**
     * @param Setting[] $moduleSettings
     */
    public function set_module_settings(array $module_settings): Module_Configuration
    {
        $this->module_settings = $module_settings;
        return $this;
    }
}