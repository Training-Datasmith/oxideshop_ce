<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Exception;
use Oxid_Esales\Eshop\Application\Model\Category;
use Oxid_Esales\Eshop\Application\Model\Shop;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Display_Error;
use Oxid_Esales\Eshop\Core\No_Js_Validator;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Contact\Form\Contact_Form_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Form_Configuration\Field_Configuration_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Module_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Event\Setting_Changed_Event;
/**
 * Admin shop config manager.
 * Collects shop config information, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> General.
 */
class Shop_Configuration extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    protected $_s_this_template = 'shop_config';
    protected $_a_skip_multiline = ['aHomeCountry'];
    protected $_a_parse_float = ['iMinOrderPrice'];
    protected $_a_conf_params = ['bool' => 'confbools', 'str' => 'confstrs', 'arr' => 'confarrs', 'aarr' => 'confaarrs', 'select' => 'confselects', 'num' => 'confnum'];
    /**
     * Executes parent method parent::render(), passes shop configuration parameters
     * to template engine and returns name of template file "shop_config".
     *
     * @return string
     */
    public function render()
    {
        $config = Registry::get_config();
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $this->_a_view_data['edit'] = $shop = $this->get_edit_shop($sox_id);
            try {
                // category choosen as default
                $this->_a_view_data['defcat'] = null;
                if ($shop->oxshops__oxdefcat->value) {
                    $category = ox_new(Category::class);
                    if ($category->load($shop->oxshops__oxdefcat->value)) {
                        $this->_a_view_data['defcat'] = $category;
                    }
                }
            } catch (Exception) {
                // on most cases this means that views are broken, so just
                // outputting notice and keeping functionality flow ..
                $this->_a_view_data['updateViews'] = 1;
            }
            $aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
            if ($aoc == 1) {
                $shop_default_category_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Default_Category_Ajax::class);
                $this->_a_view_data['oxajax'] = $shop_default_category_ajax->get_columns();
                return 'popups/shop_default_category';
            }
        }
        $db_variables = $this->load_conf_vars($sox_id, $this->get_module_for_config_vars());
        $conf_vars = $db_variables['vars'];
        $conf_vars['str']['sVersion'] = $config->get_config_param('sVersion');
        $this->_a_view_data['var_constraints'] = $db_variables['constraints'];
        $this->_a_view_data['var_grouping'] = $db_variables['grouping'];
        foreach ($this->_a_conf_params as $type => $param) {
            $this->_a_view_data[$param] = $conf_vars[$type];
        }
        // #251A passing country list
        $country_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Country_List::class);
        $country_list->load_active_countries(Registry::get_lang()->get_object_tpl_language());
        if (isset($conf_vars['arr']['aHomeCountry']) && count($conf_vars['arr']['aHomeCountry']) && count($country_list)) {
            foreach ($country_list as $s_country_id => $o_country) {
                if (in_array($o_country->oxcountry__oxid->value, $conf_vars['arr']['aHomeCountry'])) {
                    $country_list[$s_country_id]->selected = '1';
                }
            }
        }
        $this->_a_view_data['countrylist'] = $country_list;
        // checking if cUrl is enabled
        $this->_a_view_data['blCurlIsActive'] = !function_exists('curl_init') ? false : true;
        $contact_form_configuration = Container_Facade::get(Contact_Form_Bridge_Interface::class)->get_contact_form_configuration();
        /** @var FieldConfigurationInterface $fieldConfiguration */
        foreach ($contact_form_configuration->get_field_configurations() as $field_configuration) {
            $this->_a_view_data['contactFormFieldConfigurations'][] = ['name' => $field_configuration->get_name(), 'label' => $field_configuration->get_label(), 'isRequired' => $field_configuration->is_required()];
        }
        return $this->_s_this_template;
    }
    /**
     * return theme filter for config variables
     *
     * @return string
     */
    protected function get_module_for_config_vars()
    {
        return '';
    }
    /**
     * Saves shop configuration variables
     */
    public function save_conf_vars(): void
    {
        $config = Registry::get_config();
        $this->reset_content_cache();
        $config_validator = ox_new(No_Js_Validator::class);
        foreach ($this->_a_conf_params as $existing_config_type => $existing_config_name) {
            $request_value = Registry::get_request()->get_request_parameter($existing_config_name);
            if (is_array($request_value)) {
                foreach ($request_value as $config_name => $new_config_value) {
                    $old_value = $config->get_config_param($config_name);
                    if ($new_config_value !== $old_value) {
                        $s_value_to_validate = is_array($new_config_value) ? join(', ', $new_config_value) : $new_config_value;
                        if (!$config_validator->is_valid($s_value_to_validate)) {
                            $error = ox_new(Display_Error::class);
                            $error->set_format_parameters(htmlspecialchars((string) $s_value_to_validate));
                            $error->set_message('SHOP_CONFIG_ERROR_INVALID_VALUE');
                            Registry::get_utils_view()->add_error_to_display($error);
                            continue;
                        }
                        $this->save_setting($config_name, $existing_config_type, $new_config_value);
                    }
                }
            }
        }
    }
    /**
     * Saves changed shop configuration parameters.
     */
    public function save(): void
    {
        // saving config params
        $this->save_conf_vars();
        //saving additional fields ("oxshops__oxdefcat"") that goes directly to shop (not config)
        /** @var Shop $shop */
        $shop = ox_new(Shop::class);
        if ($shop->load($this->get_edit_object_id())) {
            $shop->assign(Registry::get_request()->get_request_escaped_parameter('editval'));
            $shop->save();
        }
    }
    /**
     * Load and parse config vars from db.
     * Return value is a map:
     *      'vars'        => config variable values as array[type][name] = value
     *      'constraints' => constraints list as array[name] = constraint
     *      'grouping'    => grouping info as array[name] = grouping
     *
     * @param int    $shopId Shop id
     * @param string $moduleId module to load (empty string is for base values)
     *
     * @return array
     */
    public function load_conf_vars($shop_id, $module_id)
    {
        Registry::get_config();
        $configuration_variables = ['bool' => [], 'str' => [], 'arr' => [], 'aarr' => [], 'select' => []];
        $constraints = [];
        $groupings = [];
        $rs = Database_Provider::get_db()->select('select cfg.oxvarname,
                    cfg.oxvartype,
                    cfg.oxvarvalue,
                    disp.oxvarconstraint,
                    disp.oxgrouping
                from oxconfig as cfg
                    left join oxconfigdisplay as disp
                        on cfg.oxmodule=disp.oxcfgmodule and cfg.oxvarname=disp.oxcfgvarname
                where cfg.oxshopid = :oxshopid
                    and cfg.oxmodule = :oxmodule
                order by disp.oxpos, cfg.oxvarname', ['oxshopid' => $shop_id, 'oxmodule' => $module_id]);
        if ($rs != false && $rs->count() > 0) {
            while (!$rs->EOF) {
                [$name, $type, $value, $constraint, $grouping] = array_values($rs->fields);
                $configuration_variables[$type][$name] = $this->unserialize_conf_var($type, $name, $value);
                $constraints[$name] = $this->parse_constraint($type, $constraint);
                if ($grouping) {
                    if (!isset($groupings[$grouping])) {
                        $groupings[$grouping] = [$name => $type];
                    } else {
                        $groupings[$grouping][$name] = $type;
                    }
                }
                $rs->fetch_row();
            }
        }
        return ['vars' => $configuration_variables, 'constraints' => $constraints, 'grouping' => $groupings];
    }
    /**
     * If allow to configure information sending to OXID.
     * For PE and EE users it is always turned on.
     *
     * @return bool
     */
    public function information_sending_to_oxid_configurable()
    {
        return !Registry::get_config()->get_edition()->is_community_edition();
    }
    /**
     * parse constraint from type and serialized values
     *
     * @param string $type       variable type
     * @param string $constraint serialized constraint
     *
     * @return mixed
     */
    protected function parse_constraint($type, $constraint)
    {
        return match ($type) {
            'select' => array_map(trim(...), explode('|', $constraint)),
            default => null,
        };
    }
    /**
     * serialize constraint from type and value
     *
     * @param string $type       variable type
     * @param mixed  $constraint constraint value
     *
     * @return string
     */
    protected function serialize_constraint($type, $constraint)
    {
        return match ($type) {
            'select' => implode('|', array_map(trim(...), $constraint)),
            default => '',
        };
    }
    /**
     * Unserialize config var depending on it's type
     *
     * @param string $type  var type
     * @param string $name  var name
     * @param string $value var value
     *
     * @return mixed
     */
    public function unserialize_conf_var($type, $name, $value)
    {
        $str = Str::get_str();
        $data = null;
        switch ($type) {
            case 'bool':
                $data = $value == 'true' || $value == '1';
                break;
            case 'str':
            case 'select':
            case 'num':
            case 'int':
                $data = $str->htmlentities($value);
                if (in_array($name, $this->_a_parse_float)) {
                    $data = str_replace(',', '.', $data);
                }
                break;
            case 'arr':
                if (in_array($name, $this->_a_skip_multiline)) {
                    $data = unserialize($value);
                } else {
                    $data = $str->htmlentities($this->array_to_multiline(unserialize($value)));
                }
                break;
            case 'aarr':
                if (in_array($name, $this->_a_skip_multiline)) {
                    $data = unserialize($value);
                } else {
                    $data = $str->htmlentities($this->aarray_to_multiline(unserialize($value)));
                }
                break;
        }
        return $data;
    }
    /**
     * Prepares data for storing to database.
     * Example: $sType='aarr', $sName='someName', $mValue='key1=>val1\nkey2=>val2'
     *
     * @param string $type  var type
     * @param string $name  var name
     * @param mixed  $value var value
     *
     * @return string
     */
    public function serialize_conf_var($type, $name, $value)
    {
        $data = $value;
        switch ($type) {
            case 'bool':
                break;
            case 'str':
            case 'select':
            case 'int':
                if (in_array($name, $this->_a_parse_float)) {
                    $data = str_replace(',', '.', $data);
                }
                break;
            case 'arr':
                if (!is_array($value)) {
                    $data = $this->multiline_to_array($value);
                }
                break;
            case 'aarr':
                $data = $this->multiline_to_aarray($value);
                break;
        }
        return $data;
    }
    /**
     * Converts simple array to multiline text. Returns this text.
     *
     * @param array $input Array with text
     *
     * @return string
     */
    protected function array_to_multiline($input)
    {
        return implode("\n", (array) $input);
    }
    /**
     * Converts Multiline text to simple array. Returns this array.
     *
     * @param string $multiline Multiline text
     *
     * @return array
     */
    protected function multiline_to_array($multiline)
    {
        $array = explode("\n", $multiline);
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = trim($value);
                if ($array[$key] == '') {
                    unset($array[$key]);
                }
            }
            return $array;
        }
    }
    /**
     * Converts associative array to multiline text. Returns this text.
     *
     * @param array $input Array to convert
     *
     * @return string
     */
    protected function aarray_to_multiline($input)
    {
        if (is_array($input)) {
            $multiline = '';
            foreach ($input as $key => $value) {
                if ($multiline) {
                    $multiline .= "\n";
                }
                if (is_string($value)) {
                    $multiline .= $key . ' => ' . $value;
                } else {
                    Registry::get_logger()->warning('The value in ShopConfiguration::aarrayToMultiline() is not a string. The nested values are not supported.', [$value]);
                }
            }
            return $multiline;
        }
    }
    /**
     * Converts Multiline text to associative array. Returns this array.
     *
     * @param string $multiline Multiline text
     *
     * @return array
     */
    protected function multiline_to_aarray($multiline)
    {
        $string = Str::get_str();
        $array = [];
        $lines = explode("\n", $multiline);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line != '' && $string->preg_match('/(.+)=>(.+)/', $line, $regs)) {
                $key = trim((string) $regs[1]);
                $value = trim((string) $regs[2]);
                if ($key != '' && $value != '') {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }
    /**
     * Returns active/editable object id
     *
     * @return string
     */
    public function get_edit_object_id()
    {
        $edit_id = parent::get_edit_object_id();
        if (!$edit_id) {
            return Registry::get_config()->get_shop_id();
        }
        return $edit_id;
    }
    /**
     * @param mixed $configValue
     */
    private function save_setting(string $config_name, string $existing_config_type, $config_value): void
    {
        $shop_id = (int) $this->get_edit_object_id();
        $module = $this->get_module_for_config_vars();
        $prepared_config_value = $this->serialize_conf_var($existing_config_type, $config_name, $config_value);
        if (str_contains($module, 'module:')) {
            $module_id = explode(':', $module)[1];
            $module_configuration_bridge = Container_Facade::get(Module_Configuration_Dao_Bridge_Interface::class);
            $module_configuration = $module_configuration_bridge->get($module_id);
            if ($module_configuration->has_module_setting($config_name)) {
                $setting = $module_configuration->get_module_setting($config_name);
                $setting->set_value($prepared_config_value);
                $module_configuration_bridge->save($module_configuration);
                Container_Facade::dispatch(new Setting_Changed_Event($config_name, $shop_id, $module_id));
            } else {
                Registry::get_logger()->warning("Module \"{$module_id}\" setting \"{$config_name}\" is missing in metadata.php or configuration file.");
                Registry::get_config()->save_shop_conf_var($existing_config_type, $config_name, $prepared_config_value, $shop_id, $module);
            }
        } else {
            Registry::get_config()->save_shop_conf_var($existing_config_type, $config_name, $prepared_config_value, $shop_id, $module);
        }
    }
}