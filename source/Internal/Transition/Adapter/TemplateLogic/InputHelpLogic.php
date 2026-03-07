<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Transition\Adapter\TemplateLogic;

class InputHelpLogic
{
    public function getIdent(array $params)
    {
        return $params['ident'] ?? null;
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function getTranslation($params)
    {
        $ident = $this->getIdent($params);
        $lang = \OxidEsales\Eshop\Core\Registry::getLang();
        $tplLanguage = $lang->getTplLanguage();
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $isAdmin = $config->isAdmin();

        return $lang->translateString($ident, $tplLanguage, $isAdmin);
    }
}
