<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Exception;
use Oxid_Esales\Eshop\Application\Model\Order_File_List;
use Oxid_Esales\Eshop\Core\Dynamic_Image_Generator;
use Oxid_Esales\Eshop\Core\Exception\System_Component_Exception;
use Oxid_Esales\Eshop\Core\Field as OxidEsalesField;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Admin\Event\Admin_Mode_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Adapter\Email\Email_Adapter_Interface;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Bridge_Interface;
use Php_Mailer\Php_Mailer\Php_Mailer;
use Psr\Log\Logger_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\Mailer\Mailer_Interface;
use Throwable;
/**
 * Mailing manager.
 * Collects mailing configuration, other parameters, performs mailing functions (ordering, registration emails, etc.).
 */
class Email extends Php_Mailer
{
    /**
     * Default Smtp server port
     *
     * @var int
     */
    public $smtp_port = 25;
    /**
     * Password reminder mail template
     *
     * @var string
     */
    protected $_s_forgot_pwd_template = 'email/html/forgotpwd';
    /**
     * Password reminder plain mail template
     *
     * @var string
     */
    protected $_s_forgot_pwd_template_plain = 'email/plain/forgotpwd';
    /**
     * Newsletter registration mail template
     *
     * @var string
     */
    protected $_s_newsletter_opt_in_template = 'email/html/newsletteroptin';
    /**
     * Newsletter registration plain mail template
     *
     * @var string
     */
    protected $_s_newsletter_opt_in_template_plain = 'email/plain/newsletteroptin';
    /**
     * Product suggest mail template
     *
     * @var string
     */
    protected $_s_invite_template = 'email/html/invite';
    /**
     * Product suggest plain mail template
     *
     * @var string
     */
    protected $_s_invite_template_plain = 'email/plain/invite';
    /**
     * Send order notification mail template
     *
     * @var string
     */
    protected $_s_sened_now_template = 'email/html/ordershipped';
    /**
     * Send order notification plain mail template
     *
     * @var string
     */
    protected $_s_sened_now_template_plain = 'email/plain/ordershipped';
    /**
     * Send ordered download links mail template
     *
     * @var string
     */
    protected $_s_send_downloads_template = 'email/html/senddownloadlinks';
    /**
     * Send ordered download links plain mail template
     *
     * @var string
     */
    protected $_s_send_downloads_template_plain = 'email/plain/senddownloadlinks';
    /**
     * Wishlist mail template
     *
     * @var string
     */
    protected $_s_wish_list_template = 'email/html/wishlist';
    /**
     * Wishlist plain mail template
     *
     * @var string
     */
    protected $_s_wish_list_template_plain = 'email/plain/wishlist';
    /**
     * Name of template used during registration
     *
     * @var string
     */
    protected $_s_register_template = 'email/html/register';
    /**
     * Name of plain template used during registration
     *
     * @var string
     */
    protected $_s_register_template_plain = 'email/plain/register';
    /**
     * Name of template used by reminder function (article).
     *
     * @var string
     */
    protected $_s_reminder_mail_template = 'email/html/owner_reminder';
    /**
     * Order e-mail for customer HTML template
     *
     * @var string
     */
    protected $_s_order_user_template = 'email/html/order_cust';
    /**
     * Order e-mail for customer plain text template
     *
     * @var string
     */
    protected $_s_order_user_plain_template = 'email/plain/order_cust';
    /**
     * Order e-mail for shop owner HTML template
     *
     * @var string
     */
    protected $_s_order_owner_template = 'email/html/order_owner';
    /**
     * Order e-mail for shop owner plain text template
     *
     * @var string
     */
    protected $_s_order_owner_plain_template = 'email/plain/order_owner';
    // #586A - additional templates for more customizable subjects
    /**
     * Order e-mail subject for customer template
     *
     * @var string
     */
    protected $_s_order_user_subject_template = 'email/html/order_cust_subj';
    /**
     * Order e-mail subject for shop owner template
     *
     * @var string
     */
    protected $_s_order_owner_subject_template = 'email/html/order_owner_subj';
    /**
     * Price alarm e-mail for shop owner template
     *
     * @var string
     */
    protected $_s_owner_pricealarm_template = 'email/html/pricealarm_owner';
    /**
     * Price alarm e-mail for shop owner template
     *
     * @var string
     */
    protected $_s_pricealamr_customer_template = 'email_pricealarm_customer';
    /**
     * Language specific viewconfig object array containing view data, view config and shop object
     *
     * @var array
     */
    protected $_a_shops = [];
    /**
     * Add inline images to mail
     *
     * @var bool
     */
    protected $_bl_inline_img_email;
    /**
     * Array of recipient email addresses
     *
     * @var array
     */
    protected $_a_recipients = [];
    /**
     * Array of reply addresses used
     *
     * @var array
     */
    protected $_a_replies = [];
    /**
     * Email view data
     *
     * @var array
     */
    protected $_a_view_data = [];
    /**
     * Shop object
     *
     * @var \OxidEsales\Eshop\Application\Model\Shop
     */
    protected $_o_shop;
    /**
     * Email charset
     *
     * @var string
     */
    protected $_s_char_set;
    public function __construct()
    {
        parent::__construct(true);
        $my_config = Registry::get_config();
        $this->set_smtp();
        $this::$validator = static fn($email): bool => filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false;
        $this->set_use_inline_images($my_config->get_config_param('blInlineImgEmail'));
        $this->set_mail_word_wrap(100);
        $this->is_html();
        $this->set_view_data('oEmailView', $this);
        $this->set_view_data('shopUrl', $my_config->get_shop_url());
        $this->set_view_data('shopUrlWithLangAndSubshop', $my_config->get_shop_url(null, false));
        $this->Encoding = 'base64';
    }
    /**
     * Only used for convenience in UNIT tests by doing so we avoid
     * writing extended classes for testing protected or private methods
     *
     * @param string $method Methods name
     * @param array  $arguments Argument array
     * @return false|mixed
     * @throws SystemComponentException
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([&$this, $method], $arguments);
        }
        throw new System_Component_Exception("Function '{$method}' does not exist or is not accessible! (" . static::class . ')' . PHP_EOL);
    }
    protected function get_renderer()
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    /**
     * Outputs email fields thought email output processor, includes images, and initiate email sending
     * If fails to send mail via SMTP, tries to send via mail(). On failing to send, sends mail to
     * shop administrator about failing mail sending
     *
     * @return bool
     */
    public function send()
    {
        if (count($this->get_recipient()) < 1) {
            return false;
        }
        $my_config = Registry::get_config();
        $this->set_char_set();
        if ($this->get_use_inline_images()) {
            $this->include_images($my_config->get_image_url(), $my_config->get_image_url(false, false), $my_config->get_picture_url(null, false), $my_config->get_image_dir(), $my_config->get_picture_dir(false));
        }
        $this->make_output_processing();
        if ($this->is_symfony_mailer_enabled()) {
            try {
                $symfony_email = Container_Facade::get(Email_Adapter_Interface::class)->convert_to_symfony_email($this);
                Container_Facade::get(Mailer_Interface::class)->send($symfony_email);
                return true;
            } catch (Throwable $e) {
                Container_Facade::get(Logger_Interface::class)->error('Mailer failed, falling back to PHPMailer: ' . $e->get_message(), [$e]);
            }
        }
        if ($this->get_mailer() == 'smtp') {
            $ret = $this->send_mail();
            if (!$ret) {
                $this->send_mail_error_msg();
                $this->set_mailer('mail');
                $ret = $this->send_mail();
            }
        } else {
            $this->set_mailer('mail');
            $ret = $this->send_mail();
        }
        if (!$ret) {
            $this->send_mail_error_msg();
        }
        return $ret;
    }
    /**
     * Sets smtp parameters depending on the protocol used
     * returns smtp url which should be used for fsockopen
     *
     * @param string $url initial smtp
     *
     * @return string
     */
    protected function set_smtp_protocol($url)
    {
        $protocol = '';
        $smtp_host = $url;
        $match = [];
        if (Str::get_str()->preg_match('@^([0-9a-z]+://)?(.*)$@i', $url, $match)) {
            if ($match[1]) {
                if ($match[1] == 'ssl://' || $match[1] == 'tls://') {
                    $this->set('SMTPSecure', substr($match[1], 0, 3));
                } else {
                    $protocol = $match[1];
                }
            }
            $smtp_host = $match[2];
        }
        return $protocol . $smtp_host;
    }
    /**
     * Sets SMTP mailer parameters, such as user name, password, location.
     *
     * @param \OxidEsales\Eshop\Application\Model\Shop $shop Object, that keeps base shop info
     */
    public function set_smtp($shop = null): void
    {
        $shop = $shop ?: $this->get_shop();
        $smtp_url = $this->set_smtp_protocol($shop->oxshops__oxsmtp->value);
        if (!$this->is_valid_smtp_host($smtp_url)) {
            $this->set_mailer('mail');
            return;
        }
        $this->set_host($smtp_url);
        $this->set_mailer('smtp');
        if ($shop->oxshops__oxsmtpuser->value) {
            $this->set_smtp_auth_info($shop->oxshops__oxsmtpuser->value, $shop->oxshops__oxsmtppwd->get_raw_value());
        }
        if (Container_Facade::get_parameter('oxid_esales.smtp_debug_mode')) {
            $this->set_smtp_debug(true);
        }
    }
    /**
     * Checks if smtp host is valid (tries to connect to it)
     *
     * @param string $smtpHost currently used smtp server host name
     *
     * @return bool
     */
    protected function is_valid_smtp_host($smtp_host)
    {
        $is_smtp = false;
        if ($smtp_host) {
            $match = [];
            $smtp_port = $this->smtp_port;
            if (Str::get_str()->preg_match('@^(.*?)(:([0-9]+))?$@i', $smtp_host, $match)) {
                $smtp_host = $match[1];
                if (isset($match[3]) && (int) $match[3] !== 0) {
                    $smtp_port = (int) $match[3];
                }
            }
            if ($is_smtp = (bool) $r_handle = @fsockopen($smtp_host, $smtp_port, $err_no, $err_str, 30)) {
                fclose($r_handle);
            }
        }
        return $is_smtp;
    }
    /**
     * Sets mailer additional settings and sends ordering mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\Order $order   Order object
     * @param string                                    $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_order_email_to_user($order, $subject = null)
    {
        if ($this->are_order_emails_disabled()) {
            Container_Facade::get(Logger_Interface::class)->notice('Order email not sent to user due to disabled configuration option');
            return true;
        }
        $order = $this->add_user_info_order_e_mail($order);
        $shop = $this->get_shop();
        $this->set_mail_params($shop);
        $user = $order->get_order_user();
        $this->set_user($user);
        $renderer = $this->get_renderer();
        $this->set_view_data('order', $order);
        $this->set_view_data('blShowReviewLink', $this->should_product_review_links_be_included());
        $this->process_view_array();
        $this->set_body($renderer->render_template($this->_s_order_user_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_order_user_plain_template, $this->get_view_data()));
        // #586A
        if ($subject === null) {
            if ($renderer->exists($this->_s_order_user_subject_template)) {
                $subject = $renderer->render_template($this->_s_order_user_subject_template, $this->get_view_data());
            } else {
                $subject = $shop->oxshops__oxordersubject->get_raw_value() . ' (#' . $order->oxorder__oxordernr->value . ')';
            }
        }
        $this->set_subject($subject);
        $full_name = $user->oxuser__oxfname->get_raw_value() . ' ' . $user->oxuser__oxlname->get_raw_value();
        $this->set_recipient($user->oxuser__oxusername->value, $full_name);
        $this->set_reply_to($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        return $this->send();
    }
    /**
     * Sets mailer additional settings and sends ordering mail to shop owner.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\Order $order   Order object
     * @param string                                    $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_order_email_to_owner($order, $subject = null)
    {
        if ($this->are_order_emails_disabled()) {
            Container_Facade::get(Logger_Interface::class)->notice('Order email not sent to owner due to disabled configuration option');
            return true;
        }
        Registry::get_config();
        $shop = $this->get_shop();
        $this->clear_mailer();
        $order = $this->add_user_info_order_e_mail($order);
        $user = $order->get_order_user();
        $this->set_user($user);
        $this->set_from($shop->oxshops__oxowneremail->value);
        $language = Registry::get_lang();
        $order_language = $language->get_object_tpl_language();
        if ($shop->get_language() != $order_language) {
            $shop = $this->get_shop($order_language);
        }
        $this->set_smtp($shop);
        $renderer = $this->get_renderer();
        $this->set_view_data('order', $order);
        $this->process_view_array();
        $this->set_body($renderer->render_template($this->_s_order_owner_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_order_owner_plain_template, $this->get_view_data()));
        // #586A
        if ($subject === null) {
            if ($renderer->exists($this->_s_order_owner_subject_template)) {
                $subject = $renderer->render_template($this->_s_order_owner_subject_template, $this->get_view_data());
            } else {
                $subject = $shop->oxshops__oxordersubject->get_raw_value() . ' (#' . $order->oxorder__oxordernr->value . ')';
            }
        }
        $this->set_subject($subject);
        $this->set_recipient($shop->oxshops__oxowneremail->value, $language->translate_string('order'));
        if ($user->oxuser__oxusername->value != 'admin') {
            $full_name = $user->oxuser__oxfname->get_raw_value() . ' ' . $user->oxuser__oxlname->get_raw_value();
            $this->set_reply_to($user->oxuser__oxusername->value, $full_name);
        }
        $result = $this->send();
        $this->on_order_email_to_owner_sent($user, $order);
        return $result;
    }
    /**
     * Method is called when order email is sent to owner.
     *
     * @param \OxidEsales\Eshop\Application\Model\User  $user
     * @param \OxidEsales\Eshop\Application\Model\Order $order
     */
    protected function on_order_email_to_owner_sent($user, $order)
    {
        $remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
        $remark->oxremark__oxtext = new Oxid_Esales_Field($this->get_alt_body(), Oxid_Esales_Field::T_RAW);
        $remark->oxremark__oxparentid = new Oxid_Esales_Field($user->get_id(), Oxid_Esales_Field::T_RAW);
        $remark->oxremark__oxtype = new Oxid_Esales_Field('o', Oxid_Esales_Field::T_RAW);
        $remark->save();
    }
    /**
     * Sets mailer additional settings and sends registration mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $user    user object
     * @param string                                   $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_register_confirm_email($user, $subject = null)
    {
        $this->set_view_data('contentident', 'oxregisteraltemail');
        $this->set_view_data('contentplainident', 'oxregisterplainaltemail');
        return $this->send_register_email($user, $subject);
    }
    /**
     * Sets mailer additional settings and sends registration mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $user    user object
     * @param string                                   $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_register_email($user, $subject = null)
    {
        $user = $this->add_user_register_email($user);
        $shop = $this->get_shop();
        $this->set_mail_params($shop);
        $renderer = $this->get_renderer();
        $this->set_user($user);
        $this->process_view_array();
        $this->set_body($renderer->render_template($this->_s_register_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_register_template_plain, $this->get_view_data()));
        $this->set_subject($subject ?? $shop->oxshops__oxregistersubject->get_raw_value());
        $full_name = $user->oxuser__oxfname->get_raw_value() . ' ' . $user->oxuser__oxlname->get_raw_value();
        $this->set_recipient($user->oxuser__oxusername->value, $full_name);
        $this->set_reply_to($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        return $this->send();
    }
    /**
     * Sets mailer additional settings and sends "forgot password" mail to user.
     * Returns true on success.
     *
     * @param string $emailAddress user email address
     * @param string $subject      user defined subject [optional]
     *
     * @return mixed true - success, false - user not found, -1 - could not send
     */
    public function send_forgot_pwd_email($email_address, $subject = null)
    {
        $result = false;
        $shop = $this->add_forgot_pwd_email($this->get_shop());
        $oxid = $this->get_user_id_by_user_name($email_address, $shop->get_id());
        $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        if ($oxid && $user->load($oxid)) {
            $renderer = $this->get_renderer();
            $this->set_user($user);
            $this->process_view_array();
            $this->set_mail_params($shop);
            $this->set_body($renderer->render_template($this->_s_forgot_pwd_template, $this->get_view_data()));
            $this->set_alt_body($renderer->render_template($this->_s_forgot_pwd_template_plain, $this->get_view_data()));
            $this->set_subject($subject ?? $shop->oxshops__oxforgotpwdsubject->get_raw_value());
            $full_name = $user->oxuser__oxfname->get_raw_value() . ' ' . $user->oxuser__oxlname->get_raw_value();
            $recipient_address = $user->oxuser__oxusername->get_raw_value();
            $this->set_recipient($recipient_address, $full_name);
            $this->set_reply_to($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
            if (!$this->send()) {
                $result = -1;
            } else {
                $result = true;
            }
        }
        return $result;
    }
    /**
     * Sets mailer additional settings and sends contact info mail to user.
     * Returns true on success.
     *
     * @param string $emailAddress Email address
     * @param string $subject      Email subject
     * @param string $message      Email message text
     *
     * @return bool
     */
    public function send_contact_mail($email_address = null, $subject = null, $message = null)
    {
        $shop = $this->get_shop();
        $this->set_mail_params($shop);
        $this->set_body($message);
        $this->set_subject($subject);
        $this->set_recipient($shop->oxshops__oxinfoemail->value, '');
        $this->set_from($shop->oxshops__oxowneremail->value, $shop->oxshops__oxname->get_raw_value());
        $this->set_reply_to($email_address, '');
        return $this->send();
    }
    /**
     * Sets mailer additional settings and sends "NewsletterDBOptInMail" mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $user    user object
     * @param string                                   $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_newsletter_db_opt_in_mail($user, $subject = null)
    {
        $user = $this->add_newsletter_db_opt_in_mail($user);
        // shop info
        $shop = $this->get_shop();
        $this->set_mail_params($shop);
        $renderer = $this->get_renderer();
        $confirm_code = md5($user->oxuser__oxusername->value . $user->oxuser__oxpasssalt->value);
        $this->set_view_data('subscribeLink', $this->get_news_subs_link($user->oxuser__oxid->value, $confirm_code));
        $this->set_user($user);
        $this->process_view_array();
        $this->set_body($renderer->render_template($this->_s_newsletter_opt_in_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_newsletter_opt_in_template_plain, $this->get_view_data()));
        $this->set_subject($subject ?? Registry::get_lang()->translate_string('NEWSLETTER') . ' ' . $shop->oxshops__oxname->get_raw_value());
        $full_name = $user->oxuser__oxfname->get_raw_value() . ' ' . $user->oxuser__oxlname->get_raw_value();
        $this->set_recipient($user->oxuser__oxusername->value, $full_name);
        $this->set_from($shop->oxshops__oxinfoemail->value, $shop->oxshops__oxname->get_raw_value());
        $this->set_reply_to($shop->oxshops__oxinfoemail->value, $shop->oxshops__oxname->get_raw_value());
        return $this->send();
    }
    protected function get_news_subs_link($id, $confirm_code = null)
    {
        $my_config = Registry::get_config();
        $act_shop_lang = $my_config->get_active_shop()->get_language();
        $url = $my_config->get_shop_home_url() . 'cl=newsletter&amp;fnc=addme&amp;uid=' . $id;
        $url .= '&amp;lang=' . $act_shop_lang;
        $url .= $confirm_code ? '&amp;confirm=' . $confirm_code : '';
        return $url;
    }
    /**
     * Sets mailer additional settings and sends "InviteMail" mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\User $user Mailing parameters object
     *
     * @return bool
     */
    public function send_invite_mail($user)
    {
        $my_config = Registry::get_config();
        $curr_lang = $my_config->get_active_shop()->get_language();
        $shop = $this->get_shop($curr_lang);
        $this->set_from($user->send_email, $user->send_name);
        $this->set_smtp();
        /** @var TemplateRendererInterface $renderer */
        $renderer = Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
        $this->set_user($user);
        $home_url = $this->get_view_config()->get_home_link();
        if ($my_config->get_active_view()->is_active('Invitations') && $active_user = $shop->get_user()) {
            $home_url = Registry::get_utils_url()->append_param_separator($home_url);
            $home_url .= 'su=' . $active_user->get_id();
        }
        if (is_array($user->rec_email) && count($user->rec_email) > 0) {
            foreach ($user->rec_email as $email) {
                if (!empty($email)) {
                    $register_url = Registry::get_utils_url()->append_param_separator($home_url);
                    $register_url .= 're=' . md5((string) $email);
                    $this->set_view_data('sHomeUrl', $register_url);
                    // Process view data array through oxoutput processor
                    $this->process_view_array();
                    $this->set_body($renderer->render_template($this->_s_invite_template, $this->get_view_data()));
                    $this->set_alt_body($renderer->render_template($this->_s_invite_template_plain, $this->get_view_data()));
                    $this->set_subject($user->send_subject);
                    $this->set_recipient($email);
                    $this->set_reply_to($user->send_email, $user->send_name);
                    $this->send();
                    $this->clear_all_recipients();
                }
            }
            return true;
        }
        return false;
    }
    /**
     * Sets mailer additional settings and sends "SendedNowMail" mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\Order $order   order object
     * @param string                                    $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_sended_now_mail($order, $subject = null)
    {
        $my_config = Registry::get_config();
        $order_lang = (int) ($order->oxorder__oxlang->value ?? 0);
        $shop = $this->get_shop($order_lang);
        $this->set_mail_params($shop);
        $lang = Registry::get_lang();
        $renderer = $this->get_renderer();
        $this->set_view_data('order', $order);
        $this->set_view_data('shopTemplateDir', $my_config->get_template_dir(false));
        if ($my_config->get_config_param('bl_perfLoadReviews', false)) {
            $this->set_view_data('blShowReviewLink', true);
            $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $this->set_view_data('reviewuserhash', $user->get_review_user_hash($order->oxorder__oxuserid->value));
        } else {
            $this->set_view_data('blShowReviewLink', false);
        }
        $this->process_view_array();
        $old_tpl_lang = $lang->get_tpl_language();
        $old_base_lang = $lang->get_base_language();
        $lang->set_tpl_language($order_lang);
        $lang->set_base_language($order_lang);
        $this->switch_to_shop_mode();
        $this->set_body($renderer->render_template($this->_s_sened_now_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_sened_now_template_plain, $this->get_view_data()));
        $this->switch_to_admin_mode();
        $lang->set_tpl_language($old_tpl_lang);
        $lang->set_base_language($old_base_lang);
        $this->set_subject($subject ?? $shop->oxshops__oxsendednowsubject->get_raw_value());
        $full_name = $order->oxorder__oxbillfname->get_raw_value() . ' ' . $order->oxorder__oxbilllname->get_raw_value();
        $this->set_recipient($order->oxorder__oxbillemail->value, $full_name);
        $this->set_reply_to($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        return $this->send();
    }
    /**
     * Sets mailer additional settings and sends "SendDownloadLinks" mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\Order $order   order object
     * @param string                                    $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_download_links_mail($order, $subject = null)
    {
        $my_config = Registry::get_config();
        $order_lang = (int) ($order->oxorder__oxlang->value ?? 0);
        $shop = $this->get_shop($order_lang);
        $this->set_mail_params($shop);
        $lang = Registry::get_lang();
        $renderer = $this->get_renderer();
        $this->set_view_data('order', $order);
        $this->set_view_data('shopTemplateDir', $my_config->get_template_dir(false));
        $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $this->set_view_data('reviewuserhash', $user->get_review_user_hash($order->oxorder__oxuserid->value));
        $this->process_view_array();
        $old_tpl_lang = $lang->get_tpl_language();
        $old_base_lang = $lang->get_tpl_language();
        $lang->set_tpl_language($order_lang);
        $lang->set_base_language($order_lang);
        $this->switch_to_shop_mode();
        $this->set_body($renderer->render_template($this->_s_send_downloads_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_send_downloads_template_plain, $this->get_view_data()));
        $this->switch_to_admin_mode();
        $lang->set_tpl_language($old_tpl_lang);
        $lang->set_base_language($old_base_lang);
        $this->set_subject($subject ?? $lang->translate_string('DOWNLOAD_LINKS', null, false));
        $full_name = $order->oxorder__oxbillfname->get_raw_value() . ' ' . $order->oxorder__oxbilllname->get_raw_value();
        $this->set_recipient($order->oxorder__oxbillemail->value, $full_name);
        $this->set_reply_to($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        return $this->send();
    }
    /**
     * Basic wrapper for email message sending with default parameters from the oxBaseShop.
     * Returns true on success.
     *
     * @param mixed  $to      Recipient or an array of the recipients
     * @param string $subject Mail subject
     * @param string $body    Mail body
     *
     * @return bool
     */
    public function send_email($to, $subject, $body)
    {
        $this->set_mail_params();
        if (is_array($to)) {
            foreach ($to as $address) {
                $this->set_recipient($address, '');
                $this->set_reply_to($address, '');
            }
        } else {
            $this->set_recipient($to, '');
            $this->set_reply_to($to, '');
        }
        $this->is_html(false);
        $this->set_subject($subject);
        $this->set_body($body);
        return $this->send();
    }
    /**
     * Sends reminder email to shop owner.
     *
     * @param array  $basketContents array of objects to pass to template
     * @param string $subject        user defined subject [optional]
     *
     * @return bool
     */
    public function send_stock_reminder($basket_contents, $subject = null)
    {
        $send = false;
        $article_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $article_list->load_stock_remind_products($basket_contents);
        // nothing to remind?
        if ($article_list->count()) {
            $shop = $this->get_shop();
            $this->set_mail_params($shop);
            $lang = Registry::get_lang();
            $renderer = $this->get_renderer();
            $this->set_view_data('articles', $article_list);
            $this->process_view_array();
            $this->set_recipient($shop->oxshops__oxowneremail->value, $shop->oxshops__oxname->get_raw_value());
            $this->set_from($shop->oxshops__oxowneremail->value, $shop->oxshops__oxname->get_raw_value());
            $this->set_body($renderer->render_template($this->_s_reminder_mail_template, $this->get_view_data()));
            $this->set_alt_body('');
            $this->set_subject($subject ?? $lang->translate_string('STOCK_LOW'));
            $send = $this->send();
        }
        return $send;
    }
    /**
     * Sets mailer additional settings and sends "WishlistMail" mail to user.
     * Returns true on success.
     *
     * @param \OxidEsales\Eshop\Application\Model\User|object $params Mailing parameters object
     *
     * @return bool
     */
    public function send_wishlist_mail($params)
    {
        $this->clear_mailer();
        $this->set_from($params->send_email, $params->send_name);
        $this->set_smtp();
        $renderer = $this->get_renderer();
        $this->set_user($params);
        $this->process_view_array();
        $this->set_body($renderer->render_template($this->_s_wish_list_template, $this->get_view_data()));
        $this->set_alt_body($renderer->render_template($this->_s_wish_list_template_plain, $this->get_view_data()));
        $this->set_subject($params->send_subject);
        $this->set_recipient($params->rec_email, $params->rec_name);
        $this->set_reply_to($params->send_email, $params->send_name);
        return $this->send();
    }
    /**
     * Sends a notification to the shop owner that price alarm was subscribed.
     * Returns true on success.
     *
     * @param array                                          $params  Parameters array
     * @param \OxidEsales\Eshop\Application\Model\PriceAlarm $alarm   oxPriceAlarm object
     * @param string                                         $subject user defined subject [optional]
     *
     * @return bool
     */
    public function send_price_alarm_notification($params, $alarm, $subject = null)
    {
        $this->clear_mailer();
        $shop = $this->get_shop();
        $this->set_mail_params($shop);
        $alarm_lang = $alarm->oxpricealarm__oxlang->value;
        $article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $article->load_in_lang($alarm_lang, $params['aid']);
        $lang = Registry::get_lang();
        $renderer = $this->get_renderer();
        $this->set_view_data('product', $article);
        $this->set_view_data('email', $params['email']);
        $this->set_view_data('bidprice', $lang->format_currency($alarm->oxpricealarm__oxprice->value));
        $this->process_view_array();
        $this->set_recipient($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        $this->set_subject($subject ?? $lang->translate_string('PRICE_ALERT_FOR_PRODUCT', $alarm_lang) . ' ' . $article->oxarticles__oxtitle->get_raw_value());
        $this->set_body($renderer->render_template($this->_s_owner_pricealarm_template, $this->get_view_data()));
        $this->set_from($params['email'], '');
        $this->set_reply_to($params['email'], '');
        return $this->send();
    }
    public function send_pricealarm_to_customer($recipient, $alarm, $body = null, $return_mail_body = null)
    {
        $this->clear_mailer();
        $shop = $this->get_shop();
        if ($shop->get_id() != $alarm->oxpricealarm__oxshopid->value) {
            $shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
            $shop->load($alarm->oxpricealarm__oxshopid->value);
            $this->set_shop($shop);
        }
        $this->set_mail_params($shop);
        $renderer = $this->get_renderer();
        $this->set_view_data('product', $alarm->get_article());
        $this->set_view_data('oPriceAlarm', $alarm);
        $this->set_view_data('bidprice', $alarm->get_f_proposed_price());
        $this->set_view_data('currency', $alarm->get_price_alarm_currency());
        $this->process_view_array();
        $this->set_recipient($recipient, $recipient);
        $this->set_subject($shop->oxshops__oxname->value);
        if ($body === null) {
            $body = $renderer->render_template($this->_s_pricealamr_customer_template, $this->get_view_data());
        }
        $this->set_body($body);
        $this->add_address($recipient, $recipient);
        $this->set_reply_to($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        if ($return_mail_body) {
            return $this->get_body();
        }
        return $this->send();
    }
    protected function include_images($image_dir = null, $image_dir_no_ssl = null, $dyn_image_dir = null, $abs_image_dir = null, $abs_dyn_image_dir = null)
    {
        $body = $this->get_body();
        if (preg_match_all('/<\s*img\s+[^>]*?src[\s]*=[\s]*[\'"]?([^[\'">]]+|.*?)?[\'">]/i', (string) $body, $matches, PREG_SET_ORDER)) {
            $file_utils = Registry::get_utils_file();
            $re_set_body = false;
            // preparing input
            $dyn_image_dir = $file_utils->normalize_dir($dyn_image_dir);
            $image_dir = $file_utils->normalize_dir($image_dir);
            $image_dir_no_ssl = $file_utils->normalize_dir($image_dir_no_ssl);
            if (count($matches)) {
                $image_cache = [];
                $my_utils = Registry::get_utils();
                $my_utils_object = $this->get_utils_object_instance();
                $img_generator = ox_new(Dynamic_Image_Generator::class);
                foreach ($matches as $image) {
                    $image_name = $image[1];
                    $file_name = '';
                    if (is_string($dyn_image_dir) && str_starts_with($image_name, $dyn_image_dir)) {
                        $file_name = $file_utils->normalize_dir($abs_dyn_image_dir) . str_replace($dyn_image_dir, '', $image_name);
                    } elseif (str_starts_with($image_name, (string) $image_dir)) {
                        $file_name = $file_utils->normalize_dir($abs_image_dir) . str_replace($image_dir, '', $image_name);
                    } elseif (str_starts_with($image_name, (string) $image_dir_no_ssl)) {
                        $file_name = $file_utils->normalize_dir($abs_image_dir) . str_replace($image_dir_no_ssl, '', $image_name);
                    }
                    if ($file_name && !@is_readable($file_name)) {
                        $file_name = $img_generator->get_image_path($file_name);
                    }
                    if ($file_name) {
                        if (isset($image_cache[$file_name]) && $image_cache[$file_name]) {
                            $c_id = $image_cache[$file_name];
                        } else {
                            $c_id = $my_utils_object->generate_u_id();
                            $m_ime = $my_utils->ox_mime_content_type($file_name);
                            if (in_array($m_ime, ['image/jpeg', 'image/gif', 'image/png', 'image/webp'])) {
                                if ($this->add_embedded_image($file_name, $c_id, 'image', 'base64', $m_ime)) {
                                    $image_cache[$file_name] = $c_id;
                                } else {
                                    $c_id = '';
                                }
                            }
                        }
                        if ($c_id && $c_id == $image_cache[$file_name]) {
                            if ($repl_tag = str_replace($image_name, 'cid:' . $c_id, $image[0])) {
                                $body = str_replace($image[0], $repl_tag, $body);
                                $re_set_body = true;
                            }
                        }
                    }
                }
            }
            if ($re_set_body) {
                $this->set_body($body);
            }
        }
    }
    public function set_subject($subject = null): void
    {
        // A. HTML entities in subjects must be replaced
        $subject = str_replace(['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'], ['&', '"', "'", '<', '>'], $subject);
        $this->set('Subject', $subject);
    }
    public function get_subject()
    {
        return $this->Subject;
    }
    public function set_body($body = null, $clear_sid = true): void
    {
        if ($clear_sid) {
            $body = $this->clear_sid_from_body($body);
        }
        $this->set('Body', $body);
    }
    public function get_body()
    {
        return $this->Body;
    }
    public function set_alt_body($alt_body = null, $clear_sid = true): void
    {
        if ($clear_sid) {
            $alt_body = $this->clear_sid_from_body($alt_body);
        }
        // A. alt body is used for plain text emails so we should eliminate HTML entities
        $alt_body = str_replace(['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'], ['&', '"', "'", '<', '>'], $alt_body);
        $this->set('AltBody', $alt_body);
    }
    public function get_alt_body()
    {
        return $this->alt_body;
    }
    public function set_recipient($address = null, $name = null): void
    {
        try {
            $address = $this->idn_to_ascii($address);
            parent::add_address($address, $name);
            // copying values as original class does not allow to access recipients array
            $this->_a_recipients[] = [$address, $name];
        } catch (Exception) {
        }
    }
    public function get_recipient()
    {
        return $this->_a_recipients;
    }
    public function get_cc(): array
    {
        return $this->cc;
    }
    public function get_bcc(): array
    {
        return $this->bcc;
    }
    public function clear_all_recipients(): void
    {
        $this->_a_recipients = [];
        parent::clear_all_recipients();
    }
    public function set_reply_to($email = null, $name = null): void
    {
        $email_validator = Container_Facade::get(Email_Validator_Service_Bridge_Interface::class);
        if (!$email_validator->is_email_valid($email)) {
            $email = $this->get_shop()->oxshops__oxorderemail->value;
        }
        $this->_a_replies[] = [$email, $name];
        try {
            parent::add_reply_to($email, $name);
        } catch (Exception) {
        }
    }
    public function get_reply_to()
    {
        return $this->_a_replies;
    }
    public function clear_reply_tos(): void
    {
        $this->_a_replies = [];
        parent::clear_reply_tos();
    }
    public function set_from($address, $name = '', $auto = true)
    {
        $address = substr($address, 0, 150);
        $name = substr((string) $name, 0, 150);
        $success = false;
        try {
            $success = parent::set_from($address, $name, $auto);
        } catch (Exception) {
        }
        return $success;
    }
    public function get_from()
    {
        return $this->From;
    }
    public function get_from_name()
    {
        return $this->from_name;
    }
    public function set_char_set($char_set = null): void
    {
        if ($char_set) {
            $this->_s_char_set = $char_set;
        } else {
            $this->_s_char_set = Registry::get_lang()->translate_string('charset');
        }
        $this->set('CharSet', $this->_s_char_set);
    }
    public function set_mailer($mailer = null): void
    {
        $this->set('Mailer', $mailer);
    }
    public function get_mailer()
    {
        return $this->Mailer;
    }
    public function set_host($host = null): void
    {
        $this->set('Host', $host);
    }
    public function get_error_info()
    {
        return $this->error_info;
    }
    public function set_mail_word_wrap($word_wrap = null): void
    {
        $this->set('WordWrap', $word_wrap);
    }
    public function set_use_inline_images($use_images = null): void
    {
        $this->_bl_inline_img_email = $use_images;
    }
    public function header_line($name, $value)
    {
        if (stripos((string) $name, 'X-') !== false) {
            return null;
        }
        return parent::header_line($name, $value);
    }
    protected function get_use_inline_images()
    {
        return $this->_bl_inline_img_email;
    }
    protected function send_mail_error_msg()
    {
        $recipients = $this->get_recipient();
        $owner_message = 'Error sending eMail(' . $this->get_subject() . ") to: \n\n";
        foreach ($recipients as $e_mail) {
            $owner_message .= $e_mail[0];
            $owner_message .= !empty($e_mail[1]) ? ' (' . $e_mail[1] . ')' : '';
            $owner_message .= " \n ";
        }
        $owner_message .= "\n\nError : " . $this->get_error_info();
        $shop = $this->get_shop();
        return @mail((string) $shop->oxshops__oxorderemail->value, 'eMail problem in shop!', $owner_message);
    }
    protected function add_user_info_order_e_mail($order)
    {
        return $order;
    }
    protected function add_user_register_email($user)
    {
        return $user;
    }
    protected function add_forgot_pwd_email($shop)
    {
        return $shop;
    }
    protected function add_newsletter_db_opt_in_mail($user)
    {
        return $user;
    }
    protected function clear_mailer()
    {
        $this->clear_all_recipients();
        $this->clear_reply_tos();
        $this->clear_attachments();
        $this->error_info = '';
    }
    protected function set_mail_params($shop = null)
    {
        $this->clear_mailer();
        if (!$shop) {
            $shop = $this->get_shop();
        }
        $this->set_from($shop->oxshops__oxorderemail->value, $shop->oxshops__oxname->get_raw_value());
        $this->set_smtp($shop);
    }
    public function get_shop($lang_id = null, $shop_id = null)
    {
        if ($lang_id === null && $shop_id === null) {
            return $this->_o_shop ?? $this->_o_shop = Registry::get_config()->get_active_shop();
        }
        $my_config = Registry::get_config();
        $shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        if ($shop_id !== null) {
            $shop->set_shop_id($shop_id);
        }
        if ($lang_id !== null) {
            $shop->set_language($lang_id);
        }
        $shop->load($my_config->get_shop_id());
        return $shop;
    }
    protected function set_smtp_auth_info($user_name = null, $user_password = null)
    {
        $this->set('SMTPAuth', true);
        $this->set('Username', $user_name);
        $this->set('Password', $user_password);
    }
    protected function set_smtp_debug($debug = null)
    {
        $this->set('SMTPDebug', $debug);
    }
    protected function make_output_processing()
    {
        $output = ox_new(\Oxid_Esales\Eshop\Core\Output::class);
        $this->set_body($output->process($this->get_body(), 'oxemail'));
        $this->set_alt_body($output->process($this->get_alt_body(), 'oxemail'));
        $output->process_email($this);
    }
    protected function send_mail()
    {
        $result = false;
        try {
            $result = parent::send();
        } catch (Exception $exception) {
            $ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class);
            $ex->set_message($exception->get_message());
            if ($this->is_debug_mode_enabled()) {
                throw $ex;
            }
            Registry::get_logger()->error($ex->get_message(), [$ex]);
        }
        return $result;
    }
    protected function process_view_array()
    {
        $output_processor = ox_new(\Oxid_Esales\Eshop\Core\Output::class);
        // processing assigned template variables
        $new_array = $output_processor->process_view_array($this->_a_view_data, 'oxemail');
        $this->_a_view_data = array_merge($this->_a_view_data, $new_array);
    }
    public function get_charset()
    {
        if (!$this->_s_char_set) {
            return Registry::get_lang()->translate_string('charset');
        }
        return $this->char_set;
    }
    public function set_shop($shop): void
    {
        $this->_o_shop = $shop;
    }
    public function get_view_config()
    {
        return Registry::get_config()->get_active_view()->get_view_config();
    }
    public function get_view()
    {
        return Registry::get_config()->get_active_view();
    }
    public function get_currency()
    {
        $config = Registry::get_config();
        return $config->get_act_shop_currency_object();
    }
    public function set_view_data($key, $value): void
    {
        $this->_a_view_data[$key] = $value;
    }
    public function get_view_data()
    {
        return $this->_a_view_data;
    }
    public function get_view_data_item($key)
    {
        if (isset($this->_a_view_data[$key])) {
            return $this->_a_view_data;
        }
    }
    public function set_user($user): void
    {
        $this->_a_view_data['oUser'] = $user;
    }
    public function get_user()
    {
        return $this->_a_view_data['oUser'];
    }
    public function get_order_file_list($order_id)
    {
        $order_file_list = ox_new(Order_File_List::class);
        $order_file_list->load_order_files($order_id);
        if (count($order_file_list) > 0) {
            return $order_file_list;
        }
        return false;
    }
    private function clear_sid_from_body($alt_body)
    {
        return Str::get_str()->preg_replace('/(\?|&(amp;)?)(force_)?(admin_)?sid=[A-Z0-9\.]+/i', '\1shp=' . Registry::get_config()->get_shop_id(), $alt_body);
    }
    protected function get_utils_object_instance()
    {
        return Registry::get_utils_object();
    }
    private function is_debug_mode_enabled(): mixed
    {
        return Container_Facade::get_parameter('oxid_esales.debug_mode');
    }
    private function get_user_id_by_user_name($user_name, $shop_id)
    {
        $select = "SELECT `OXID` \n          FROM `oxuser` \n          WHERE `OXACTIVE` = 1 \n          AND `OXUSERNAME` = :oxusername \n          AND `OXPASSWORD` != ''";
        if (Registry::get_config()->get_config_param('blMallUsers')) {
            $select .= ' ORDER BY OXSHOPID = :oxshopid DESC';
        } else {
            $select .= ' AND OXSHOPID = :oxshopid';
        }
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($select, ['oxusername' => $user_name, 'oxshopid' => $shop_id]);
    }
    private function should_product_review_links_be_included(): bool
    {
        $config = Registry::get_config();
        $reviews_enabled = $config->get_config_param('bl_perfLoadReviews', false);
        $product_review_link_inclusion_enabled = $config->get_config_param('includeProductReviewLinksInEmail', false);
        return $reviews_enabled && $product_review_link_inclusion_enabled;
    }
    private function idn_to_ascii($idn)
    {
        if (function_exists('idn_to_ascii')) {
            $parts = explode('@', (string) $idn);
            return $parts[0] . '@' . idn_to_ascii($parts[1]);
        }
        return $idn;
    }
    private function switch_to_shop_mode(): void
    {
        Registry::get_config()->set_admin_mode(false);
        $this->dispatch_admin_mode_changed_event();
    }
    private function switch_to_admin_mode(): void
    {
        Registry::get_config()->set_admin_mode(true);
        $this->dispatch_admin_mode_changed_event();
    }
    private function dispatch_admin_mode_changed_event(): void
    {
        Container_Facade::get(Event_Dispatcher_Interface::class)->dispatch(new Admin_Mode_Changed_Event());
    }
    private function are_order_emails_disabled(): bool
    {
        return Container_Facade::has_parameter('oxid_esales.email.disable_order_emails') && Container_Facade::get_parameter('oxid_esales.email.disable_order_emails');
    }
    private function is_symfony_mailer_enabled(): bool
    {
        return Container_Facade::has_parameter('oxid_esales.mailing.use_symfony_mailer') && Container_Facade::get_parameter('oxid_esales.mailing.use_symfony_mailer');
    }
}