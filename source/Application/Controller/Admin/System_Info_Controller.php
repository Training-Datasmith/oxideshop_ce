<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
/**
 * Admin systeminfo manager.
 * Returns template "systeminfo" and phphinfo() result to frame.
 */
class System_Info_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Executes parent method parent::render(), prints shop and
     * PHP configuration information.
     */
    public function render()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        parent::render();
        $o_auth_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
        $o_auth_user->load_admin_user();
        $blis_mall_admin = $o_auth_user->oxuser__oxrights->value == 'malladmin';
        if ($blis_mall_admin && !$my_config->is_demo_shop()) {
            $a_class_vars = get_object_vars($my_config);
            $a_system_info = [];
            $a_system_info['pkg.info'] = $my_config->get_package_info();
            foreach ($a_class_vars as $name => $value) {
                if (gettype($value) == 'object') {
                    continue;
                }
                if (!$this->is_class_variable_visible($name)) {
                    continue;
                }
                $value = var_export($value, true);
                $value = str_replace("\n", '<br>', $value);
                $a_system_info[$name] = $value;
            }
            $context = ['oViewConf' => $this->_a_view_data['oViewConf'], 'oView' => $this->_a_view_data['oView'], 'shop' => $this->_a_view_data['shop'] ?? 1, 'isdemo' => $my_config->is_demo_shop(), 'aSystemInfo' => $a_system_info];
            ob_start();
            echo $this->get_renderer()->render_template('systeminfo', $context);
            echo '<br><br>';
            phpinfo();
            $s_message = ob_get_clean();
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->show_message_and_exit($s_message);
        } else {
            return \Oxid_Esales\Eshop\Core\Registry::get_utils()->show_message_and_exit('Access denied !');
        }
    }
    /**
     * @internal
     *
     * @return TemplateRendererInterface
     */
    private function get_renderer()
    {
        return Container_Facade::get(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    /**
     * Checks if class var can be shown in systeminfo.
     *
     * @param string $varName
     * @return bool
     */
    protected function is_class_variable_visible($var_name)
    {
        return !in_array($var_name, ['oDB', 'dbUser', 'dbPwd', 'oSerial', 'aSerials', 'sSerialNr']);
    }
}