<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Dom_Document;
use Dom_Element;
use Domx_Path;
use Oxid_Esales\Eshop\Core\Base;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use stdClass;
use Symfony\Contracts\Cache\Item_Interface;
use Symfony\Contracts\Cache\Tag_Aware_Cache_Interface;
class Navigation_Tree extends Base
{
    /**
     * stores DOM object for all navigation tree
     */
    protected $_o_dom;
    /**
     * keeps unmodified dom
     */
    protected $_o_initial_dom;
    /**
     * Default EXPATH supported encodings
     *
     * @var array
     */
    protected $_a_supported_expath_xml_encodings = ['utf-8', 'utf-16', 'iso-8859-1', 'us-ascii'];
    /**
     * clean empty nodes from tree
     *
     * @param object $dom         dom object
     * @param string $parentXPath parent xpath
     * @param string $childXPath  child xpath from parent
     */
    protected function clean_empty_parents($dom, $parent_x_path, $child_x_path)
    {
        $x_path = new Dom_X_Path($dom);
        $node_list = $x_path->query($parent_x_path);
        foreach ($node_list as $node) {
            $id = $node->get_attribute('id');
            $child_list = $x_path->query("{$parent_x_path}[@id='{$id}']/{$child_x_path}");
            if (!$child_list->length) {
                $node->parent_node->remove_child($node);
            }
        }
    }
    /**
     * Adds links to xml nodes to resolve paths
     *
     * @param DomDocument $dom where to add links
     */
    protected function add_links($dom)
    {
        $url = 'index.php?';
        // session parameters will be included later (after cache processor)
        $x_path = new Dom_X_Path($dom);
        // building
        $node_list = $x_path->query('//SUBMENU[@cl]');
        foreach ($node_list as $node) {
            // fetching class
            $cl = $node->get_attribute('cl');
            $cl = $cl ? "cl={$cl}" : '';
            // fetching params
            $param = $node->get_attribute('clparam');
            $param = $param ? "&{$param}" : '';
            // setting link
            $node->set_attribute('link', "{$url}{$cl}{$param}");
        }
    }
    /**
     * Loads data form XML file, and merges it with main oDomXML.
     *
     * @param string      $menuFile which file to load
     * @param DomDocument $dom      where to load
     */
    protected function load_from_file($menu_file, $dom)
    {
        $merge = false;
        $dom_file = new Dom_Document();
        $dom_file->preserve_white_space = false;
        if (!@$dom_file->load($menu_file)) {
            $merge = true;
        } elseif (is_readable($menu_file) && $xml = @file_get_contents($menu_file)) {
            // looking for non supported character encoding
            if (Str::get_str()->preg_match("/encoding\\=(.*)\\?\\>/", $xml, $matches) !== 0) {
                if (isset($matches[1])) {
                    $curr_encoding = trim((string) $matches[1], '"');
                    if (!in_array(strtolower($curr_encoding), $this->_a_supported_expath_xml_encodings)) {
                        $xml = str_replace($matches[1], '"UTF-8"', $xml);
                        $xml = iconv($curr_encoding, 'UTF-8', $xml);
                    }
                }
            }
            // load XML as string
            if (@$dom_file->load_xml($xml)) {
                $merge = true;
            }
        }
        if ($merge) {
            $this->merge($dom_file, $dom);
        }
    }
    /**
     * add session parameters to local urls
     *
     * @param object $dom dom element to add links
     */
    protected function sessionize_local_urls($dom)
    {
        $url = $this->get_admin_url();
        $x_path = new Dom_X_Path($dom);
        $str = Str::get_str();
        foreach (['url', 'link'] as $attr_type) {
            foreach ($x_path->query("//OXMENU//*[@{$attr_type}]") as $node) {
                $local_url = $node->get_attribute($attr_type);
                if (str_starts_with((string) $local_url, 'index.php?')) {
                    $local_url = $str->preg_replace('#^index.php\?#', $url, $local_url);
                    $node->set_attribute($attr_type, $local_url);
                }
            }
        }
    }
    /**
     * Removes form tree elements which does not have required user rights
     *
     * @param object $dom DOMDocument
     */
    protected function check_rights($dom)
    {
        $x_path = new Dom_X_Path($dom);
        $node_list = $x_path->query('//*[@rights or @norights]');
        foreach ($node_list as $node) {
            // only allowed modules/user rights or so
            if ($req = $node->get_attribute('rights')) {
                $perms = explode(',', (string) $req);
                foreach ($perms as $perm) {
                    if ($perm && !$this->has_rights($perm)) {
                        $node->parent_node->remove_child($node);
                    }
                }
                // not allowed modules/user rights or so
            } elseif ($no_req = $node->get_attribute('norights')) {
                $perms = explode(',', (string) $no_req);
                foreach ($perms as $perm) {
                    if ($perm && $this->has_rights($perm)) {
                        $node->parent_node->remove_child($node);
                    }
                }
            }
        }
    }
    /**
     * Removes from tree elements which don't have required groups
     *
     * @param DOMDocument $dom document to check group
     */
    protected function check_groups($dom)
    {
        $x_path = new Dom_X_Path($dom);
        $node_list = $x_path->query('//*[@nogroup or @group]');
        foreach ($node_list as $node) {
            // allowed only for groups
            if ($req = $node->get_attribute('group')) {
                $perms = explode(',', (string) $req);
                foreach ($perms as $perm) {
                    if ($perm && !$this->has_group($perm)) {
                        $node->parent_node->remove_child($node);
                    }
                }
                // not allowed for groups
            } elseif ($no_req = $node->get_attribute('nogroup')) {
                $perms = explode(',', (string) $no_req);
                foreach ($perms as $perm) {
                    if ($perm && $this->has_group($perm)) {
                        $node->parent_node->remove_child($node);
                    }
                }
            }
        }
    }
    /**
     * Removes form tree elements if this is demo shop and elements have disableForDemoShop="1"
     *
     * @param DOMDocument $dom document to check group
     */
    protected function check_demo_shop_denials($dom)
    {
        if (!\Oxid_Esales\Eshop\Core\Registry::get_config()->is_demo_shop()) {
            // nothing to check for non demo shop
            return;
        }
        $x_path = new Dom_X_Path($dom);
        $node_list = $x_path->query('//*[@disableForDemoShop]');
        foreach ($node_list as $node) {
            if ($node->get_attribute('disableForDemoShop')) {
                $node->parent_node->remove_child($node);
            }
        }
    }
    /**
     * Removes node from tree elements if it is marked as not visible (visible="0")
     *
     * @param DOMDocument $dom document to check group
     */
    protected function remove_invisible_menu_nodes($dom)
    {
        $x_path = new Dom_X_Path($dom);
        $node_list = $x_path->query('//*[@visible]');
        foreach ($node_list as $node) {
            if (!$node->get_attribute('visible')) {
                $node->parent_node->remove_child($node);
            }
        }
    }
    /**
     * Copys attributes form one element to another
     *
     * @param object $domElemTo   DOMElement
     * @param object $domElemFrom DOMElement
     */
    protected function copy_attributes($dom_elem_to, $dom_elem_from)
    {
        foreach ($dom_elem_from->attributes as $attr) {
            $dom_elem_to->set_attribute($attr->node_name, $attr->node_value);
        }
    }
    /**
     * Merges nodes of newly added menu xml file
     *
     * @param object $domElemTo   merge target
     * @param object $domElemFrom merge source
     * @param object $xPathTo     node path
     * @param object $domDocTo    node to append child
     * @param string $queryStart  node query
     */
    protected function merge_nodes($dom_elem_to, $dom_elem_from, $x_path_to, $dom_doc_to, $query_start)
    {
        foreach ($dom_elem_from->child_nodes as $from_node) {
            if ($from_node->node_type === XML_ELEMENT_NODE) {
                $from_attr_name = $from_node->get_attribute('id');
                $from_node_name = $from_node->tag_name;
                // find current item
                $query = "{$query_start}/{$from_node_name}[@id='{$from_attr_name}']";
                $cur_node = $x_path_to->query($query);
                // if not found - append
                if ($cur_node->length == 0) {
                    $dom_elem_to->append_child($dom_doc_to->import_node($from_node, true));
                } else {
                    $cur_node = $cur_node->item(0);
                    // if found copy all attributes and check childnodes
                    $this->copy_attributes($cur_node, $from_node);
                    if ($from_node->child_nodes->length) {
                        $this->merge_nodes($cur_node, $from_node, $x_path_to, $dom_doc_to, $query);
                    }
                }
            }
        }
    }
    /**
     * If oDomXML exist meges nodes
     *
     * @param DomDocument $domNew what to merge
     * @param DomDocument $dom    where to merge
     */
    protected function merge($dom_new, $dom)
    {
        $x_path = new Domx_Path($dom);
        $this->merge_nodes($dom->document_element, $dom_new->document_element, $x_path, $dom, '/OX');
    }
    /**
     * Returns from oDomXML tree tabs DOMNodeList, which belongs to $id
     *
     * @param string $id        class name
     * @param int    $act       current tab number
     * @param bool   $setActive marks tab as active
     *
     * @return \DOMNodeList
     */
    public function get_tabs($id, $act, $set_active = true)
    {
        $x_path = new Domx_Path($this->get_dom_xml());
        //$nodeList = $xPath->query( "//SUBMENU[@cl='$id' or @list='$id']/TAB | //SUBMENU/../TAB[@cl='$id']" );
        $node_list = $x_path->query("//SUBMENU[@cl='{$id}']/TAB | //SUBMENU[@list='{$id}']/TAB | //SUBMENU/../TAB[@cl='{$id}']");
        $act = $act > $node_list->length ? $node_list->length - 1 : $act;
        if ($set_active) {
            foreach ($node_list as $pos => $node) {
                if ($pos == $act) {
                    // marking active node
                    $node->set_attribute('active', 1);
                }
            }
        }
        return $node_list;
    }
    /**
     * Returns active TAB class name
     *
     * @param string $id  class name
     * @param int    $act active tab number
     *
     * @return string
     */
    public function get_active_tab($id, $act)
    {
        $node_list = $this->get_tabs($id, $act, false);
        $act = $act > $node_list->length ? $node_list->length - 1 : $act;
        if ($node_list->length && $node = $node_list->item($act)) {
            return $node->get_attribute('cl');
        }
    }
    /**
     * returns from oDomXML tree buttons stdClass, which belongs to $class
     *
     * @param string $class class name
     *
     * @return mixed
     */
    public function get_btn($class)
    {
        $buttons = null;
        $x_path = new Domx_Path($this->get_dom_xml());
        $node_list = $x_path->query("//TAB[@cl='{$class}']/../BTN");
        if ($node_list->length) {
            $buttons = new stdClass();
            foreach ($node_list as $node) {
                $btn_id = $node->get_attribute('id');
                $buttons->{$btn_id} = 1;
            }
        }
        return $buttons;
    }
    /**
     * Returns array with paths + names ox menu xml files. Paths are checked
     *
     * @return array
     */
    protected function get_menu_files()
    {
        return Container_Facade::get('oxid_esales.templating.admin.navigation.file.locator')->locate();
    }
    /**
     * Method is used for overriding.
     *
     * @param string $cacheContents
     *
     * @return string
     */
    protected function process_cached_file($cache_contents)
    {
        return $cache_contents;
    }
    protected function get_initial_dom()
    {
        if ($this->_o_initial_dom !== null) {
            return $this->_o_initial_dom;
        }
        $files_to_load = $this->get_menu_files();
        if (!is_array($files_to_load)) {
            return null;
        }
        $template_language_code = $this->get_template_language_code();
        $cache_name = 'shop_menu_cache_' . $template_language_code;
        $cache = Container_Facade::get(Tag_Aware_Cache_Interface::class);
        if ($this->is_menu_cache_outdated($cache, $cache_name, $files_to_load)) {
            $cache->delete($cache_name);
        }
        $cache_contents = $cache->get($cache_name, function (Item_Interface $item) use ($files_to_load): array {
            $item->tag('oxid_esales.cache.menu');
            return ['creation_time' => time(), 'menu_dom' => $this->generate_initial_menu_dom_xml($files_to_load)];
        });
        $this->_o_initial_dom = new Dom_Document();
        $this->_o_initial_dom->preserve_white_space = false;
        $this->_o_initial_dom->load_xml($cache_contents['menu_dom']);
        $this->sessionize_local_urls($this->_o_initial_dom);
        return $this->_o_initial_dom;
    }
    /**
     * Returns DomXML
     *
     * @return DOMDocument
     */
    public function get_dom_xml()
    {
        if ($this->_o_dom === null) {
            $this->_o_dom = clone $this->get_initial_dom();
            // removes items denied by user group
            $this->check_groups($this->_o_dom);
            // removes items denied by user rights
            $this->check_rights($this->_o_dom);
            // removes items marked as not visible
            $this->remove_invisible_menu_nodes($this->_o_dom);
            // check config params
            $this->check_demo_shop_denials($this->_o_dom);
            $this->on_getting_dom_xml();
            $this->clean_empty_parents($this->_o_dom, '//SUBMENU[@id][@list]', 'TAB');
            $this->clean_empty_parents($this->_o_dom, '//MAINMENU[@id]', 'SUBMENU');
        }
        return $this->_o_dom;
    }
    /**
     * Returns DOMNodeList of given navigation classes
     *
     * @param array $nodes Node array
     *
     * @return \DOMNodeList
     */
    public function get_list_nodes($nodes)
    {
        $x_path = new Domx_Path($this->get_dom_xml());
        $node_list = $x_path->query("//SUBMENU[@cl='" . implode("' or @cl='", $nodes) . "']");
        return $node_list->length ? $node_list : null;
    }
    /**
     * Marks passed node as active
     *
     * @param string $nodeId node id
     */
    public function mark_node_active($node_id): void
    {
        $x_path = new Domx_Path($this->get_dom_xml());
        $node_list = $x_path->query("//*[@cl='{$node_id}' or @list='{$node_id}']");
        if ($node_list->length) {
            foreach ($node_list as $node) {
                // special case for external resources
                $node->set_attribute('active', 1);
                $node->parent_node->set_attribute('active', 1);
            }
        }
    }
    /**
     * Formats and returns url for list area
     *
     * @param string $id tab related class
     *
     * @return string
     */
    public function get_list_url($id)
    {
        $x_path = new Domx_Path($this->get_dom_xml());
        $node_list = $x_path->query("//SUBMENU[@cl='{$id}']");
        if ($node_list->length && $node = $node_list->item(0)) {
            $cl = $node->get_attribute('list');
            $cl = $cl ? "cl={$cl}" : '';
            $params = $node->get_attribute('listparam');
            $params = $params ? "&{$params}" : '';
            return "{$cl}{$params}";
        }
    }
    /**
     * Formats and returns url for edit area
     *
     * @param string $id     tab related class
     * @param int    $actTab active tab
     *
     * @return string
     */
    public function get_edit_url($id, $act_tab)
    {
        $x_path = new Domx_Path($this->get_dom_xml());
        $node_list = $x_path->query("//SUBMENU[@cl='{$id}']/TAB");
        $act_tab = $act_tab > $node_list->length ? $node_list->length - 1 : $act_tab;
        if ($node_list->length && $act_tab = $node_list->item($act_tab)) {
            // special case for external resources
            if ($act_tab->get_attribute('external')) {
                return $act_tab->get_attribute('location');
            }
            $cl = $act_tab->get_attribute('cl');
            $cl = $cl ? "cl={$cl}" : '';
            $params = $act_tab->get_attribute('clparam');
            $params = $params ? "&{$params}" : '';
            return "{$cl}{$params}";
        }
    }
    /**
     * Admin url getter
     *
     * @return string
     */
    protected function get_admin_url()
    {
        \Oxid_Esales\Eshop\Core\Registry::get_config();
        if ($admin_url = Container_Facade::get_parameter('oxid_esales.shop_admin_url')) {
            $url = trim((string) $admin_url, '/');
        } else {
            $url = trim((string) Container_Facade::get_parameter('oxid_esales.shop_url'), '/') . '/admin';
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->process_url("{$url}/index.php", false);
    }
    /**
     * Checks if user has required rights
     *
     * @param string $rights session user rights
     *
     * @return bool
     */
    protected function has_rights($rights)
    {
        return $this->get_user()->oxuser__oxrights->value == $rights;
    }
    /**
     * Checks if user in required group
     *
     * @param string $groupId active group id
     *
     * @return bool
     */
    protected function has_group($group_id)
    {
        return $this->get_user()->in_group($group_id);
    }
    /**
     * Returns id of class assigned to current node
     *
     * @param string $className active class name
     *
     * @return string
     */
    public function get_class_id($class_name)
    {
        $x_path = new Domx_Path($this->get_initial_dom());
        $node_list = $x_path->query("//*[@cl='{$class_name}' or @list='{$class_name}']");
        if ($node_list->length && $first_item = $node_list->item(0)) {
            return $first_item->get_attribute('id');
        }
    }
    /**
     * Get template language code
     *
     * @return string
     */
    protected function get_template_language_code()
    {
        $language = \Oxid_Esales\Eshop\Core\Registry::get_lang();
        return $language->get_language_array()[$language->get_tpl_language()]->abbr;
    }
    /**
     * Method is used for overriding.
     */
    protected function on_getting_dom_xml()
    {
    }
    private function is_menu_cache_outdated(object $cache, string $cache_name, array $files_to_load): bool
    {
        $cache_item = $cache->get_item($cache_name);
        if (!$cache_item->is_hit()) {
            return true;
        }
        $cache_creation_time = $cache_item->get()['creation_time'];
        foreach ($files_to_load as $file_path) {
            if ($cache_creation_time < filemtime($file_path)) {
                return true;
            }
        }
        return false;
    }
    private function generate_initial_menu_dom_xml(array $files_to_load): string
    {
        $initial_dom = new Dom_Document();
        $initial_dom->append_child(new Dom_Element('OX'));
        foreach ($files_to_load as $file_path) {
            $this->load_from_file($file_path, $initial_dom);
        }
        $this->add_links($initial_dom);
        return $initial_dom->save_xml();
    }
}