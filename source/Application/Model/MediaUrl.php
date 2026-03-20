<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Symfony\Component\Filesystem\Path;
class Media_Url extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    protected $_s_class_name = 'oxmediaurls';
    public function __construct()
    {
        parent::__construct();
        $this->init('oxmediaurls');
    }
    /**
     * Return HTML code depending on current URL
     *
     * @return string
     */
    public function get_html()
    {
        $s_url = $this->oxmediaurls__oxurl->value;
        //youtube link
        if (strpos((string) $s_url, 'youtube.com') || strpos((string) $s_url, 'youtu.be')) {
            return $this->get_youtube_html();
        }
        //simple link
        return $this->get_html_link();
    }
    /**
     * Returns simple HTML link
     *
     * @param bool $blNewPage Whether to open link in new window (adds target=_blank to link)
     *
     * @return string
     */
    public function get_html_link($bl_new_page = true)
    {
        $s_force_blank = $bl_new_page ? ' target="_blank"' : '';
        $s_desc = $this->oxmediaurls__oxdesc->value;
        $s_url = $this->get_link();
        return "<a href=\"{$s_url}\"{$s_force_blank}>{$s_desc}</a>";
    }
    public function get_link()
    {
        if ($this->oxmediaurls__oxisuploaded->value) {
            return Registry::get_config()->get_shop_url() . 'out/media/' . basename((string) $this->oxmediaurls__oxurl->value);
        }
        return $this->oxmediaurls__oxurl->value;
    }
    /**
     * Returns  object id
     *
     * @return string
     */
    public function get_object_id()
    {
        return $this->oxmediaurls__oxobjectid->value;
    }
    /**
     * Deletes record and unlinks the file
     *
     * @param string $sOXID Object ID(default null)
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        $s_file_path = Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'out', 'media', basename((string) $this->oxmediaurls__oxurl->value));
        if ($this->oxmediaurls__oxisuploaded->value) {
            if (file_exists($s_file_path)) {
                unlink($s_file_path);
            }
        }
        return parent::delete($s_oxid);
    }
    /**
     * @return string
     */
    protected function get_youtube_html()
    {
        $url = $this->oxmediaurls__oxurl->value;
        $you_tube_url = '';
        if (strpos((string) $url, 'youtube.com')) {
            $you_tube_url = str_replace('www.youtube.com/watch?v=', 'www.youtube.com/embed/', $url);
            $you_tube_url = preg_replace('/&/', '?', $you_tube_url, 1);
        }
        if (strpos((string) $url, 'youtu.be')) {
            $you_tube_url = str_replace('youtu.be/', 'www.youtube.com/embed/', $url);
        }
        return sprintf('%s<br><iframe width="425" height="344" src="%s" frameborder="0" allowfullscreen></iframe>', $this->oxmediaurls__oxdesc->value, $you_tube_url);
    }
}