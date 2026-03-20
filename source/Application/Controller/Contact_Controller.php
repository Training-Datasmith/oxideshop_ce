<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Email;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form\Contact_Form_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form\Form_Field;
/**
 * Contact window.
 * Arranges "CONTACT" window, by creating form for user opinion (etc.)
 * submission. After user correctly
 * fulfils all required fields all information is sent to shop owner by
 * email. OXID eShop -> CONTACT.
 */
class Contact_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Entered user data.
     *
     * @var array
     */
    protected $_a_user_data;
    /**
     * Entered contact subject.
     *
     * @var string
     */
    protected $_s_contact_subject;
    /**
     * Entered conatct message.
     *
     * @var string
     */
    protected $_s_contact_message;
    /**
     * Contact email send status.
     *
     * @var null|int
     */
    protected $_bl_contact_send_status;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'page/info/contact';
    /**
     * Current view search engine indexing state
     *
     * @var int
     */
    protected $_i_view_index_state = VIEW_INDEXSTATE_NOINDEXNOFOLLOW;
    /**
     * @return string
     */
    public function render()
    {
        $form = Container_Facade::get(Contact_Form_Bridge_Interface::class)->get_contact_form();
        /** @var FormField $formField */
        foreach ($form->get_fields() as $key => $form_field) {
            $this->_a_view_data['contactFormFields'][$key] = ['name' => $form_field->get_name(), 'label' => $form_field->get_label(), 'isRequired' => $form_field->is_required()];
        }
        return parent::render();
    }
    /**
     * Composes and sends user written message, returns false if some parameters
     * are missing.
     *
     * @return bool
     */
    public function send()
    {
        $contact_form_bridge = Container_Facade::get(Contact_Form_Bridge_Interface::class);
        $form = $contact_form_bridge->get_contact_form();
        $form->handle_request($this->get_mapped_contact_form_request());
        if ($form->is_valid()) {
            $this->send_contact_mail($form->email->get_value(), $form->subject->get_value(), $contact_form_bridge->get_contact_form_message($form));
        } else {
            foreach ($form->get_errors() as $error) {
                Registry::get_utils_view()->add_error_to_display($error);
            }
            return false;
        }
    }
    /**
     * Template variable getter. Returns entered user data
     *
     * @return object
     */
    public function get_user_data()
    {
        if ($this->_a_user_data === null) {
            $this->_a_user_data = Registry::get_request()->get_request_escaped_parameter('editval');
        }
        return $this->_a_user_data;
    }
    /**
     * Template variable getter. Returns entered contact subject
     *
     * @return object
     */
    public function get_contact_subject()
    {
        if ($this->_s_contact_subject === null) {
            $this->_s_contact_subject = Registry::get_request()->get_request_escaped_parameter('c_subject');
        }
        return $this->_s_contact_subject;
    }
    /**
     * Template variable getter. Returns entered message
     *
     * @return object
     */
    public function get_contact_message()
    {
        if ($this->_s_contact_message === null) {
            $this->_s_contact_message = Registry::get_request()->get_request_escaped_parameter('c_message');
        }
        return $this->_s_contact_message;
    }
    /**
     * Template variable getter. Returns status if email was send succesfull
     *
     * @return null|int
     */
    public function get_contact_send_status()
    {
        return $this->_bl_contact_send_status;
    }
    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function get_bread_crumb()
    {
        $title = Registry::get_lang()->translate_string('CONTACT', Registry::get_lang()->get_base_language(), false);
        return [['title' => $title, 'link' => $this->get_link()]];
    }
    /**
     * Page title
     *
     * @return string
     */
    public function get_title()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_active_shop()->oxshops__oxcompany->value;
    }
    private function get_mapped_contact_form_request(): array
    {
        $request = Registry::get_request();
        $person_data = $request->get_request_escaped_parameter('editval');
        return ['email' => $person_data['oxuser__oxusername'] ?? '', 'firstName' => $person_data['oxuser__oxfname'] ?? '', 'lastName' => $person_data['oxuser__oxlname'] ?? '', 'salutation' => $person_data['oxuser__oxsal'] ?? '', 'subject' => $request->get_request_escaped_parameter('c_subject'), 'message' => $request->get_request_escaped_parameter('c_message')];
    }
    /**
     * Send a contact mail.
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     */
    private function send_contact_mail($email, $subject, $message): void
    {
        $mailer = ox_new(Email::class);
        if ($mailer->send_contact_mail($email, $subject, $message)) {
            $this->_bl_contact_send_status = 1;
        } else {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_CHECK_EMAIL');
        }
    }
}