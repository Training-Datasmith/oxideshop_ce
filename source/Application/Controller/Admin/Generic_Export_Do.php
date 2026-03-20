<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Templating\Template_Renderer_Interface;
/**
 * General export class.
 */
class Generic_Export_Do extends \Oxid_Esales\Eshop\Application\Controller\Admin\Dynamic_Export_Base_Controller
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = 'genExport_do';
    /**
     * Export ui class name
     *
     * @var string
     */
    public $s_class_main = 'genExport_main';
    /**
     * Export file name
     *
     * @var string
     */
    public $s_export_file_name = 'genexport';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'dynbase_do';
    /**
     * Does Export line by line on position iCnt
     *
     * @param integer $cnt export position
     *
     * @return bool
     */
    public function next_tick($cnt)
    {
        $exported_items = $cnt;
        $continue = false;
        if ($article = $this->get_one_article($cnt, $continue)) {
            $config = Registry::get_config();
            $article->long_description = $this->prepare_long_description($article);
            $context = ['sCustomHeader' => Registry::get_session()->get_variable('sExportCustomHeader'), 'linenr' => $cnt, 'article' => $article, 'spr' => $config->get_config_param('sCSVSign'), 'encl' => $config->get_config_param('sGiCsvFieldEncloser')];
            $context['oxEngineTemplateId'] = $this->get_view_id();
            $this->write($this->get_renderer()->render_template('genexport', $context));
            return ++$exported_items;
        }
        return $continue;
    }
    private function prepare_long_description(Article $article): string
    {
        if ($article->get_long_description() && $article->get_long_description()->get_raw_value()) {
            $active_language_id = Registry::get_lang()->get_tpl_language();
            $oxid = $article->get_id() . $article->get_language();
            return $this->get_renderer()->render_fragment($article->get_long_description()->get_raw_value(), "ox:{$oxid}{$active_language_id}", $this->get_view_data());
        }
        return '';
    }
    private function get_renderer(): Template_Renderer_Interface
    {
        return $this->get_service(Template_Renderer_Bridge_Interface::class)->get_template_renderer();
    }
    /**
     * writes one line into open export file
     *
     * @param string $sLine exported line
     */
    public function write($s_line): void
    {
        $s_line = $this->remove_sid($s_line);
        $s_line = str_replace(["\r\n", "\n"], '', $s_line);
        $s_line = str_replace('<br>', "\n", $s_line);
        fwrite($this->fp_file, $s_line . "\n");
    }
    /**
     * Current view ID getter helps to identify navigation position.
     * Bypassing dynexportbase::getViewId
     *
     * @return string
     */
    public function get_view_id()
    {
        return \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller::get_view_id();
    }
}