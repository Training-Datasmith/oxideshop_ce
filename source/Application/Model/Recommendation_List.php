<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Exception;
use Oxid_Esales\Eshop\Core\Database_Provider;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Recommendation list manager class.
 *
 * @deprecated since v5.3 (2016-06-17); Listmania will be moved to an own module.
 */
class Recommendation_List extends \Oxid_Esales\Eshop\Core\Model\Base_Model implements \Oxid_Esales\Eshop\Core\Contract\I_Url
{
    /**
     * Current object class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxRecommList';
    /**
     * Article list
     *
     * @var string
     */
    protected $_o_articles;
    /**
     * Article list loading filter (appended where statement)
     *
     * @var string
     */
    protected $_s_articles_filter = '';
    /**
     * Seo article urls for languages
     *
     * @var array
     */
    protected $_a_seo_urls = [];
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxrecommlists');
    }
    /**
     * Returns list of recommendation list items
     *
     * @param integer $iStart        start for sql limit
     * @param integer $iNrofArticles nr of items per page
     * @param bool    $blReload      if TRUE forces to reload list
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_articles($i_start = null, $i_nrof_articles = null, $bl_reload = false)
    {
        // cached ?
        if ($this->_o_articles !== null && !$bl_reload) {
            return $this->_o_articles;
        }
        $this->_o_articles = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        if ($i_start !== null && $i_nrof_articles !== null) {
            $this->_o_articles->set_sql_limit($i_start, $i_nrof_articles);
        }
        // loading basket items
        $this->_o_articles->load_recomm_articles($this->get_id(), $this->_s_articles_filter);
        return $this->_o_articles;
    }
    /**
     * Returns count of recommendation list items
     *
     * @return integer
     */
    public function get_art_count()
    {
        $i_cnt = 0;
        $s_select = $this->get_article_select();
        if ($s_select) {
            return Database_Provider::get_db()->get_one($s_select);
        }
        return $i_cnt;
    }
    /**
     * Returns the appropriate SQL select
     *
     * @return string
     */
    protected function get_article_select()
    {
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_art_view = $table_view_name_generator->get_view_name('oxarticles');
        $s_select = "select count(distinct {$s_art_view}.oxid) from oxobject2list ";
        $s_select .= "left join {$s_art_view} on oxobject2list.oxobjectid = {$s_art_view}.oxid ";
        return $s_select . ("where (oxobject2list.oxlistid = '" . $this->get_id() . "') ");
    }
    /**
     * returns first article from this list's article list
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_first_article()
    {
        $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
        $o_art_list->set_sql_limit(0, 1);
        $o_art_list->load_recomm_articles($this->get_id(), $this->_s_articles_filter);
        $o_art_list->rewind();
        return $o_art_list->current();
    }
    /**
     * Removes articles from the recommlist and deletes list
     *
     * @param string $sOXID Object ID(default null)
     *
     * @return bool
     */
    public function delete($s_oxid = null)
    {
        if (!$s_oxid) {
            $s_oxid = $this->get_id();
        }
        if (!$s_oxid) {
            return false;
        }
        if ($bl_delete = parent::delete($s_oxid)) {
            $o_db = Database_Provider::get_db();
            // cleaning up related data
            $o_db->execute('delete from oxobject2list where oxlistid = :oxlistid', ['oxlistid' => $s_oxid]);
            $this->on_delete();
        }
        return $bl_delete;
    }
    /**
     * Returns article description for recommendation list
     *
     * @param string $sOXID Object ID
     *
     * @return string
     */
    public function get_art_description($s_oxid)
    {
        if (!$s_oxid) {
            return false;
        }
        $o_db = Database_Provider::get_db();
        $s_select = 'select oxdesc from oxobject2list 
            where oxlistid = :oxlistid and oxobjectid = :oxobjectid';
        return $o_db->get_one($s_select, ['oxlistid' => $this->get_id(), 'oxobjectid' => $s_oxid]);
    }
    /**
     * Remove article from recommendation list
     *
     * @param string $sOXID Object ID
     *
     * @return bool
     */
    public function remove_article($s_oxid)
    {
        if ($s_oxid) {
            $o_db = Database_Provider::get_db();
            $s_q = 'delete from oxobject2list where oxobjectid = :oxobjectid and oxlistid = :oxlistid';
            return $o_db->execute($s_q, ['oxobjectid' => $s_oxid, 'oxlistid' => $this->get_id()]);
        }
    }
    /**
     * Add article to recommendation list
     *
     * @param string $sOXID Object ID
     * @param string $sDesc recommended article description
     *
     * @throws Exception
     *
     * @return bool
     */
    public function add_article($s_oxid, $s_desc)
    {
        $bl_add = false;
        if ($s_oxid) {
            // We force reading from master to prevent issues with slow replications or open transactions (see ESDEV-3804 and ESDEV-3822).
            $database = Database_Provider::get_master();
            $sql = 'select oxid from oxobject2list 
                where oxobjectid = :oxobjectid 
                    and oxlistid = :oxlistid';
            $params = ['oxobjectid' => $s_oxid, 'oxlistid' => $this->get_id()];
            if (!$database->get_one($sql, $params)) {
                $s_uid = \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_uid();
                $s_q = 'insert into oxobject2list (oxid, oxobjectid, oxlistid, oxdesc) values (:oxid, :oxobjectid, :oxlistid, :oxdesc)';
                $bl_add = $database->execute($s_q, ['oxid' => $s_uid, 'oxobjectid' => $s_oxid, 'oxlistid' => $this->get_id(), 'oxdesc' => $s_desc]);
            }
        }
        return $bl_add;
    }
    /**
     * get recommendation lists which include given article ids
     * also sort these lists by these criteria:
     *     1. show lists, that has more requested articles first
     *     2. show lists, that have more any articles
     *
     * @param array $aArticleIds Object IDs
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_recomm_lists_by_ids($a_article_ids)
    {
        if (is_array($a_article_ids) && count($a_article_ids)) {
            start_profile(__FUNCTION__);
            $s_ids = implode(',', Database_Provider::get_db()->quote_array($a_article_ids));
            $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_recomm_list->init('oxrecommlist');
            $i_cnt = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCrossellArticles');
            $o_recomm_list->set_sql_limit(0, $i_cnt);
            $s_select = 'SELECT distinct lists.* FROM oxobject2list AS o2l_lists';
            $s_select .= ' LEFT JOIN oxobject2list AS o2l_count ON o2l_lists.oxlistid = o2l_count.oxlistid';
            $s_select .= ' LEFT JOIN oxrecommlists as lists ON o2l_lists.oxlistid = lists.oxid';
            $s_select .= " WHERE o2l_lists.oxobjectid IN ( {$s_ids} ) and lists.oxshopid = :oxshopid";
            $s_select .= ' GROUP BY lists.oxid order by (';
            $s_select .= ' SELECT count( order1.oxobjectid ) FROM oxobject2list AS order1';
            $s_select .= " WHERE order1.oxobjectid IN ( {$s_ids} ) AND o2l_lists.oxlistid = order1.oxlistid";
            $s_select .= ' ) DESC, count( lists.oxid ) DESC';
            $o_recomm_list->select_string($s_select, ['oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()]);
            stop_profile(__FUNCTION__);
            if ($o_recomm_list->count()) {
                start_profile('_loadFirstArticles');
                $this->load_first_articles($o_recomm_list, $a_article_ids);
                stop_profile('_loadFirstArticles');
                return $o_recomm_list;
            }
        }
    }
    /**
     * loads first articles to recomm list also ordering them and clearing not usable list objects
     * ordering priorities:
     *     1. first show articles from our search
     *     2. do not shown articles as 1st, which are shown in other recomm lists as 1st
     *
     * @param \OxidEsales\Eshop\Core\Model\ListModel $oRecommList recommendation list
     * @param array                                  $aIds        article ids
     */
    protected function load_first_articles(\Oxid_Esales\Eshop\Core\Model\List_Model $o_recomm_list, $a_ids)
    {
        $a_ids = Database_Provider::get_db()->quote_array($a_ids);
        $s_ids = implode(', ', $a_ids);
        $a_prev_ids = [];
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_art_view = $table_view_name_generator->get_view_name('oxarticles');
        foreach ($o_recomm_list as $key => $o_recomm) {
            if (count($a_prev_ids)) {
                $s_negate_sql = " AND {$s_art_view}.oxid not in ( '" . implode("','", $a_prev_ids) . "' ) ";
            } else {
                $s_negate_sql = '';
            }
            $s_articles_filter = "{$s_negate_sql} ORDER BY {$s_art_view}.oxid in ( {$s_ids} ) desc";
            $o_recomm->set_articles_filter($s_articles_filter);
            $o_art_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Article_List::class);
            $o_art_list->set_sql_limit(0, 1);
            $o_art_list->load_recomm_articles($o_recomm->get_id(), $s_articles_filter);
            if (count($o_art_list) == 1) {
                $o_art_list->rewind();
                $o_article = $o_art_list->current();
                $s_id = $o_article->get_id();
                $a_prev_ids[$s_id] = $s_id;
                unset($a_ids[$s_id]);
                $s_ids = implode(', ', $a_ids);
            } else {
                unset($o_recomm_list[$key]);
            }
        }
    }
    /**
     * Returns user recommendation list objects
     *
     * @param string $sSearchStr Search string
     *
     * @return object oxlist with oxrecommlist objects
     */
    public function get_search_recomm_lists($s_search_str)
    {
        if ($s_search_str) {
            // sets active page
            $i_act_page = (int) Registry::get_request()->get_request_escaped_parameter('pgNr');
            $i_act_page = $i_act_page < 0 ? 0 : $i_act_page;
            // load only lists which we show on screen
            $i_nrof_cat_articles = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iNrofCatArticles');
            $i_nrof_cat_articles = $i_nrof_cat_articles ?: 10;
            $o_recomm_list = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_recomm_list->init('oxrecommlist');
            $s_select = $this->get_search_select($s_search_str);
            $o_recomm_list->set_sql_limit($i_nrof_cat_articles * $i_act_page, $i_nrof_cat_articles);
            $o_recomm_list->select_string($s_select);
            return $o_recomm_list;
        }
    }
    /**
     * Returns the amount of lists according to search parameters.
     *
     * @param string $sSearchStr Search string
     *
     * @return int
     */
    public function get_search_recomm_list_count($s_search_str)
    {
        $i_cnt = 0;
        $s_select = $this->get_search_select($s_search_str);
        if ($s_select) {
            $s_partial = substr($s_select, strpos($s_select, ' from '));
            $s_select = "select count( distinct rl.oxid ) {$s_partial} ";
            $i_cnt = Database_Provider::get_db()->get_one($s_select);
        }
        return $i_cnt;
    }
    /**
     * Returns the appropriate SQL select according to search parameters
     *
     * @param string $sSearchStr Search string
     *
     * @return string
     */
    protected function get_search_select($s_search_str)
    {
        $i_shop_id = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id();
        $s_search_str_quoted = Database_Provider::get_db()->quote("%{$s_search_str}%");
        $s_select = 'select distinct rl.* from oxrecommlists as rl';
        $s_select .= ' inner join oxobject2list as o2l on o2l.oxlistid = rl.oxid';
        $s_select .= " where ( rl.oxtitle like {$s_search_str_quoted} or rl.oxdesc like {$s_search_str_quoted}";
        return $s_select . " or o2l.oxdesc like {$s_search_str_quoted} ) and rl.oxshopid = '{$i_shop_id}'";
    }
    /**
     * Calculates and saves product rating average
     *
     * @param integer $iRating new rating value
     */
    public function add_to_rating_average($i_rating): void
    {
        $d_old_rating = $this->oxrecommlists__oxrating->value;
        $d_old_cnt = $this->oxrecommlists__oxratingcnt->value;
        $this->oxrecommlists__oxrating = new \Oxid_Esales\Eshop\Core\Field(($d_old_rating * $d_old_cnt + $i_rating) / ($d_old_cnt + 1), \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->oxrecommlists__oxratingcnt = new \Oxid_Esales\Eshop\Core\Field($d_old_cnt + 1, \Oxid_Esales\Eshop\Core\Field::T_RAW);
        $this->save();
    }
    /**
     * Collects user written reviews about an article.
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel
     */
    public function get_reviews()
    {
        $o_review = ox_new(\Oxid_Esales\Eshop\Application\Model\Review::class);
        $o_revs = $o_review->load_list('oxrecommlist', $this->get_id());
        //if no review found, return null
        if ($o_revs->count() < 1) {
            return null;
        }
        return $o_revs;
    }
    /**
     * Returns raw recommlist seo url
     *
     * @param int $iLang language id
     * @param int $iPage page number [optional]
     *
     * @return string
     */
    public function get_base_seo_link($i_lang, $i_page = 0)
    {
        $o_encoder = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Seo_Encoder_Recomm::class);
        if (!$i_page) {
            return $o_encoder->get_recomm_url($this, $i_lang);
        }
        return $o_encoder->get_recomm_page_url($this, $i_page, $i_lang);
    }
    /**
     * return url to this recomm list page
     *
     * @param int $iLang language id [optional]
     *
     * @return string
     */
    public function get_link($i_lang = null)
    {
        if ($i_lang === null) {
            $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        }
        if (!\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active()) {
            return $this->get_std_link($i_lang);
        }
        if (!isset($this->_a_seo_urls[$i_lang])) {
            $this->_a_seo_urls[$i_lang] = $this->get_base_seo_link($i_lang);
        }
        return $this->_a_seo_urls[$i_lang];
    }
    /**
     * Returns standard (dynamic) object URL
     *
     * @param int   $iLang   language id [optional]
     * @param array $aParams additional params to use [optional]
     *
     * @return string
     */
    public function get_std_link($i_lang = null, $a_params = [])
    {
        if ($i_lang === null) {
            $i_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_base_language();
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->process_url($this->get_base_std_link($i_lang), true, $a_params, $i_lang);
    }
    /**
     * Returns base dynamic recommlist url: shopurl/index.php?cl=recommlist
     *
     * @param int  $iLang   language id
     * @param bool $blAddId add current object id to url or not
     * @param bool $blFull  return full including domain name [optional]
     *
     * @return string
     */
    public function get_base_std_link($i_lang, $bl_add_id = true, $bl_full = true)
    {
        $s_url = '';
        if ($bl_full) {
            //always returns shop url, not admin
            $s_url = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_url($i_lang, false);
        }
        return $s_url . 'index.php?cl=recommlist' . ($bl_add_id ? '&amp;recommid=' . $this->get_id() : '');
    }
    /**
     * set sql filter for article loading
     *
     * @param string $sArticlesFilter article filter
     */
    public function set_articles_filter($s_articles_filter): void
    {
        $this->_s_articles_filter = $s_articles_filter;
    }
    /**
     * Save this Object to database, insert or update as needed.
     *
     * @return mixed
     */
    public function save()
    {
        if (!$this->oxrecommlists__oxtitle->value) {
            throw ox_new(\Oxid_Esales\Eshop\Core\Exception\Object_Exception::class, 'EXCEPTION_RECOMMLIST_NOTITLE');
        }
        $this->on_save();
        return parent::save();
    }
    /**
     * Method is used for overriding when deleting recommendation list.
     */
    protected function on_delete()
    {
    }
    /**
     * Method is used for overriding when saving.
     */
    protected function on_save()
    {
    }
}