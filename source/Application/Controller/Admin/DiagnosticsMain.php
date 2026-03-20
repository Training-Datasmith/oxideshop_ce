<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Module\Module;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Shop_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
/**
 * Checks Version of System files.
 * Admin Menu: Service -> Version Checker -> Main.
 */
class Diagnostics_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * error tag
     *
     * @var boolean
     */
    protected $_bl_error = false;
    /**
     * error message
     *
     * @var string
     */
    protected $_s_error_message;
    /**
     * Diagnostic check object
     *
     * @var mixed
     */
    protected $_o_diagnostics;
    /**
     * Result output object
     *
     * @var mixed
     */
    protected $_o_output;
    /**
     * Variable for storing shop root directory
     *
     * @var mixed|string
     */
    protected $_s_shop_dir = '';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'diagnostics_main';
    /**
     * Error status getter
     *
     * @return string
     */
    protected function has_error()
    {
        return $this->_bl_error;
    }
    /**
     * Error status getter
     *
     * @return string
     */
    protected function get_error_message()
    {
        return $this->_s_error_message;
    }
    /**
     * Calls parent constructor and initializes checker object
     */
    public function __construct()
    {
        parent::__construct();
        $this->_s_shop_dir = Container_Facade::get_parameter('oxid_esales.shop_source_directory');
        $this->_o_output = ox_new(\Oxid_Esales\Eshop\Application\Model\Diagnostics_Output::class);
    }
    /**
     * @return string
     */
    public function render()
    {
        parent::render();
        if ($this->has_error()) {
            $this->_a_view_data['sErrorMessage'] = $this->get_error_message();
        }
        return 'diagnostics_form';
    }
    /**
     * Checks system file versions
     */
    public function start_diagnostics(): void
    {
        $this->_o_output->store_result($this->get_rendered_report($this->run_basic_diagnostics()));
        $this->_a_view_data['sResult'] = $this->_o_output->read_result_file();
    }
    /**
     * Performs main system diagnostic.
     * Shop and module details, database health, php parameters, server information
     *
     * @return array
     */
    protected function run_basic_diagnostics()
    {
        $a_view_data = [];
        $o_diagnostics = ox_new(\Oxid_Esales\Eshop\Application\Model\Diagnostics::class);
        $o_diagnostics->set_shop_link(Container_Facade::get_parameter('oxid_esales.shop_url'));
        $o_diagnostics->set_edition(Registry::get_config()->get_full_edition());
        $o_diagnostics->set_version(ox_new(\Oxid_Esales\Eshop\Core\Shop_Version::class)->get_version());
        /**
         * Shop
         */
        if ($this->get_param('runAnalysis')) {
            $a_view_data['runAnalysis'] = true;
            $a_view_data['aShopDetails'] = $o_diagnostics->get_shop_details();
        }
        /**
         * Modules
         */
        if ($this->get_param('oxdiag_frm_modules')) {
            $a_view_data['oxdiag_frm_modules'] = true;
            $a_view_data['mylist'] = $this->get_installed_modules();
        }
        /**
         * Health
         */
        if ($this->get_param('oxdiag_frm_health')) {
            $o_sys_req = ox_new(\Oxid_Esales\Eshop\Core\System_Requirements::class);
            $a_view_data['oxdiag_frm_health'] = true;
            $a_view_data['aInfo'] = $o_sys_req->get_system_info();
            $a_view_data['aCollations'] = $o_sys_req->check_collation();
        }
        /**
         * PHP info
         * Fetches a hand full of php configuration parameters and collects their values.
         */
        if ($this->get_param('oxdiag_frm_php')) {
            $a_view_data['oxdiag_frm_php'] = true;
            $a_view_data['aPhpConfigparams'] = $o_diagnostics->get_php_selection();
            $a_view_data['sPhpDecoder'] = $o_diagnostics->get_php_decoder();
        }
        /**
         * Server info
         */
        if ($this->get_param('oxdiag_frm_server')) {
            $a_view_data['isExecAllowed'] = $o_diagnostics->is_exec_allowed();
            $a_view_data['oxdiag_frm_server'] = true;
            $a_view_data['aServerInfo'] = $o_diagnostics->get_server_info();
        }
        return $a_view_data;
    }
    /**
     * Downloads result of system file check
     */
    public function download_result_file(): void
    {
        $this->_o_output->download_result_file();
        exit(0);
    }
    /**
     * Checks system file versions
     *
     * @return string
     */
    public function get_support_contact_form()
    {
        $a_links = ['de' => 'https://www.oxid-esales.com/ressourcen/anwenderbereich/supportangebot/', 'en' => 'https://www.oxid-esales.com/en/resources/user-center/support-offer/'];
        $o_lang = Registry::get_lang();
        $a_languages = $o_lang->get_language_array();
        $i_lang_id = $o_lang->get_tpl_language();
        $s_lang_code = $a_languages[$i_lang_id]->abbr;
        if (!array_key_exists($s_lang_code, $a_links)) {
            $s_lang_code = 'de';
        }
        return $a_links[$s_lang_code];
    }
    /**
     * Request parameter getter
     *
     * @param string $name
     *
     * @return string
     */
    public function get_param($name)
    {
        $request = Registry::get(\Oxid_Esales\Eshop\Core\Request::class);
        return $request->get_request_escaped_parameter($name);
    }
    private function get_installed_modules(): array
    {
        $shop_configuration = Container_Facade::get(Shop_Configuration_Dao_Bridge_Interface::class)->get();
        $modules = [];
        foreach ($shop_configuration->get_module_configurations() as $module_configuration) {
            $module = ox_new(Module::class);
            $module->load($module_configuration->get_id());
            $modules[$module_configuration->get_id()] = $module;
        }
        return $modules;
    }
    private function get_rendered_report(array $diagnostics_result): string
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer()->render_template($this->_s_this_template, $diagnostics_result);
    }
}