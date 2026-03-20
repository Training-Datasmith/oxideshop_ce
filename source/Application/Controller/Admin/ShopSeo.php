<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin shop system setting manager.
 * Collects shop system settings, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> System.
 */
class Shop_Seo extends \Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Configuration
{
    /**
     * Active seo url id
     */
    protected $_s_act_seo_object;
    /**
     * Executes parent method parent::render() and returns name of template
     * file "shop_system".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_a_view_data['subjlang'] = $this->_i_edit_lang;
        // loading shop
        $o_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        $o_shop->load_in_lang($this->_i_edit_lang, $this->_a_view_data['edit']->get_id());
        $this->_a_view_data['edit'] = $o_shop;
        // loading static seo urls
        $s_q = "select oxstdurl, oxobjectid from oxseo where oxtype='static' and oxshopid = :oxshopid" . ' group by oxobjectid order by oxstdurl';
        $o_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_list->init('oxbase', 'oxseo');
        $o_list->select_string($s_q, ['oxshopid' => $o_shop->get_id()]);
        $this->_a_view_data['aStaticUrls'] = $o_list;
        // loading active url info
        $this->load_active_url($o_shop->get_id());
        return 'shop_seo';
    }
    /**
     * Loads and sets active url info to view
     *
     * @param int $iShopId active shop id
     */
    protected function load_active_url($i_shop_id)
    {
        $s_act_object = null;
        if ($this->_s_act_seo_object) {
            $s_act_object = $this->_s_act_seo_object;
        } elseif (is_array($a_stat_url = Registry::get_request()->get_request_escaped_parameter('aStaticUrl'))) {
            $s_act_object = $a_stat_url['oxseo__oxobjectid'];
        }
        if ($s_act_object && $s_act_object != '-1') {
            $this->_a_view_data['sActSeoObject'] = $s_act_object;
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'select oxseourl, oxlang from oxseo where oxobjectid = :oxobjectid and oxshopid = :oxshopid';
            $o_rs = $o_db->select($s_q, ['oxobjectid' => $s_act_object, 'oxshopid' => $i_shop_id]);
            if ($o_rs != false && $o_rs->count() > 0) {
                while (!$o_rs->EOF) {
                    $a_seo_urls[$o_rs->fields['oxlang']] = [$s_act_object, $o_rs->fields['oxseourl']];
                    $o_rs->fetch_row();
                }
                $this->_a_view_data['aSeoUrls'] = $a_seo_urls;
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
        $o_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        if ($o_shop->load_in_lang($this->_i_edit_lang, $this->get_edit_object_id())) {
            //assigning values
            $o_shop->set_language(0);
            $o_shop->assign(Registry::get_request()->get_request_escaped_parameter('editval'));
            $o_shop->set_language($this->_i_edit_lang);
            $o_shop->save();
            // saving static url changes
            if (is_array($a_static_url = Registry::get_request()->get_request_escaped_parameter('aStaticUrl'))) {
                $this->_s_act_seo_object = Registry::get_seo_encoder()->encode_static_urls($this->process_urls($a_static_url), $o_shop->get_id(), $this->_i_edit_lang);
            }
        }
    }
    /**
     * Goes through urls array and prepares them for saving to db
     *
     * @param array $aUrls urls to process
     *
     * @return array
     */
    protected function process_urls($a_urls)
    {
        if (isset($a_urls['oxseo__oxstdurl']) && $a_urls['oxseo__oxstdurl']) {
            $a_urls['oxseo__oxstdurl'] = $this->cleanup_url($a_urls['oxseo__oxstdurl']);
        }
        if (isset($a_urls['oxseo__oxseourl']) && is_array($a_urls['oxseo__oxseourl'])) {
            foreach ($a_urls['oxseo__oxseourl'] as $i_pos => $s_url) {
                $a_urls['oxseo__oxseourl'][$i_pos] = $this->cleanup_url($s_url);
            }
        }
        return $a_urls;
    }
    /**
     * processes urls by fixing "&amp;", "&"
     *
     * @param string $sUrl processable url
     *
     * @return string
     */
    protected function cleanup_url($s_url)
    {
        // replacing &amp; to & or removing double &&
        while (stripos($s_url, '&amp;') !== false || stripos($s_url, '&&') !== false) {
            $s_url = str_replace('&amp;', '&', $s_url);
            $s_url = str_replace('&&', '&', $s_url);
        }
        // converting & to &amp;
        return str_replace('&', '&amp;', $s_url);
    }
    /**
     * Resetting SEO ids
     */
    public function drop_seo_ids(): void
    {
        $this->reset_seo_data(Registry::get_config()->get_shop_id());
    }
    /**
     * Deletes static url.
     */
    public function delete_static_url(): void
    {
        $a_static_url = Registry::get_request()->get_request_escaped_parameter('aStaticUrl');
        if (is_array($a_static_url)) {
            $s_objectid = $a_static_url['oxseo__oxobjectid'];
            if ($s_objectid && $s_objectid != '-1') {
                $this->delete_static_url_from_db($s_objectid);
            }
        }
    }
    /**
     * Deletes static url from DB.
     *
     * @param string $staticUrlId
     */
    protected function delete_static_url_from_db($static_url_id)
    {
        // active shop id
        $shop_id = $this->get_edit_object_id();
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $db->execute("delete from oxseo where oxtype='static' and oxobjectid = :oxobjectid and oxshopid = :oxshopid", ['oxobjectid' => $static_url_id, 'oxshopid' => $shop_id]);
    }
}