<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Shop;
use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Exception\Standard_Exception;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Admin article main shop manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Main Menu -> Core Settings -> Main.
 */
class Shop_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** Identifies new shop. */
    public const NEW_SHOP_ID = '-1';
    /**
     * Shop field set size, limited to 64bit by MySQL
     *
     * @var int
     */
    public const SHOP_FIELD_SET_SIZE = 64;
    /**
     * Controller render method, which returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        $config = Registry::get_config();
        parent::render();
        $shop_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        $template_name = $this->render_new_shop();
        if ($template_name) {
            return $template_name;
        }
        $user = $this->get_user();
        $shop_id = $this->update_shop_id_by_user($user, $shop_id, true);
        if (isset($shop_id) && $shop_id != self::NEW_SHOP_ID) {
            $shop = ox_new(Shop::class);
            $subj_lang = Registry::get_request()->get_request_escaped_parameter('subjlang');
            if (!isset($subj_lang)) {
                $subj_lang = $this->_i_edit_lang;
            }
            if ($subj_lang && $subj_lang > 0) {
                $this->_a_view_data['subjlang'] = $subj_lang;
            }
            $shop->load_in_lang($subj_lang, $shop_id);
            $this->_a_view_data['edit'] = $shop;
            Registry::get_session()->set_variable('shp', $shop_id);
        }
        $this->check_parent($shop);
        $this->_a_view_data['IsOXDemoShop'] = $config->is_demo_shop();
        if (!isset($this->_a_view_data['updatenav'])) {
            $this->_a_view_data['updatenav'] = Registry::get_request()->get_request_escaped_parameter('updatenav');
        }
        return 'shop_main';
    }
    /**
     * Saves changed main shop configuration parameters.
     */
    public function save(): void
    {
        parent::save();
        $config = Registry::get_config();
        $shop_id = $this->get_edit_object_id();
        $parameters = Registry::get_request()->get_request_escaped_parameter('editval');
        $user = $this->get_user();
        $shop_id = $this->update_shop_id_by_user($user, $shop_id, false);
        //  #918 S
        // checkbox handling
        $parameters['oxshops__oxactive'] = isset($parameters['oxshops__oxactive']) && $parameters['oxshops__oxactive'] == true ? 1 : 0;
        $parameters['oxshops__oxproductive'] = isset($parameters['oxshops__oxproductive']) && $parameters['oxshops__oxproductive'] == true ? 1 : 0;
        $subj_lang = Registry::get_request()->get_request_escaped_parameter('subjlang');
        $shop_language_id = $subj_lang && $subj_lang > 0 ? $subj_lang : 0;
        $shop = ox_new(Shop::class);
        if ($shop_id != self::NEW_SHOP_ID) {
            $shop->load_in_lang($shop_language_id, $shop_id);
        } else {
            $parameters = $this->update_parameters($parameters);
        }
        if (isset($parameters['oxshops__oxsmtp']) && $parameters['oxshops__oxsmtp']) {
            $parameters['oxshops__oxsmtp'] = trim((string) $parameters['oxshops__oxsmtp']);
        }
        $shop->set_language(0);
        $shop->assign($parameters);
        $shop->set_language($shop_language_id);
        if ($new_smpt_pass = Registry::get_request()->get_request_escaped_parameter('oxsmtppwd')) {
            $shop->oxshops__oxsmtppwd->set_value($new_smpt_pass == '-' ? '' : $new_smpt_pass);
        }
        $can_create_shop = $this->can_create_shop($shop_id, $shop);
        if (!$can_create_shop) {
            return;
        }
        try {
            $shop->save();
        } catch (Standard_Exception $e) {
            $this->check_exception_type($e);
            return;
        }
        $this->_a_view_data['updatelist'] = '1';
        $this->update_shop_information($config, $shop, $shop_id);
        Registry::get_session()->set_variable('actshop', $shop_id);
    }
    /**
     * Returns array of config variables which cannot be copied
     */
    protected function get_non_copy_config_vars(): array
    {
        $non_copy_vars = ['aSerials', 'IMS', 'IMD', 'IMA', 'sBackTag', 'sUtilModule'];
        $multi_shop_tables = Container_Facade::get_parameter('oxid_esales.multi_shop_tables');
        foreach ($multi_shop_tables as $multi_shop_table) {
            $non_copy_vars[] = 'blMallInherit_' . strtolower((string) $multi_shop_table);
        }
        return $non_copy_vars;
    }
    /**
     * Copies base shop config variables to current
     *
     * @param Shop $shop new shop object
     */
    protected function copy_config_vars($shop)
    {
        $config = Registry::get_config();
        $utils_object = Registry::get_utils_object();
        $db = Database_Provider::get_db();
        $non_copy_vars = $this->get_non_copy_config_vars();
        $select_shop_configuration_query = "select oxvarname, oxvartype, oxvarvalue, oxmodule\n            from oxconfig where oxshopid = '1'";
        $shop_configuration = $db->select($select_shop_configuration_query);
        if ($shop_configuration != false && $shop_configuration->count() > 0) {
            while (!$shop_configuration->EOF) {
                $config_name = $shop_configuration->fields['oxvarname'];
                if (!in_array($config_name, $non_copy_vars)) {
                    $new_id = $utils_object->generate_uid();
                    $insert_new_config_query = 'insert into oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue, oxmodule)
                         values (:oxid, :oxshopid, :oxvarname, :oxvartype, :value, :oxmodule)';
                    $db->execute($insert_new_config_query, ['oxid' => $new_id, 'oxshopid' => $shop->get_id(), 'oxvarname' => $shop_configuration->fields['oxvarname'], 'oxvartype' => $shop_configuration->fields['oxvartype'], 'value' => $shop_configuration->fields['oxvarvalue'], 'oxmodule' => $shop_configuration->fields['oxmodule']]);
                }
                $shop_configuration->fetch_row();
            }
        }
        $inherit_all = $shop->get_field_data('oxisinherited') ? 'true' : 'false';
        foreach (Container_Facade::get_parameter('oxid_esales.multi_shop_tables') as $multi_shop_table) {
            $config->save_shop_conf_var('bool', 'blMallInherit_' . strtolower((string) $multi_shop_table), $inherit_all, $shop->get_id());
        }
    }
    /**
     * Return template name for new shop if it is different from standard.
     *
     * @return string
     */
    protected function render_new_shop()
    {
        return '';
    }
    /**
     * Check user rights and change userId if it needs.
     *
     * @param User $user
     * @param string $shopId
     * @param bool $updateViewData Update view data when shop ID changes.
     *
     * @return string
     */
    protected function update_shop_id_by_user($user, $shop_id, $update_view_data = false)
    {
        return $shop_id;
    }
    /**
     * Load Shop parent and set result to _aViewData.
     *
     * @param Shop $shop
     */
    protected function check_parent($shop)
    {
    }
    /**
     * Unset not used Shop parameters.
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function update_parameters($parameters)
    {
        $parameters['oxshops__oxid'] = null;
        return $parameters;
    }
    /**
     * Check for exception type and set it to _aViewData.
     *
     * @param StandardException $exception
     */
    protected function check_exception_type($exception)
    {
    }
    /**
     * Check if Shop can be created.
     *
     * @param string $shopId
     * @param Shop $shop
     *
     * @return bool
     */
    protected function can_create_shop($shop_id, $shop)
    {
        return true;
    }
    /**
     * Update shop information in DB and oxConfig.
     *
     * @param \OxidEsales\Eshop\Core\Config $config
     * @param Shop $shop
     * @param string $shopId
     */
    protected function update_shop_information($config, $shop, $shop_id)
    {
    }
}