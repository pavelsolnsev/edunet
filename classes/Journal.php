<?php

class Journal
{
    const META_TITLE = 'Медиа',
        META_DESC = 'Журнал',
        META_NAME = 'Медиа';

    public static function ShowList($params)
    {
        global $tpl, $host;

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);
        $url = '/jour/';
        if (intval($params['rubric']))
            $url .= '?rubric='.intval($params['rubric']);

        $list = self::getList($params);

        if(empty($list)) {
            ob_start();
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: '.$host->getHost().'/jour/');
            ob_end_flush();
            die("Redirect");
        }

        $articles = '';

        foreach ($list as $item) {
            $tpl->start('tpl/Journal/article-item.html');
            $tpl->replace([
                '[name]'          => $item['name'],
                '[slug]'          => $item['slug'],
                '[href]'          => '/jour/'.$item['id'].'/',
                '[date]'          => date("d.m.Y", strtotime($item['date'])),
                '[preview_image]' => $item['preview_image'],
                '[rubric]'        => $item['rubric'][0]['name'] ?? '',
                '[short_text]'    => $item['short_text'],
            ]);
            $articles .= $tpl->get();
        }
        $rubrics = self::getRubricsTpl();

        $new_metaTitle = self::META_TITLE;
        if (isset($params['rubric'])) {
            $rubrics_meta = [
                1 => 'Статьи о профессиях: где учиться, какой заработок, плюсы и минусы',
                2 => 'Статьи о ВУЗах – Актуальная информация для школьников, абитуриентов и студентов',
                4 => 'Статьи с актуальной информацией про ЕГЭ',
                5 => 'Статьи о поступлении в ВУЗ',
                6 => 'Рейтинг и популярность ВУЗов по городам – Самые востребованные специальности'
            ];
            if (isset($rubrics_meta[$params['rubric']])) {
                $new_metaTitle = $rubrics_meta[$params['rubric']];
            }
        }

        $new_metaDescription = self::META_DESC;
        if (isset($params['rubric'])) {
            $rubrics_meta = [
                1 => 'Рубрика Профессии на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                2 => 'Рубрика ВУЗы на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                4 => 'Рубрика ЕГЭ на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                5 => 'Рубрика Поступление на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                6 => 'Рубрика Рейтинги ВУЗов на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов'
            ];
            if (isset($rubrics_meta[$params['rubric']])) {
                $new_metaDescription = $rubrics_meta[$params['rubric']];
            }
        }

        $new_metaName = self::META_NAME;
        if (isset($params['rubric'])) {
            $rubrics_meta = [
                1 => 'Статьи о профессиях',
                2 => 'Все о ВУЗах: какую выбрать профессию, как подготовиться к поступлению, обзоры вузов',
                4 => 'Актуальная информация про ЕГЭ',
                5 => 'Все о поступлении в ВУЗ',
                6 => 'Рейтинги ВУЗов, стоимость обучения и востребованность специальностей по городам России'
            ];
            if (isset($rubrics_meta[$params['rubric']])) {
                $new_metaName = $rubrics_meta[$params['rubric']];
            }
        }
        $rubricBreadcrumb = '';
        if (isset($params['rubric'])) {
            $rubrics_meta = [
                1 => '<a>Профессии</a>',
                2 => '<a>ВУЗы</a>',
                4 => '<a>ЕГЭ</a>',
                5 => '<a>Поступление</a>',
                6 => '<a>Рейтинги ВУЗов</a>'
            ];
            if (isset($rubrics_meta[$params['rubric']])) {
                $rubricBreadcrumb = $rubrics_meta[$params['rubric']];
            }
        }
        
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $tpl->start('tpl/Journal/articles.html');
        $tpl->replace([
            '[head]'              => get_head($new_metaTitle, $new_metaDescription, '', '', $url),
            '[second-gtm-code]'   => getSecondGtmCode(),
            '[roof]'              => get_roof(),
            '[rubric-breadcrumb]' => $rubricBreadcrumb,
            '[description]'       => $new_metaDescription,
            '[h1]'                => $new_metaName,
            '[articles]'          => $articles,
            '[rubrics]'           => $rubrics,
            '[url]'               => $url,

            '[ads1]'              => $ads[1],
            '[ads2]'              => $ads[2],
            '[ads3]'              => $ads[3],
            '[ads5]'              => $ads[5],
            '[ads6]'              => $ads[6],
            '[ads7]'              => $ads[7],
            '[quiz]'              => file_get_contents('tpl/quiz.html'),
            '[footer]'            => file_get_contents('tpl/footer.html'),
        ]);
        $tpl->out();
    }

    public static function ShowItemById($id)
    {
        global $tpl, $host;
        $article = self::getById($id);
        $url = '/jour/'.$id.'/';

        if(!isset($article['id'])) {
            ob_start();
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: '.$host->getHost().'/jour/');
            ob_end_flush();
            die("Redirect");
        }

        $rubrics = self::getRubricsTpl();

        $articleRubrics = '';
        foreach ($article['rubric'] as $item) {
            $tpl->start('tpl/Journal/rubrics-item.html');
            $tpl->replace([
                '[id]'   => $item['id'],
                '[name]' => $item['name'],
            ]);
            $articleRubrics .= $tpl->get();
        }

        $breadcrumbRubric = '';
        if (isset($article['rubric'][0])) {
            $breadcrumbRubric = sprintf('<a href="/jour/?rubric=%s">%s</a> »', $article['rubric'][0]['id'], $article['rubric'][0]['name']);
        }

        if ($article['seo_description'] != '') {
            $meta_desc = $article['seo_description'];
        } else {
            $meta_desc = self::META_DESC;
        }

        $new_metaDescription = self::META_DESC;
        if (isset($params['rubric'])) {
            $rubrics_meta = [
                1 => 'Рубрика Профессии на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                2 => 'Рубрика ВУЗы на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                4 => 'Рубрика ЕГЭ на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                5 => 'Рубрика Поступление на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов',
                6 => 'Рубрика Рейтинги ВУЗов на сайте edunetwork.ru – лучшие статьи для школьников, абитуриентов и студентов'
            ];
            if (isset($rubrics_meta[$params['rubric']])) {
                $new_metaDescription = $rubrics_meta[$params['rubric']];
            }
        }

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $vars = [
            '[head]'                      => get_head($article['name'], $meta_desc, '', '', $url),
            '[second-gtm-code]'           => getSecondGtmCode(),
            '[roof]'                      => get_roof(),
            '[rubrics]'                   => $rubrics,

            '[date]'                      => date("d.m.Y", strtotime($article['date'])),
            '[preview_image]'             => $article['preview_image'],
            '[h1]'                        => $article['h1'] ?? $article['name'],
            '[full_text]'                 => $article['full_text'],
            '[author-img]'                => $article['author']['image'],
            '[author-name]'               => $article['author']['name'],
            '[author-text]'               => $article['author']['text'],
            '[article-rubric]'            => $articleRubrics,
            '[article-rubric-breadcrumb]' => $breadcrumbRubric,
            '[ylead]'                     => vuz::leads_form(),
            '[url]'                       => $url,
            '[description]'               => $new_metaDescription,

            '[quiz]'                      => file_get_contents('tpl/quiz.html'),
            '[footer]'                    => file_get_contents('tpl/footer.html'),
        ];

        if (!empty($article['article_similar'])) {

            $articleSimilarList = '';
            foreach ($article['article_similar'] as $item) {
                $articleSimilarList .= strtr(file_get_contents('tpl/Journal/article-similar-item.html'), array(
                    '[id]'   => $item['id'],
                    '[name]' => $item['name'],
                    '[img]'  => $item['preview_image'],
                ));
            }

            $vars['[article-similar]'] = strtr(file_get_contents('tpl/Journal/article-similar.html'), array(
                "[article-similar-list]" => $articleSimilarList
            ));
        } else {
            $vars['[article-similar]'] = '';
        }

        $tpl->start('tpl/Journal/article.html');
        $tpl->replace($vars);
        $tpl->out();
    }

    public static function getRubricsTpl()
    {
        global $tpl;
        $list = self::getRubrics();
        $rubrics = '';
        foreach ($list as $item) {
            $tpl->start('tpl/Journal/rubrics-item.html');
            $tpl->replace(array(
                '[id]'   => $item['id'],
                '[name]' => $item['name'],
            ));
            $rubrics .= $tpl->get();
        }

        return $rubrics;
    }

    public static function getList($params)
    {
        global $db;

        $list = [];
        $date = new DateTime();
        if ($params['rubric']) {
            $db->query('SELECT * FROM `vuz`.`articles` 
                INNER JOIN `vuz`.`article_rubric` ON `vuz`.`articles`.`id` = `vuz`.`article_rubric`.`article_id` 
                WHERE `vuz`.`article_rubric`.`rubric_id` = ? AND `published` = 1 AND `date` <= ?
                ORDER BY `date` DESC', $params['rubric'], $date->format("Y-m-d H:i:s"));
        } else {
            $db->query('SELECT * FROM `vuz`.`articles` 
                WHERE `published` = 1 AND `date` <= ? 
                ORDER BY `date` DESC', $date->format("Y-m-d H:i:s"));
        }

        while ($article = $db->get_row()) {
            $list[] = $article;
        }

        foreach ($list as &$item) {
            $db->query('
                SELECT id, name FROM `vuz`.`article_rubric` 
                INNER JOIN `vuz`.`rubrics` 
                ON `vuz`.`article_rubric`.`rubric_id` = `vuz`.`rubrics`.`id`
                WHERE `article_id` = ?'
                , $item['id']);

            $item['rubric'] = [];
            while ($rubric = $db->get_row()) {
                $item['rubric'][] = $rubric;
            }
        }

        return $list;
    }

    public static function getById($id)
    {
        global $db;

        $db->query('
                SELECT * FROM `vuz`.`articles` 
                WHERE `id` = ?'
            , $id);
        $article = $db->get_row();

        /** rubric */
        $db->query('
            SELECT id, name FROM `vuz`.`article_rubric` 
            INNER JOIN `vuz`.`rubrics` 
            ON `vuz`.`article_rubric`.`rubric_id` = `vuz`.`rubrics`.`id`
            WHERE `article_id` = ?'
            , $article['id']);

        $article['rubric'] = [];
        while ($rubric = $db->get_row()) {
            $article['rubric'][] = $rubric;
        }

        if (isset($article['short_text']) &&strpos($article['short_text'],'&gt;') > 0 ) {
            $article['short_text'] = html_entity_decode($article['short_text']);
        }

        if (isset($article['full_text']) &&strpos($article['full_text'],'&gt;') > 0 ) {
            $article['full_text'] = html_entity_decode($article['full_text']);
        }

        /** author */
        $db->query(
            'SELECT * FROM `vuz`.`authors` 
            WHERE `id` = ?',
            $article['author_id']
        );

        $article['author'] = $db->get_row();

        /** article_similar */
        $db->query(
            'SELECT * FROM `vuz`.`article_similar` 
            INNER JOIN `vuz`.`articles` 
            ON `vuz`.`article_similar`.`similar_article_id` = `vuz`.`articles`.`id`
            WHERE `vuz`.`article_similar`.`article_id` = ?',
            $article['id']
        );

        $article['article_similar'] = [];
        while ($rubric = $db->get_row()) {
            $article['article_similar'][] = $rubric;
        }

        try {
            $s = $db->query('SELECT * FROM `ads`.`seo` WHERE t_name="vuz.articles" and e_id=?', $id);
            if($db->num_rows($s)) {
                while($srow=$db->get_row($s)) {
                    $article['seo_title']    = $srow['title'];
                    $article['seo_description'] = $srow['description'];
                    if ($article['seo_title'] != '') $article['name'] = $article['seo_title'];
                }
            } else {
                $article['seo_title']    = '';
                $article['seo_description'] = '';
            }

        } catch (Exception $e ) {
            $article['seo_title']    = '';
            $article['seo_description'] = '';
        }

        return $article;
    }

    public static function getRubrics()
    {
        global $db;

        $list = [];
        $db->query('SELECT * FROM `vuz`.`rubrics`');
        while ($rubric = $db->get_row()) {
            $list[] = $rubric;
        }

        return $list;
    }
}