<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * @internal Do not make a module extension for this class.
 *
 * Email builder class
 */
abstract class Email_Builder
{
    protected $build_param;
    /**
     * Set configuration first, build and return the email after.
     *
     * @param mixed $buildParam
     *
     * @return Email
     */
    public function build($build_param = null)
    {
        $this->build_param = $build_param;
        return $this->build_email();
    }
    /**
     * Builds and returns the email object
     *
     * @return \OxidEsales\Eshop\Core\Email
     */
    protected function build_email()
    {
        $email = $this->get_email_object();
        $email->set_subject($this->get_subject());
        $email->set_recipient($this->get_recipient());
        $email->set_from($this->get_sender());
        $email->set_body($this->get_body());
        return $email;
    }
    /**
     * @return \OxidEsales\Eshop\Core\Email
     */
    protected function get_email_object()
    {
        return ox_new(\Oxid_Esales\Eshop\Core\Email::class);
    }
    /**
     * Prepare and get recipient address
     *
     * @return string
     */
    protected function get_recipient()
    {
        return $this->get_shop_info_address();
    }
    /**
     * Prepare and get sender address
     *
     * @return string
     */
    protected function get_sender()
    {
        return $this->get_shop_info_address();
    }
    /**
     * Prepare and get subject
     *
     * @return string
     */
    protected function get_subject()
    {
        return '';
    }
    /**
     * Prepare and get body
     *
     * @return string
     */
    protected function get_body()
    {
        return '';
    }
    /**
     * Returns active shop info email address.
     *
     * @return string
     */
    protected function get_shop_info_address()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $active_shop = $config->get_active_shop();
        return $active_shop->get_field_data('oxinfoemail');
    }
    /**
     * Returns the message with email origin information.
     *
     * @return string
     */
    protected function get_email_origin_message()
    {
        $lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $shop_url = Container_Facade::get_parameter('oxid_esales.shop_url');
        return '<br>' . sprintf($lang->translate_string('SHOP_EMAIL_ORIGIN_MESSAGE', null, true), $shop_url);
    }
}