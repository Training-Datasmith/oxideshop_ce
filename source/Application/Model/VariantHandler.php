<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service\Product_Variant_Media_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
/**
 * VariantHandler encapsulates methods dealing with multidimensional variant and variant names.
 */
class Variant_Handler extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Variant names
     *
     * @var array
     */
    protected $_o_articles;
    /**
     * Multidimensional variant separator
     *
     * @var string
     */
    protected $_s_md_separator = ' | ';
    /**
     * Multidimensional variant tree structure
     *
     * @var \OxidEsales\Eshop\Application\Model\MdVariant
     */
    protected $_o_md_variants;
    /**
     * Sets internal variant name array from article list.
     *
     * @param array $oArticles Variant list
     */
    public function init($o_articles): void
    {
        $this->_o_articles = $o_articles;
    }
    /**
     * Returns multidimensional variant structure
     *
     * @param object $oVariants all article variants
     * @param string $sParentId parent article id
     *
     * @return \OxidEsales\Eshop\Application\Model\MdVariant
     */
    public function build_md_variants($o_variants, $s_parent_id)
    {
        $o_md_variants = ox_new(\Oxid_Esales\Eshop\Application\Model\Md_Variant::class);
        $o_md_variants->set_parent_id($s_parent_id);
        $o_md_variants->set_name('_parent_product_');
        foreach ($o_variants as $s_key => $o_variant) {
            $a_names = explode(trim($this->_s_md_separator), (string) $o_variant->oxarticles__oxvarselect->value);
            foreach ($a_names as $s_name_key => $s_name) {
                $a_names[$s_name_key] = trim($s_name);
            }
            $o_md_variants->add_names($s_key, $a_names, \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('bl_perfLoadPrice') ? $o_variant->get_price()->get_price() : null, $o_variant->get_link());
        }
        return $o_md_variants;
    }
    /**
     * Generate variants from selection lists
     *
     * @param array  $aSels    ids of selection list
     * @param object $oArticle parent article
     */
    public function gen_variant_from_sell($a_sels, $o_article): void
    {
        $o_variants = $o_article->get_admin_variants();
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $my_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $a_conf_languages = $my_lang->get_language_ids();
        foreach ($a_sels as $s_sel_id) {
            $o_sel = ox_new(\Oxid_Esales\Eshop\Core\Model\Multi_Language_Model::class);
            $o_sel->set_enable_multilang(false);
            $o_sel->init('oxselectlist');
            $o_sel->load($s_sel_id);
            $s_var_name_update = '';
            foreach ($a_conf_languages as $s_key => $s_lang) {
                $s_prefix = $my_lang->get_language_tag($s_key);
                $a_sel_values = $my_utils->assign_values_from_text($o_sel->{'oxselectlist__oxvaldesc' . $s_prefix}->value);
                foreach ($a_sel_values as $s_i => $o_value) {
                    $a_values[$s_i][$s_key] = $o_value;
                }
                $a_sel_title[$s_key] = $o_sel->{'oxselectlist__oxtitle' . $s_prefix}->value;
                $s_md_separator = $o_article->oxarticles__oxvarname->value ? $this->_s_md_separator : '';
                if ($s_var_name_update) {
                    $s_var_name_update .= ', ';
                }
                $s_var_name = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote($s_md_separator . $a_sel_title[$s_key]);
                $s_var_name_update .= 'oxvarname' . $s_prefix . ' = CONCAT(oxvarname' . $s_prefix . ', ' . $s_var_name . ')';
            }
            $o_md_variants = $this->assign_values($a_values, $o_variants, $o_article, $a_conf_languages);
            if ($my_config->get_config_param('blUseMultidimensionVariants')) {
                $o_attribute = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
                $o_attribute->assign_var_to_attribute($o_md_variants, $a_sel_title);
            }
            $this->update_article_var_name($s_var_name_update, $o_article->oxarticles__oxid->value);
        }
    }
    /**
     * Assigns values of selection list to variants
     *
     * @param array  $aValues        multilang values of selection list
     * @param object $oVariants      variant list
     * @param object $oArticle       parent article
     * @param array  $aConfLanguages array of all active languages
     *
     * @return mixed
     */
    protected function assign_values($a_values, $o_variants, $o_article, $a_conf_languages)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $my_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        $i_counter = 0;
        $a_varselect = [];
        //multilanguage names of existing variants
        //iterating through all select list values (eg. $oValue->name = S, M, X, XL)
        for ($i = 0; $i < count($a_values); $i++) {
            $o_value = $a_values[$i][0];
            $d_price_mod = $this->get_value_price($o_value, $o_article->oxarticles__oxprice->value);
            if ($o_variants->count() > 0) {
                //if we have any existing variants then copying each variant with $oValue->name
                foreach ($o_variants as $o_simple_variant) {
                    if (!$i_counter) {
                        //we just update the first variant
                        $o_variant = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                        $o_variant->set_enable_multilang(false);
                        $o_variant->load($o_simple_variant->oxarticles__oxid->value);
                        $o_variant->oxarticles__oxprice->set_value($o_variant->oxarticles__oxprice->value + $d_price_mod);
                        //assign for all languages
                        foreach ($a_conf_languages as $s_key => $s_lang) {
                            $o_value = $a_values[$i][$s_key];
                            $s_prefix = $my_lang->get_language_tag($s_key);
                            $a_varselect[$o_simple_variant->oxarticles__oxid->value][$s_key] = $o_variant->{'oxarticles__oxvarselect' . $s_prefix}->value;
                            $o_variant->{'oxarticles__oxvarselect' . $s_prefix}->set_value($o_variant->{'oxarticles__oxvarselect' . $s_prefix}->value . $this->_s_md_separator . $o_value->name);
                        }
                        $o_variant->oxarticles__oxsort->set_value($o_variant->oxarticles__oxsort->value * 10);
                        $o_variant->save();
                        $s_var_id = $o_simple_variant->oxarticles__oxid->value;
                    } else {
                        //we create new variants
                        foreach ($a_varselect[$o_simple_variant->oxarticles__oxid->value] as $s_key => $s_varselect) {
                            $o_value = $a_values[$i][$s_key];
                            $s_prefix = $my_lang->get_language_tag($s_key);
                            $a_params['oxarticles__oxvarselect' . $s_prefix] = $s_varselect . $this->_s_md_separator . $o_value->name;
                        }
                        $a_params['oxarticles__oxartnum'] = $o_simple_variant->oxarticles__oxartnum->value . '-' . $i_counter;
                        $a_params['oxarticles__oxprice'] = $o_simple_variant->oxarticles__oxprice->value + $d_price_mod;
                        $a_params['oxarticles__oxsort'] = $o_simple_variant->oxarticles__oxsort->value * 10 + 10 * $i_counter;
                        $a_params['oxarticles__oxstock'] = 0;
                        $a_params['oxarticles__oxstockflag'] = $o_simple_variant->oxarticles__oxstockflag->value;
                        $a_params['oxarticles__oxisconfigurable'] = $o_simple_variant->oxarticles__oxisconfigurable->value;
                        $s_var_id = $this->create_new_variant($a_params, $o_article->oxarticles__oxid->value);
                        if ($my_config->get_config_param('blUseMultidimensionVariants')) {
                            $o_attr_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
                            $a_ids = $o_attr_list->get_attribute_assigns($o_simple_variant->oxarticles__oxid->value);
                            $a_md_variants['mdvar_' . $s_var_id] = $a_ids;
                        }
                    }
                    if ($my_config->get_config_param('blUseMultidimensionVariants')) {
                        $a_md_variants[$s_var_id] = $a_values[$i];
                    }
                }
                $i_counter++;
            } else {
                //in case we don't have any variants then we just create variant(s) with $oValue->name
                $i_counter++;
                foreach ($a_conf_languages as $s_key => $s_lang) {
                    $o_value = $a_values[$i][$s_key];
                    $s_prefix = $my_lang->get_language_tag($s_key);
                    $a_params['oxarticles__oxvarselect' . $s_prefix] = $o_value->name;
                }
                $a_params['oxarticles__oxartnum'] = $o_article->oxarticles__oxartnum->value . '-' . $i_counter;
                $a_params['oxarticles__oxprice'] = $o_article->oxarticles__oxprice->value + $d_price_mod;
                $a_params['oxarticles__oxsort'] = $i_counter * 100;
                // reduction
                $a_params['oxarticles__oxstock'] = 0;
                $a_params['oxarticles__oxstockflag'] = $o_article->oxarticles__oxstockflag->value;
                $a_params['oxarticles__oxisconfigurable'] = $o_article->oxarticles__oxisconfigurable->value;
                $s_var_id = $this->create_new_variant($a_params, $o_article->oxarticles__oxid->value);
                if ($my_config->get_config_param('blUseMultidimensionVariants')) {
                    $a_md_variants[$s_var_id] = $a_values[$i];
                }
            }
        }
        return $a_md_variants;
    }
    /**
     * Returns article price
     *
     * @param object $oValue       selection list value
     * @param double $dParentPrice parent article price
     *
     * @return double
     */
    protected function get_value_price($o_value, $d_parent_price)
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $d_price_mod = 0;
        if ($my_config->get_config_param('bl_perfLoadSelectLists') && $my_config->get_config_param('bl_perfUseSelectlistPrice')) {
            if ($o_value->price_unit == 'abs') {
                $d_price_mod = $o_value->price;
            } elseif ($o_value->price_unit == '%') {
                $d_price_mod_perc = abs($o_value->price) * $d_parent_price / 100.0;
                if ($o_value->price >= 0.0) {
                    $d_price_mod = $d_price_mod_perc;
                } else {
                    $d_price_mod = -$d_price_mod_perc;
                }
            }
        }
        return $d_price_mod;
    }
    /**
     * Creates new article variant.
     *
     * @param array  $aParams   assigned parameters
     * @param string $sParentId parent article id
     */
    protected function create_new_variant($a_params = null, $s_parent_id = null)
    {
        // checkbox handling
        $a_params['oxarticles__oxactive'] = 0;
        // shopid
        $s_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_session()->get_variable('actshop');
        $a_params['oxarticles__oxshopid'] = $s_shop_id;
        // varianthandling
        $a_params['oxarticles__oxparentid'] = $s_parent_id;
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->set_enable_multilang(false);
        $o_article->assign($a_params);
        $o_article->save();
        $variant_id = $o_article->get_id();
        $this->copy_media_from_parent($s_parent_id, $variant_id);
        return $variant_id;
    }
    private function copy_media_from_parent(string $parent_id, string $variant_id): void
    {
        Container_Facade::get(Product_Variant_Media_Service_Interface::class)->assign_from_parent_to_variant(Id::from_string($parent_id), Id::from_string($variant_id));
    }
    /**
     * Inserts article variant name for all languages
     *
     * @param string $sUpdate query for update variant name
     * @param string $sArtId  parent article id
     */
    protected function update_article_var_name($s_update, $s_art_id)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_update = 'update oxarticles set ' . $s_update . ' where oxid = :oxid';
        $o_db->execute($s_update, ['oxid' => $s_art_id]);
    }
    /**
     * Check if variant is multidimensional
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article object
     *
     * @return bool
     */
    public function is_md_variant($o_article)
    {
        if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blUseMultidimensionVariants')) {
            return false;
        }
        if (!is_object($o_article)) {
            return false;
        }
        if (!isset($o_article->oxarticles__oxvarselect)) {
            return false;
        }
        if (str_contains((string) $o_article->oxarticles__oxvarselect->value, trim($this->_s_md_separator))) {
            return true;
        }
        return false;
    }
    /**
     * Creates array/matrix with variant selections
     *
     * @param \OxidEsales\Eshop\Application\Model\ArticleList $oVariantList  variant list
     * @param int                                             $iVarSelCnt    possible variant selection count
     * @param array                                           $aFilter       active filter array
     * @param string                                          $sActVariantId active variant id
     *
     * @return array
     */
    protected function fill_variant_selections($o_variant_list, $i_var_sel_cnt, &$a_filter, $s_act_variant_id)
    {
        $a_selections = [];
        // filling selections
        foreach ($o_variant_list as $o_variant) {
            $a_names = $this->get_selections($o_variant->oxarticles__oxvarselect->get_raw_value());
            $bl_active = $s_act_variant_id === $o_variant->get_id() ? true : false;
            for ($i = 0; $i < $i_var_sel_cnt; $i++) {
                $s_name = isset($a_names[$i]) ? trim($a_names[$i]) : false;
                if ($s_name !== '' && $s_name !== false) {
                    $s_hash = md5($s_name);
                    // filling up filter
                    if ($bl_active) {
                        $a_filter[$i] = $s_hash;
                    }
                    $a_selections[$o_variant->get_id()][$i] = ['name' => $s_name, 'disabled' => null, 'active' => false, 'hash' => $s_hash];
                }
            }
        }
        return $a_selections;
    }
    /**
     * Cleans up user given filter. If filter was empty - returns false
     *
     * @param array $aFilter user given filter
     *
     * @return array|bool
     */
    protected function clean_filter($a_filter)
    {
        $a_clean_filter = false;
        if (is_array($a_filter) && count($a_filter)) {
            foreach ($a_filter as $i_key => $s_filter) {
                if ($s_filter) {
                    $a_clean_filter[$i_key] = $s_filter;
                }
            }
        }
        return $a_clean_filter;
    }
    /**
     * Applies filter on variant selection array
     *
     * @param array $aSelections selections
     * @param array $aFilter     filter
     *
     * @return array
     */
    protected function apply_variant_selections_filter($a_selections, $a_filter)
    {
        $i_max_active_count = 0;
        $s_most_suitable_variant_id = null;
        $bl_perfect_fit = false;
        // applying filters, disabling/activating items
        if ($a_filter = $this->clean_filter($a_filter)) {
            $a_filter_keys = array_keys($a_filter);
            $i_filter_keys_count = count($a_filter);
            foreach ($a_selections as $s_variant_id => &$a_line_selections) {
                $i_active = 0;
                foreach ($a_filter as $i_key => $s_val) {
                    if (strcmp((string) $a_line_selections[$i_key]['hash'], (string) $s_val) === 0) {
                        $a_line_selections[$i_key]['active'] = true;
                        $i_active++;
                    } else {
                        foreach ($a_line_selections as $i_other_key => &$a_line_other_variant) {
                            if ($i_key != $i_other_key) {
                                $a_line_other_variant['disabled'] = true;
                            }
                        }
                    }
                }
                foreach ($a_line_selections as $i_other_key => &$a_line_other_variant) {
                    if (!in_array($i_other_key, $a_filter_keys)) {
                        $a_line_other_variant['disabled'] = !($i_filter_keys_count == $i_active);
                    }
                }
                $bl_fits_all = $i_active && count($a_line_selections) == $i_active && $i_filter_keys_count == $i_active;
                if ($i_active > $i_max_active_count || !$bl_perfect_fit && $bl_fits_all) {
                    $bl_perfect_fit = $bl_fits_all;
                    $s_most_suitable_variant_id = $s_variant_id;
                    $i_max_active_count = $i_active;
                }
                unset($a_line_selections);
            }
        }
        return [$a_selections, $s_most_suitable_variant_id, $bl_perfect_fit];
    }
    /**
     * Builds variant selections list - array containing oxVariantSelectList
     *
     * @param array $aVarSelects variant selection titles
     * @param array $aSelections variant selections
     *
     * @return array
     */
    protected function build_variant_selections_list($a_var_selects, $a_selections)
    {
        // creating selection lists
        foreach ($a_var_selects as $i_key => $s_label) {
            $a_variant_selections[$i_key] = ox_new(\Oxid_Esales\Eshop\Application\Model\Variant_Select_List::class, $s_label, $i_key);
        }
        // building variant selections
        foreach ($a_selections as $a_line_selections) {
            foreach ($a_line_selections as $o_pos => $a_line) {
                $a_variant_selections[$o_pos]->add_variant($a_line['name'], $a_line['hash'], $a_line['disabled'], $a_line['active']);
            }
        }
        return $a_variant_selections;
    }
    /**
     * In case multidimentional variants ON explodes title by _sMdSeparator
     * and returns array, else - returns array containing title
     *
     * @param string $sTitle title to process
     *
     * @return array
     */
    protected function get_selections($s_title)
    {
        if (\Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('blUseMultidimensionVariants')) {
            return explode($this->_s_md_separator, $s_title);
        }
        return [$s_title];
    }
    /**
     * Builds variant selection list
     *
     * @param string                                          $sVarName      product (parent product) oxvarname value
     * @param \OxidEsales\Eshop\Application\Model\ArticleList $oVariantList  variant list
     * @param array                                           $aFilter       variant filter
     * @param string                                          $sActVariantId active variant id
     * @param int                                             $iLimit        limit variant lists count (if non zero, return limited number of multidimensional variant selections)
     *
     * @return array|false
     */
    public function build_variant_selections($s_var_name, $o_variant_list, $a_filter, $s_act_variant_id, $i_limit = 0)
    {
        // assigning variants
        $a_var_selects = $this->get_selections($s_var_name);
        if ($i_limit) {
            $a_var_selects = array_slice($a_var_selects, 0, $i_limit);
        }
        if ($i_var_sel_cnt = count($a_var_selects)) {
            // filling selections
            $a_raw_variant_selections = $this->fill_variant_selections($o_variant_list, $i_var_sel_cnt, $a_filter, $s_act_variant_id);
            // applying filters, disabling/activating items
            [$a_raw_variant_selections, $s_act_variant_id, $bl_perfect_fit] = $this->apply_variant_selections_filter($a_raw_variant_selections, $a_filter);
            // creating selection lists
            $a_variant_selections = $this->build_variant_selections_list($a_var_selects, $a_raw_variant_selections);
            $o_current_variant = null;
            if ($s_act_variant_id) {
                $o_current_variant = $o_variant_list[$s_act_variant_id];
            }
            return ['selections' => $a_variant_selections, 'rawselections' => $a_raw_variant_selections, 'oActiveVariant' => $o_current_variant, 'blPerfectFit' => $bl_perfect_fit];
        }
        return false;
    }
}