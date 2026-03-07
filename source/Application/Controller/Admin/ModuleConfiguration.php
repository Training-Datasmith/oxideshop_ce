<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Str;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Event\SettingChangedEvent;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setting\Setting;

#[\AllowDynamicProperties]
class ModuleConfiguration extends \OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration
{
    /**
     * Add additional config type for modules.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_aConfParams['password'] = 'confpassword';
    }

    /** @inheritdoc */
    public function render()
    {
        $this->_sModuleId = $this->getSelectedModuleId();
        $moduleId = $this->_sModuleId;

        try {
            $moduleConfiguration = ContainerFacade::get(ModuleConfigurationDaoBridgeInterface::class)
                ->get($moduleId);
            if (!empty($moduleConfiguration->getModuleSettings())) {
                $formatModuleSettings = $this
                    ->formatModuleSettingsForTemplate($moduleConfiguration->getModuleSettings());

                $this->_aViewData['var_constraints'] = $formatModuleSettings['constraints'];
                $this->_aViewData['var_grouping'] = $formatModuleSettings['grouping'];

                foreach ($this->_aConfParams as $sType => $sParam) {
                    $this->_aViewData[$sParam] = $formatModuleSettings['vars'][$sType] ?? null;
                }
            }
        } catch (\Throwable $throwable) {
            Registry::getUtilsView()->addErrorToDisplay($throwable);
            Registry::getLogger()->error($throwable->getMessage());
        }

        $module = oxNew(Module::class);
        $module->load($moduleId);

        $this->_aViewData['oModule'] = $module;

        return 'module_config';
    }

    /**
     * Saves shop configuration variables
     */
    public function saveConfVars(): void
    {
        $this->resetContentCache();
        $this->_sModuleId = $this->getSelectedModuleId();

        try {
            $this->saveModuleConfigVariables($this->_sModuleId, $this->getConfigVariablesFromRequest());
        } catch (\Throwable $throwable) {
            Registry::getUtilsView()->addErrorToDisplay($throwable);
            Registry::getLogger()->error($throwable->getMessage());
        }
    }

    private function getSelectedModuleId(): string
    {
        $moduleId = $this->_sEditObjectId
            ?? Registry::getRequest()->getRequestEscapedParameter('oxid')
            ?? Registry::getSession()->getVariable('saved_oxid');

        if ($moduleId === null) {
            throw new \InvalidArgumentException('Module id not found.');
        }

        return $moduleId;
    }

    private function saveModuleConfigVariables(string $moduleId, array $variables): void
    {
        $moduleConfigurationDaoBridge = ContainerFacade::get(ModuleConfigurationDaoBridgeInterface::class);
        $moduleConfiguration = $moduleConfigurationDaoBridge->get($moduleId);

        foreach ($variables as $name => $value) {
            if ($moduleConfiguration->hasModuleSetting($name)) {
                $moduleSetting = $moduleConfiguration->getModuleSetting($name);

                if ($moduleSetting->getType() === 'aarr') {
                    $value = $this->multilineToAarray($value);
                }
                if ($moduleSetting->getType() === 'arr') {
                    $value = $this->multilineToArray($value);
                }
                if ($moduleSetting->getType() === 'bool') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($moduleSetting->getType() === 'num' && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    $value = (int)$value;
                } elseif ($moduleSetting->getType() === 'num' && filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
                    $value = (float)$value;
                }

                $moduleSetting->setValue($value);

                ContainerFacade::dispatch(new SettingChangedEvent(
                    $name,
                    (int)Registry::getConfig()->getShopId(),
                    $moduleId
                ));
            }
        }

        $moduleConfigurationDaoBridge->save($moduleConfiguration);
    }

    private function getConfigVariablesFromRequest(): array
    {
        $settings = [];

        foreach ($this->_aConfParams as $requestParameterKey) {
            $settingsFromRequest = Registry::getRequest()->getRequestEscapedParameter($requestParameterKey);

            if (\is_array($settingsFromRequest)) {
                foreach ($settingsFromRequest as $name => $value) {
                    $settings[$name] = $value;
                }
            }
        }

        return $settings;
    }

    /**
     * @param Setting[] $moduleSettings
     */
    private function formatModuleSettingsForTemplate(array $moduleSettings): array
    {
        $confVars = [
            'bool'     => [],
            'str'      => [],
            'arr'      => [],
            'aarr'     => [],
            'select'   => [],
            'password' => [],
        ];
        $constraints = [];
        $grouping = [];

        foreach ($moduleSettings as $setting) {
            $name = $setting->getName();
            $valueType = $setting->getType();
            $value = null;

            if ($setting->getValue() !== null) {
                $value = match ($setting->getType()) {
                    'arr' => $this->arrayToMultiline($setting->getValue()),
                    'aarr' => $this->aarrayToMultiline($setting->getValue()),
                    'bool' => filter_var($setting->getValue(), FILTER_VALIDATE_BOOLEAN),
                    default => $setting->getValue(),
                };
                $value = Str::getStr()->htmlentities($value);
            }

            $group = $setting->getGroupName();

            $confVars[$valueType][$name] = $value;
            $constraints[$name] = $setting->getConstraints() ?? '';

            if ($group) {
                if (!isset($grouping[$group])) {
                    $grouping[$group] = [$name => $valueType];
                } else {
                    $grouping[$group][$name] = $valueType;
                }
            }
        }

        return [
            'vars'        => $confVars,
            'constraints' => $constraints,
            'grouping'    => $grouping,
        ];
    }
}
