<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use stdClass;
use Symfony\Component\Filesystem\Path;
/**
 * Error constants
 */
DEFINE('ERR_SUCCESS', -2);
DEFINE('ERR_GENERAL', -1);
DEFINE('ERR_FILEIO', 1);
/**
 * DynExportBase framework class encapsulating a method for defining implementation class.
 * Performs export function according to user chosen categories.
 *
 * @subpackage dyn
 */
class Dynamic_Export_Base_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Export class name
     *
     * @var string
     */
    public $s_class_do = '';
    /**
     * Export ui class name
     *
     * @var string
     */
    public $s_class_main = '';
    /**
     * Export output folder
     *
     * @var string
     */
    public $s_export_path = 'export/';
    /**
     * Export file extension
     *
     * @var string
     */
    public $s_export_file_type = 'txt';
    /**
     * Export file name
     *
     * @var string
     */
    public $s_export_file_name = 'dynexport';
    /**
     * Export file resource
     *
     * @var object
     */
    public $fp_file;
    /**
     * Default number of records to export per tick
     * Used if not set in config
     *
     * @var int
     */
    public $i_export_per_tick = 30;
    /**
     * Number of records to export per tick
     *
     * @var int
     */
    protected $_i_export_per_tick;
    /**
     * Full export file path
     */
    protected string $_s_file_path;
    /**
     * Export result set
     *
     * @var array
     */
    protected $_a_export_resultset = [];
    /**
     * View template name
     *
     * @var string
     */
    protected $_s_this_template = 'dynexportbase';
    /**
     * Category data cache
     *
     * @var array
     */
    protected $_a_cat_lvl_cache;
    /**
     * Calls parent costructor and initializes $this->_sFilePath parameter
     */
    public function __construct()
    {
        parent::__construct();
        // set generic frame template
        $this->_s_file_path = Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), $this->s_export_path, "{$this->s_export_file_name}.{$this->s_export_file_type}");
    }
    /**
     * Calls parent rendering methods, sends implementation class names to template
     * and returns default template name
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        // assign all member variables to template
        $a_class_vars = get_object_vars($this);
        foreach ($a_class_vars as $name => $value) {
            $this->_a_view_data[$name] = $value;
        }
        $this->_a_view_data['sOutputFile'] = $this->_s_file_path;
        $this->_a_view_data['sDownloadFile'] = Path::join(Container_Facade::get_parameter('oxid_esales.shop_url'), $this->s_export_path, "{$this->s_export_file_name}.{$this->s_export_file_type}");
        return $this->_s_this_template;
    }
    /**
     * Prepares and fill all data which all the dyn exports needs
     */
    public function create_main_export_view(): void
    {
        // parent categorie tree
        $this->_a_view_data['cattree'] = ox_new(\Oxid_Esales\Eshop\Application\Model\Category_List::class);
        $this->_a_view_data['cattree']->load_list();
        $o_lang_obj = ox_new(\Oxid_Esales\Eshop\Core\Language::class);
        $a_langs = $o_lang_obj->get_language_array();
        foreach ($a_langs as $id => $language) {
            $language->selected = $id == $this->_i_edit_lang;
            $this->_a_view_data['aLangs'][$id] = clone $language;
        }
    }
    /**
     * Prepares Export
     */
    public function start(): void
    {
        // delete file, if its already there
        $this->fp_file = @fopen($this->_s_file_path, 'w');
        if (!isset($this->fp_file) || !$this->fp_file) {
            // we do have an error !
            $this->stop(ERR_FILEIO);
        } else {
            $this->_a_view_data['refresh'] = 0;
            $this->_a_view_data['iStart'] = 0;
            fclose($this->fp_file);
            // prepare it
            $i_end = $this->prepare_export();
            Registry::get_session()->set_variable('iEnd', $i_end);
            $this->_a_view_data['iEnd'] = $i_end;
        }
    }
    /**
     * Stops Export
     *
     * @param integer $iError error number
     */
    public function stop($i_error = 0): void
    {
        if ($i_error) {
            $this->_a_view_data['iError'] = $i_error;
        }
        // delete temporary heap table
        \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->execute('drop TABLE if exists ' . $this->get_heap_table_name());
    }
    /**
     * virtual function must be overloaded
     *
     * @param integer $cnt counter
     *
     * @return bool
     */
    public function next_tick($cnt)
    {
        return false;
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
        fwrite($this->fp_file, $s_line . "\r\n");
    }
    /**
     * Does Export
     */
    public function run(): void
    {
        $bl_continue = true;
        $i_exported_items = 0;
        $this->fp_file = @fopen($this->_s_file_path, 'a');
        if (!isset($this->fp_file) || !$this->fp_file) {
            // we do have an error !
            $this->stop(ERR_FILEIO);
        } else {
            // file is open
            $i_start = Registry::get_request()->get_request_escaped_parameter('iStart');
            // load from session
            $this->_a_export_resultset = Registry::get_request()->get_request_escaped_parameter('aExportResultset');
            $i_export_per_tick = $this->get_export_per_tick();
            for ($i = $i_start; $i < $i_start + $i_export_per_tick; $i++) {
                if (($i_exported_items = $this->next_tick($i)) === false) {
                    // end reached
                    $this->stop(ERR_SUCCESS);
                    $bl_continue = false;
                    break;
                }
            }
            if ($bl_continue) {
                // make ticker continue
                $this->_a_view_data['refresh'] = 0;
                $this->_a_view_data['iStart'] = $i;
                $this->_a_view_data['iExpItems'] = $i_exported_items;
            }
            fclose($this->fp_file);
        }
    }
    /**
     * Returns how many articles should be exported per tick
     *
     * @return int
     */
    public function get_export_per_tick()
    {
        if ($this->_i_export_per_tick === null) {
            $this->_i_export_per_tick = (int) Registry::get_config()->get_config_param('iExportNrofLines');
            if (!$this->_i_export_per_tick) {
                $this->_i_export_per_tick = $this->i_export_per_tick;
            }
        }
        return $this->_i_export_per_tick;
    }
    /**
     * Sets how many articles should be exported per tick
     *
     * @param int $iCount articles count per tick
     */
    public function set_export_per_tick($i_count): void
    {
        $this->_i_export_per_tick = $i_count;
    }
    /**
     * Removes Session ID from $sInput
     *
     * @param string $sInput Input to process
     */
    public function remove_sid($s_input)
    {
        $session = Registry::get_session();
        $s_sid = $session->get_id();
        // remove sid from link
        $s_output = str_replace("sid={$s_sid}/", '', $s_input);
        $s_output = str_replace("sid/{$s_sid}/", '', $s_output);
        $s_output = str_replace("sid={$s_sid}&amp;", '', $s_output);
        $s_output = str_replace("sid={$s_sid}&", '', $s_output);
        return str_replace("sid={$s_sid}", '', $s_output);
    }
    /**
     * Removes tags, shortens a string to $iMaxSize adding "..."
     *
     * @param string  $sInput          input to process
     * @param integer $iMaxSize        maximum output size
     * @param bool    $blRemoveNewline if true - \n and \r will be replaced by " "
     *
     * @return string
     */
    public function shrink($s_input, $i_max_size, $bl_remove_newline = true)
    {
        if ($bl_remove_newline) {
            $s_input = str_replace("\r\n", ' ', $s_input);
            $s_input = str_replace("\n", ' ', $s_input);
        }
        $s_input = str_replace("\t", '    ', $s_input);
        // remove html entities, remove html tags
        $s_input = $this->un_html_entities(strip_tags($s_input));
        $o_str = Str::get_str();
        if ($o_str->strlen($s_input) > $i_max_size - 3) {
            return $o_str->substr($s_input, 0, $i_max_size - 5) . '...';
        }
        return $s_input;
    }
    /**
     * Loads all article parent categories and returns titles separated by $$separator
     */
    public function get_category_string($article, $separator = '/')
    {
        $category_titles = '';
        $s_lang = Registry::get_lang()->get_base_language();
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $category_view_name = $table_view_name_generator->get_view_name('oxcategories', $s_lang);
        $object_to_category_view_name = $table_view_name_generator->get_view_name('oxobject2category', $s_lang);
        $query = sprintf('select c.oxleft as oxleft, c.oxright as oxright, c.oxrootid as oxrootid from %s as o2c left join ' . '%s as c on c.oxid = o2c.oxcatnid where o2c.oxobjectid = :oxobjectid and c.oxactive = 1 ' . 'order by o2c.oxtime', $object_to_category_view_name, $category_view_name);
        $categories = $database->select($query, ['oxobjectid' => $article->get_id()]);
        if ($categories != false && $categories->count() > 0) {
            $left = $categories->fields['oxleft'];
            $right = $categories->fields['oxright'];
            $root_id = $categories->fields['oxrootid'];
            $query = sprintf('select oxtitle from %s where oxright >= :oxright and oxleft <= :oxleft and' . ' oxrootid = :oxrootid order by oxleft', $category_view_name);
            $titles = $database->get_col($query, ['oxright' => $right, 'oxleft' => $left, 'oxrootid' => $root_id]);
            foreach ($titles as $title) {
                if ($category_titles) {
                    $category_titles .= $separator;
                }
                $category_titles .= $title;
            }
        }
        return $category_titles;
    }
    /**
     * Loads article default category
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle Article object
     */
    public function get_default_category_string($o_article)
    {
        $s_lang = Registry::get_lang()->get_base_language();
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_cat_view = $table_view_name_generator->get_view_name('oxcategories', $s_lang);
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category', $s_lang);
        //selecting category
        $s_q = "select {$s_cat_view}.oxtitle from {$s_o2c_view} as oxobject2category left join {$s_cat_view} on" . " {$s_cat_view}.oxid = oxobject2category.oxcatnid where oxobject2category.oxobjectid = :oxobjectid" . " and {$s_cat_view}.oxactive = 1 order by oxobject2category.oxtime ";
        return $o_db->get_one($s_q, ['oxobjectid' => $o_article->get_id()]);
    }
    /**
     * Converts field for CSV
     *
     * @param string $sInput input to process
     *
     * @return string
     */
    public function prepare_csv($s_input)
    {
        $s_input = Registry::get_utils_string()->prepare_csv_field($s_input);
        return str_replace(['&nbsp;', '&euro;', '|'], [' ', '', ''], $s_input);
    }
    /**
     * Changes special chars to be XML compatible
     *
     * @param string $sInput string which have to be changed
     *
     * @return string
     */
    public function prepare_xml($s_input)
    {
        $s_output = str_replace('&', '&amp;', $s_input);
        $s_output = str_replace('"', '&quot;', $s_output);
        $s_output = str_replace('>', '&gt;', $s_output);
        $s_output = str_replace('<', '&lt;', $s_output);
        return str_replace("'", '&apos;', $s_output);
    }
    /**
     * Searches for deepest path to a categorie this article is assigned to
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     *
     * @return string
     */
    public function get_deepest_category_path($o_article)
    {
        return $this->find_deepest_cat_path($o_article);
    }
    /**
     * create export resultset
     *
     * @return int
     */
    public function prepare_export()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_heap_table = $this->get_heap_table_name();
        // #1070 Saulius 2005.11.28
        // check mySQL version
        $o_rs = $o_db->select('SELECT version() as version');
        $s_table_charset = $this->generate_table_char_set($o_rs->fields['version']);
        // create heap table
        if (!$this->create_heap_table($s_heap_table, $s_table_charset)) {
            // error
            Registry::get_utils()->show_message_and_exit("Could not create HEAP Table {$s_heap_table}\n<br>");
        }
        $s_cat_add = $this->get_cat_add(Registry::get_request()->get_request_escaped_parameter('acat'));
        if (!$this->insert_articles($s_heap_table, $s_cat_add)) {
            Registry::get_utils()->show_message_and_exit("Could not insert Articles in Table {$s_heap_table}\n<br>");
        }
        $this->remove_parent_articles($s_heap_table);
        $this->set_session_params();
        // get total cnt
        return $o_db->get_one("select count(*) from {$s_heap_table}");
    }
    /**
     * get's one oxid for exporting
     *
     * @param integer $iCnt       counter
     * @param bool    $blContinue false is used to stop exporting
     *
     * @return mixed
     */
    public function get_one_article($i_cnt, &$bl_continue)
    {
        $my_config = Registry::get_config();
        //[Alfonsas 2006-05-31] setting specific parameter
        //to be checked in oxarticle.php init() method
        $my_config->set_config_param('blExport', true);
        $bl_continue = false;
        if ($o_article = $this->init_article($this->get_heap_table_name(), $i_cnt, $bl_continue)) {
            $bl_continue = true;
            $o_article = $this->set_campaign_detail_link($o_article);
        }
        //[Alfonsas 2006-05-31] unsetting specific parameter
        //to be checked in oxarticle.php init() method
        $my_config->set_config_param('blExport', false);
        return $o_article;
    }
    /**
     * Make sure that string is never empty.
     *
     * @param string $sInput   string that will be replaced
     * @param string $sReplace string that will replace
     *
     * @return string
     */
    public function assure_content($s_input, $s_replace = null)
    {
        $o_str = Str::get_str();
        if (!$o_str->strlen($s_input)) {
            if (!isset($s_replace) || !$o_str->strlen($s_replace)) {
                $s_replace = '-';
            }
            $s_input = $s_replace;
        }
        return $s_input;
    }
    /**
     * Replace HTML Entities
     * Replacement for html_entity_decode which is only available from PHP 4.3.0 onj
     *
     * @param string $sInput string to replace
     *
     * @return string
     */
    protected function un_html_entities($s_input)
    {
        $a_trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES));
        return strtr($s_input, $a_trans_tbl);
    }
    /**
     * Create valid Heap table name
     *
     * @return string
     */
    protected function get_heap_table_name()
    {
        // table name must not start with any digit
        $session = Registry::get_session();
        return 'tmp_' . str_replace('0', '', md5((string) $session->get_id()));
    }
    /**
     * generates table charset
     *
     * @param string $sMysqlVersion MySql version
     *
     * @return string
     */
    protected function generate_table_char_set($s_mysql_version)
    {
        $s_table_charset = '';
        //if MySQL >= 4.1.0 set charsets and collations
        if (version_compare($s_mysql_version, '4.1.0', '>=') > 0) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $o_rs = $o_db->select("SHOW FULL COLUMNS FROM `oxarticles` WHERE field like 'OXID'");
            if (isset($o_rs->fields['Collation']) && $s_mysql_collation = $o_rs->fields['Collation']) {
                $o_rs = $o_db->select("SHOW COLLATION LIKE '{$s_mysql_collation}'");
                if (isset($o_rs->fields['Charset']) && $s_mysql_character_set = $o_rs->fields['Charset']) {
                    $s_table_charset = "DEFAULT CHARACTER SET {$s_mysql_character_set} COLLATE {$s_mysql_collation}";
                }
            }
        }
        return $s_table_charset;
    }
    /**
     * creates heaptable
     *
     * @param string $sHeapTable    table name
     * @param string $sTableCharset table charset
     *
     * @return bool
     */
    protected function create_heap_table($s_heap_table, $s_table_charset)
    {
        $bl_done = false;
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = sprintf("CREATE TABLE IF NOT EXISTS %s ( `oxid` CHAR(32) NOT NULL default '' ) ENGINE=InnoDB %s", $s_heap_table, $s_table_charset);
        if ($o_db->execute($s_q) !== false) {
            $bl_done = true;
            $o_db->execute("TRUNCATE TABLE {$s_heap_table}");
        }
        return $bl_done;
    }
    /**
     * creates additional cat string
     *
     * @param array $aChosenCat Selected category array
     *
     * @return string
     */
    protected function get_cat_add($a_chosen_cat)
    {
        $s_cat_add = null;
        if (is_array($a_chosen_cat) && count($a_chosen_cat)) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_cat_add = ' and ( ';
            $bl_sep = false;
            foreach ($a_chosen_cat as $s_cat) {
                if ($bl_sep) {
                    $s_cat_add .= ' or ';
                }
                $s_cat_add .= 'oxobject2category.oxcatnid = ' . $o_db->quote($s_cat);
                $bl_sep = true;
            }
            $s_cat_add .= ')';
        }
        return $s_cat_add;
    }
    /**
     * inserts articles into heaptable
     *
     * @param string $sHeapTable heap table name
     * @param string $sCatAdd    category id filter (part of sql)
     *
     * @return bool
     */
    protected function insert_articles($s_heap_table, $s_cat_add)
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $i_exp_lang = Registry::get_request()->get_request_escaped_parameter('iExportLanguage');
        if (!isset($i_exp_lang)) {
            $i_exp_lang = Registry::get_session()->get_variable('iExportLanguage');
        }
        $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
        $o_article->set_language($i_exp_lang);
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_o2c_view = $table_view_name_generator->get_view_name('oxobject2category', $i_exp_lang);
        $s_article_table = $table_view_name_generator->get_view_name('oxarticles', $i_exp_lang);
        $insert_query = "insert into {$s_heap_table} select {$s_article_table}.oxid from {$s_article_table}, {$s_o2c_view}" . ' as oxobject2category where ';
        $insert_query .= $o_article->get_sql_active_snippet();
        if (!Registry::get_request()->get_request_escaped_parameter('blExportVars')) {
            $insert_query .= " and {$s_article_table}.oxid = oxobject2category.oxobjectid and" . " {$s_article_table}.oxparentid = '' ";
        } else {
            $insert_query .= " and ( {$s_article_table}.oxid = oxobject2category.oxobjectid or {$s_article_table}.oxparentid" . ' = oxobject2category.oxobjectid ) ';
        }
        $s_search_string = Registry::get_request()->get_request_escaped_parameter('search');
        if (isset($s_search_string)) {
            $insert_query .= "and ( {$s_article_table}.OXTITLE like " . $o_db->quote("%{$s_search_string}%");
            $insert_query .= " or {$s_article_table}.OXSHORTDESC like " . $o_db->quote("%{$s_search_string}%");
            $insert_query .= " or {$s_article_table}.oxsearchkeys like " . $o_db->quote("%{$s_search_string}%") . ' ) ';
        }
        if ($s_cat_add) {
            $insert_query .= $s_cat_add;
        }
        // add minimum stock value
        if (Registry::get_config()->get_config_param('blUseStock') && $d_min_stock = Registry::get_request()->get_request_escaped_parameter('sExportMinStock')) {
            $d_min_stock = str_replace([';', ' ', '/', "'"], '', $d_min_stock);
            $insert_query .= " and {$s_article_table}.oxstock >= " . $o_db->quote($d_min_stock);
        }
        $insert_query .= " group by {$s_article_table}.oxid";
        return $o_db->execute($insert_query) ? true : false;
    }
    /**
     * removes parent articles so that we only have variants itself
     *
     * @param string $sHeapTable table name
     */
    protected function remove_parent_articles($heap_table)
    {
        if (!Registry::get_request()->get_request_escaped_parameter('blExportMainVars')) {
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $article_table = $table_view_name_generator->get_view_name('oxarticles');
            $query = sprintf('select h.oxid from %s as h, %s as a where h.oxid = a.oxparentid group by h.oxid', $heap_table, $article_table);
            $parents_article = $database->get_col($query);
            $delete_query = "delete from {$heap_table} where oxid in ( ";
            $separator = false;
            foreach ($parents_article as $parent_article) {
                if ($separator) {
                    $delete_query .= ',';
                }
                $delete_query .= $database->quote($parent_article);
                $separator = true;
            }
            $delete_query .= ' )';
            $database->execute($delete_query);
        }
    }
    /**
     * stores some info in session
     */
    protected function set_session_params()
    {
        // reset it from session
        Registry::get_session()->delete_variable('sExportDelCost');
        $d_del_cost = Registry::get_request()->get_request_escaped_parameter('sExportDelCost');
        if (isset($d_del_cost)) {
            $d_del_cost = str_replace([';', ' ', '/', "'"], '', $d_del_cost);
            $d_del_cost = str_replace(',', '.', $d_del_cost);
            Registry::get_session()->set_variable('sExportDelCost', $d_del_cost);
        }
        Registry::get_session()->delete_variable('sExportMinPrice');
        $d_min_price = Registry::get_request()->get_request_escaped_parameter('sExportMinPrice');
        if (isset($d_min_price)) {
            $d_min_price = str_replace([';', ' ', '/', "'"], '', $d_min_price);
            $d_min_price = str_replace(',', '.', $d_min_price);
            Registry::get_session()->set_variable('sExportMinPrice', $d_min_price);
        }
        // #827
        Registry::get_session()->delete_variable('sExportCampaign');
        $s_campaign = Registry::get_request()->get_request_escaped_parameter('sExportCampaign');
        if (isset($s_campaign)) {
            $s_campaign = str_replace([';', ' ', '/', "'"], '', $s_campaign);
            Registry::get_session()->set_variable('sExportCampaign', $s_campaign);
        }
        // reset it from session
        Registry::get_session()->delete_variable('blAppendCatToCampaign');
        // now retrieve it from get or post.
        $bl_append_cat_to_campaign = Registry::get_request()->get_request_escaped_parameter('blAppendCatToCampaign');
        if ($bl_append_cat_to_campaign) {
            Registry::get_session()->set_variable('blAppendCatToCampaign', $bl_append_cat_to_campaign);
        }
        // reset it from session
        Registry::get_session()->delete_variable('iExportLanguage');
        Registry::get_session()->set_variable('iExportLanguage', Registry::get_request()->get_request_escaped_parameter('iExportLanguage'));
        //setting the custom header
        Registry::get_session()->set_variable('sExportCustomHeader', Registry::get_request()->get_request_escaped_parameter('sExportCustomHeader'));
    }
    /**
     * Load all root cat's == all trees
     */
    protected function load_root_cats()
    {
        if ($this->_a_cat_lvl_cache === null) {
            $this->_a_cat_lvl_cache = [];
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $category_view = $table_view_name_generator->get_view_name('oxcategories');
            $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            // Load all root categories
            $root_categories_id = $database->get_col(sprintf("select oxid from %s where oxparentid = 'oxrootid'", $category_view));
            foreach ($root_categories_id as $root_category_id) {
                $tree_query = sprintf('SELECT s.oxid as oxid, s.oxtitle as oxtitle, s.oxparentid as oxparentid, count( * ) AS' . ' LEVEL FROM %s v, %s s WHERE s.oxrootid = :oxrootid and v.oxrootid = :oxrootid and' . " s.oxleft BETWEEN v.oxleft AND v.oxright AND s.oxhidden = '0' GROUP BY s.oxleft order by level", $category_view, $category_view);
                $tree = $database->select($tree_query, ['oxrootid' => $root_category_id]);
                if ($tree != false && $tree->count() > 0) {
                    while (!$tree->EOF) {
                        $category = new stdClass();
                        $category->_s_oxid = $tree->fields['oxid'];
                        $category->oxtitle = $tree->fields['oxtitle'];
                        $category->oxparentid = $tree->fields['oxparentid'];
                        $category->ilevel = $tree->fields['LEVEL'];
                        $this->_a_cat_lvl_cache[$category->_s_oxid] = $category;
                        $tree->fetch_row();
                    }
                }
            }
        }
        return $this->_a_cat_lvl_cache;
    }
    /**
     * finds deepest category path
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     *
     * @return string
     */
    protected function find_deepest_cat_path($o_article)
    {
        $s_ret = '';
        // find deepest
        $a_ids = $o_article->get_category_ids();
        if (is_array($a_ids) && count($a_ids)) {
            if ($a_cat_lvl_cache = $this->load_root_cats()) {
                $s_id_max = null;
                $d_max_lvl = 0;
                foreach ($a_ids as $s_cat_id) {
                    if ($d_max_lvl < $a_cat_lvl_cache[$s_cat_id]->ilevel) {
                        $d_max_lvl = $a_cat_lvl_cache[$s_cat_id]->ilevel;
                        $s_id_max = $s_cat_id;
                        $s_ret = $a_cat_lvl_cache[$s_cat_id]->oxtitle;
                    }
                }
                // endless
                while (true) {
                    if (!isset($a_cat_lvl_cache[$s_id_max]->oxparentid) || $a_cat_lvl_cache[$s_id_max]->oxparentid == 'oxrootid') {
                        break;
                    }
                    $s_id_max = $a_cat_lvl_cache[$s_id_max]->oxparentid;
                    $s_ret = $a_cat_lvl_cache[$s_id_max]->oxtitle . '/' . $s_ret;
                }
            }
        }
        return $s_ret;
    }
    /**
     * initialize article
     *
     * @param string $sHeapTable heap table name
     * @param int    $iCnt       record number
     * @param bool   $blContinue false is used to stop exporting
     *
     * @return object
     */
    protected function init_article($s_heap_table, $i_cnt, &$bl_continue)
    {
        $o_rs = $this->get_db()->select_limit("select oxid from {$s_heap_table}", 1, $i_cnt);
        if ($o_rs != false && $o_rs->count() > 0) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->set_load_parent_data(true);
            $o_article->set_language(Registry::get_session()->get_variable('iExportLanguage'));
            if ($o_article->load($o_rs->fields['oxid'])) {
                // if article exists, do not stop export
                $bl_continue = true;
                // check price
                $d_min_price = Registry::get_request()->get_request_escaped_parameter('sExportMinPrice');
                if (!isset($d_min_price) || isset($d_min_price) && $o_article->get_price()->get_brutto_price() >= $d_min_price) {
                    //Saulius: variant title added
                    $s_title = $o_article->oxarticles__oxvarselect->value ? ' ' . $o_article->oxarticles__oxvarselect->value : '';
                    $o_article->oxarticles__oxtitle->set_value($o_article->oxarticles__oxtitle->value . $s_title);
                    return $this->update_article($o_article);
                }
            }
        }
    }
    /**
     * sets detail link for campaigns
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $oArticle article object
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function set_campaign_detail_link($o_article)
    {
        // #827
        if ($s_campaign = Registry::get_request()->get_request_escaped_parameter('sExportCampaign')) {
            // modify detaillink
            //#1166R - pangora - campaign
            $o_article->append_link("campaign={$s_campaign}");
            if (Registry::get_request()->get_request_escaped_parameter('blAppendCatToCampaign') && $s_cat = $this->get_category_string($o_article)) {
                $o_article->append_link("/{$s_cat}");
            }
        }
        return $o_article;
    }
    /**
     * Returns view id ('dyn_interface')
     *
     * @return string
     */
    public function get_view_id()
    {
        return 'dyn_interface';
    }
    /**
     * Updates Article object. Method is used for overriding.
     *
     * @param \OxidEsales\Eshop\Application\Model\Article $article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    protected function update_article($article)
    {
        return $article;
    }
    /**
     * Get the actual database.
     *
     * @return \OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface The database.
     */
    protected function get_db()
    {
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
    }
}