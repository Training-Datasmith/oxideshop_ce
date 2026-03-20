<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Class OnlineServerEmailBuilder is responsible for generation of email with specific message
 * when it's not possible to make OLIS call via CURL.
 *
 * @internal Do not make a module extension for this class.
 *
 * @ignore   This class will not be included in documentation.
 */
class Online_Server_Email_Builder extends \Oxid_Esales\Eshop\Core\Email_Builder
{
    public const OLC_EMAIL = 'olc@oxid-esales.com';
    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function get_body()
    {
        return $this->build_param;
    }
    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function get_subject()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('SUBJECT_UNABLE_TO_SEND_VIA_CURL', null, true);
    }
    /**
     * @inheritdoc
     *
     * @return string
     */
    protected function get_recipient()
    {
        return self::OLC_EMAIL;
    }
}