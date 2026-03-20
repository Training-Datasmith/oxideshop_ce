<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Article actions manager. Collects and keeps actions of chosen article.
 */
class Actions extends \Oxid_Esales\Eshop\Core\Model\Multi_Language_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxactions';
    /**
     * Class constructor. Executes oxActions::init(), initiates parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxactions');
    }
    /**
     * Adds an article to this actions
     *
     * @param string $articleId id of the article to be added
     */
    public function add_article($article_id): void
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_q = 'select max(oxsort) 
                from oxactions2article 
                where oxactionid = :oxactionid and oxshopid = :oxshopid';
        $params = ['oxactionid' => $this->get_id(), 'oxshopid' => $this->get_shop_id()];
        $i_sort = (int) $o_db->get_one($s_q, $params) + 1;
        $o_new_group = ox_new(\Oxid_Esales\Eshop\Core\Model\Base_Model::class);
        $o_new_group->init('oxactions2article');
        $o_new_group->oxactions2article__oxshopid = new \Oxid_Esales\Eshop\Core\Field($this->get_shop_id());
        $o_new_group->oxactions2article__oxactionid = new \Oxid_Esales\Eshop\Core\Field($this->get_id());
        $o_new_group->oxactions2article__oxartid = new \Oxid_Esales\Eshop\Core\Field($article_id);
        $o_new_group->oxactions2article__oxsort = new \Oxid_Esales\Eshop\Core\Field($i_sort);
        $o_new_group->save();
    }
    /**
     * Removes an article from this actions
     *
     * @param string $articleId id of the article to be removed
     *
     * @return bool
     */
    public function remove_article($article_id)
    {
        // remove actions from articles also
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delete = 'delete from oxactions2article where oxactionid = :oxactionid and oxartid = :oxartid and oxshopid = :oxshopid';
        $i_removed_articles = $o_db->execute($s_delete, ['oxactionid' => $this->get_id(), 'oxartid' => $article_id, 'oxshopid' => $this->get_shop_id()]);
        return (bool) $i_removed_articles;
    }
    /**
     * Removes article action, returns true on success. For
     * performance - you can not load action object - just pass
     * action ID.
     *
     * @param string $articleId Object ID
     *
     * @return bool
     */
    public function delete($article_id = null)
    {
        $article_id = $article_id ?: $this->get_id();
        if (!$article_id) {
            return false;
        }
        // remove actions from articles also
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $s_delete = 'delete from oxactions2article where oxactionid = :oxactionid and oxshopid = :oxshopid';
        $o_db->execute($s_delete, ['oxactionid' => $article_id, 'oxshopid' => $this->get_shop_id()]);
        return parent::delete($article_id);
    }
    /**
     * return time left until finished
     *
     * @return int
     */
    public function get_time_left()
    {
        $i_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $i_from = strtotime((string) $this->oxactions__oxactiveto->value);
        return $i_from - $i_now;
    }
    /**
     * return time left until start
     *
     * @return int
     */
    public function get_time_until_start()
    {
        $i_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $i_from = strtotime((string) $this->oxactions__oxactivefrom->value);
        return $i_from - $i_now;
    }
    /**
     * start the promotion NOW!
     */
    public function start(): void
    {
        $this->oxactions__oxactivefrom = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time()));
        if ($this->oxactions__oxactiveto->value && $this->oxactions__oxactiveto->value != '0000-00-00 00:00:00') {
            $i_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
            $i_to = strtotime((string) $this->oxactions__oxactiveto->value);
            if ($i_now > $i_to) {
                $this->oxactions__oxactiveto = new \Oxid_Esales\Eshop\Core\Field('0000-00-00 00:00:00');
            }
        }
        $this->save();
    }
    /**
     * stop the promotion NOW!
     */
    public function stop(): void
    {
        $this->oxactions__oxactiveto = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time()));
        $this->save();
    }
    /**
     * check if this action is active
     *
     * @return bool
     */
    public function is_running()
    {
        if (!($this->oxactions__oxactive->value && $this->oxactions__oxtype->value == 2 && $this->oxactions__oxactivefrom->value != '0000-00-00 00:00:00')) {
            return false;
        }
        $i_now = \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time();
        $i_from = strtotime((string) $this->oxactions__oxactivefrom->value);
        if ($i_now < $i_from) {
            return false;
        }
        if ($this->oxactions__oxactiveto->value != '0000-00-00 00:00:00') {
            $i_to = strtotime((string) $this->oxactions__oxactiveto->value);
            if ($i_now > $i_to) {
                return false;
            }
        }
        return true;
    }
    /**
     * return assigned banner article
     *
     * @return \OxidEsales\Eshop\Application\Model\Article
     */
    public function get_banner_article()
    {
        $s_art_id = $this->fetch_banner_article_id();
        if ($s_art_id) {
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($this->is_admin()) {
                $o_article->set_language(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_edit_language());
            }
            if ($o_article->load($s_art_id)) {
                return $o_article;
            }
        }
        return null;
    }
    /**
     * Fetch the oxobjectid of the article corresponding this action.
     *
     * @return string The id of the oxobjectid belonging to this action.
     */
    protected function fetch_banner_article_id()
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        return $database->get_one('select oxobjectid from oxobject2action ' . 'where oxactionid = :oxactionid and oxclass = :oxclass', ['oxactionid' => $this->get_id(), 'oxclass' => 'oxarticle']);
    }
    /**
     * Returns assigned banner article picture url
     *
     * @return string
     */
    public function get_banner_picture_url()
    {
        if (isset($this->oxactions__oxpic) && $this->oxactions__oxpic->value) {
            $s_promo_dir = \Oxid_Esales\Eshop\Core\Registry::get_utils_file()->normalize_dir(\Oxid_Esales\Eshop\Core\Utils_File::PROMO_PICTURE_DIR);
            return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_picture_url($s_promo_dir . $this->oxactions__oxpic->value, false);
        }
    }
    /**
     * Returns assigned banner link. If no link is defined and article is
     * assigned to banner, article link will be returned.
     *
     * @return string
     */
    public function get_banner_link()
    {
        $s_url = null;
        if (isset($this->oxactions__oxlink) && $this->oxactions__oxlink->value) {
            /** @var \OxidEsales\Eshop\Core\UtilsUrl $oUtilsUlr */
            $o_utils_ulr = \Oxid_Esales\Eshop\Core\Registry::get_utils_url();
            $s_url = $o_utils_ulr->add_shop_host($this->oxactions__oxlink->value);
            $s_url = $o_utils_ulr->process_url($s_url);
        } else if ($o_article = $this->get_banner_article()) {
            // if article is assigned to banner, getting article link
            $s_url = $o_article->get_link();
        }
        return $s_url;
    }
    /**
     * Returns true if Action is default.
     *
     * @return bool
     */
    public function is_default()
    {
        return '0' === $this->oxactions__oxtype->value;
    }
}