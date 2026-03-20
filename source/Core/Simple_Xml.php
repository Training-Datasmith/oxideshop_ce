<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Simple_Xml_Element;
/**
 * Parses objects to XML and XML to simple XML objects.
 *
 * Example object:
 * oxStdClass Object
 *   (
 *       [title] => TestTitle
 *       [keys] => oxStdClass Object
 *           (
 *               [key] => Array
 *                   (
 *                       [0] => testKey1
 *                       [1] => testKey2
 *                   )
 *           )
 *   )
 *
 * would produce the following XML:
 * <?xml version="1.0" encoding="utf-8"?>
 * <testXml><title>TestTitle</title><keys><key>testKey1</key><key>testKey2</key></keys></testXml>
 */
class Simple_Xml
{
    /**
     * Parses object structure to XML string
     *
     * @param object $oInput    Input object
     * @param string $sDocument Document name.
     *
     * @return string
     */
    public function object_to_xml($o_input, $s_document): string|false
    {
        $o_xml = new Simple_Xml_Element("<?xml version=\"1.0\" encoding=\"utf-8\"?><{$s_document}/>");
        $this->add_simple_xml_element($o_xml, $o_input);
        return $o_xml->as_xml();
    }
    /**
     * Parses XML string into object structure
     *
     * @param string $sXml XML Input
     *
     * @return SimpleXMLElement
     */
    public function xml_to_object($s_xml): \Simple_Xml_Element|false
    {
        return simplexml_load_string($s_xml);
    }
    /**
     * Recursively adds $oInput object data to SimpleXMLElement structure
     *
     * @param SimpleXMLElement    $oXml          Xml handler
     * @param string|array|object $oInput        Input object
     * @param string              $sPreferredKey Key to use instead of node's key.
     *
     * @return SimpleXMLElement
     */
    protected function add_simple_xml_element($o_xml, $o_input, $s_preferred_key = null)
    {
        $a_elements = is_object($o_input) ? get_object_vars($o_input) : (array) $o_input;
        foreach ($a_elements as $s_key => $m_element) {
            $o_xml = $this->add_child_node($o_xml, $s_key, $m_element, $s_preferred_key);
        }
        return $o_xml;
    }
    /**
     * Adds child node to given simple xml object.
     *
     * @param SimpleXMLElement    $oXml
     * @param string              $sKey
     * @param string|array|object $mElement
     * @param string              $sPreferredKey
     *
     * @return SimpleXMLElement
     */
    protected function add_child_node($o_xml, $s_key, $m_element, $s_preferred_key = null)
    {
        $a_attributes = [];
        if (is_array($m_element) && array_key_exists('attributes', $m_element) && is_array($m_element['attributes'])) {
            $a_attributes = $m_element['attributes'];
            $m_element = $m_element['value'];
        }
        if (is_object($m_element) || is_array($m_element)) {
            if (is_array($m_element) && is_int(key($m_element))) {
                $this->add_simple_xml_element($o_xml, $m_element, $s_key);
            } else {
                $o_child_node = $o_xml->add_child($s_preferred_key ?: $s_key);
                $this->add_node_attributes($o_child_node, $a_attributes);
                $this->add_simple_xml_element($o_child_node, $m_element);
            }
        } else {
            $o_child_node = $o_xml->add_child($s_preferred_key ?: $s_key);
            $o_child_node[0] = $m_element;
            // $oChildNode[0] is the inner text-node
            $this->add_node_attributes($o_child_node, $a_attributes);
        }
        return $o_xml;
    }
    /**
     * Adds attributes to given node.
     *
     * @param SimpleXMLElement $oNode
     * @param array            $aAttributes
     *
     * @return SimpleXMLElement
     */
    protected function add_node_attributes($o_node, $a_attributes)
    {
        $a_attributes = (array) $a_attributes;
        foreach ($a_attributes as $s_key => $s_value) {
            $o_node->add_attribute($s_key, $s_value);
        }
        return $o_node;
    }
}