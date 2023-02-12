<?php

class article
{
    static function redir()
    {
        global $db;
        $id =& $_GET['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::err404();
        }

        $db->query('SELECT `c_id` FROM `knowledge`.`articles` WHERE `id` = ?', $id);
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $row = $db->get_row();
        if ($row['c_id'] == '3') {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: /reviews/".$id);
        } else {
            myErr::err404();
        }
    }

    static function show($canonicalUrl = '')
    {
        global $db, $tpl;

        $id =& $_GET['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::hack('vuz', '/гостевая часть/просмотр статьи', 'Некорректный тип id статьи GET[id]', 'BadParam0');
            myErr::err404();
        }

        if ($canonicalUrl != '') {
            $canonicalUrl = '/article/'.$id;
        }
        $cat = $_GET['c_id'];
        if ($cat != 2 && $cat != 3) {
            $cat = 4; // sys
        }
		
        $db->query('
        	SELECT 
        		`cats`.`id`, `articles`.`name`, `articles`.`text`, 
        		`articles`.`title`, `articles`.`desc`, `articles`.`keywords`, 
        		`articles`.`show_date`, 
        		`authors`.`id` AS author_id, `authors`.`name` AS author_name, `authors`.`dan`, `authors`.`photo`
        	FROM 
        		`knowledge`.`articles` LEFT JOIN
        		`knowledge`.`cats` ON `cats`.`id`=`articles`.`c_id` LEFT JOIN
        		`knowledge`.`authors` ON `articles`.`author_id` = `authors`.`id`
        	WHERE `articles`.`id` = ? AND `articles`.`c_id` = ?', $id, $cat);
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $art = $db->get_row();
        if ($cat == 4) {
            $tpl->start('tpl/sys_article.html');
            $tpl->replace([
                '[head]'            => get_head($art['title'], $art['desc'], $art['keywords'],$canonicalUrl),
                '[second-gtm-code]' => getSecondGtmCode(),
                "[roof]"            => get_roof(),

                "[h1]"   => $art['name'],
                "[html]" => $art['text'],
                '[quiz]' => file_get_contents('tpl/quiz.html'),

                '[footer]' => file_get_contents('tpl/footer.html'),
            ]);
            if ($id == 29) {
                $tpl->replace([', user-scalable=no' => '',]);
            }
        } else {
            switch ($art['id']) {
                case '2': $nav='<a href="/jour/">Обзоры и рейтинги вузов</a>'; break;
                case '3': $nav='<a href="/reviews/">Поступление в вуз</a>'; break;
                default: $nav=''; break;
            }

            $sign = '<address>';
            if ($art['author_id']) {
                if ($art['photo'] == '1') {
                    $sign .= '
						<img src="//static'.DOMAIN.'/imgs/authors/'.$art['author_id'].'.jpg" alt="author photo">';
                } else {
                    $sign .= '
						<img src="//static'.DOMAIN.'/imgs/authors/default.png" alt="author icon">';
                }
                $sign .= '
					<div>
						<p>'.$art['author_name'].'</p>
						<p>'.$art['dan'].'</p>
						<p>EduNetwork.ru</p>
						<time pubdate="pubdate" datetime="'.$art['show_date'].'">'.date::mysql2Rus($art['show_date']).'</time>
					</div>';
            } else {
                $sign .= '
					<p>EduNetwork.ru</p>
					<time pubdate="pubdate" datetime="'.$art['show_date'].'">'.date::mysql2Rus(
                        $art['show_date']
                    ).'</time>';
            }

            $sign .= '</address>';

            $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
            ads::get($ads);

            $tpl->start('tpl/article.html');
            $tpl->replace([
                '[head]'            => get_head($art['title'], $art['desc'], $art['keywords'], $canonicalUrl),
                '[second-gtm-code]' => getSecondGtmCode(),
                "[roof]"            => get_roof(),

                '[nav]'  => $nav,
                "[h1]"   => $art['name'],
                "[text]" => $art['text'],
                "[sign]" => $sign,

                '[ads1]' => $ads[1],
                '[ads2]' => $ads[2],
                '[ads3]' => $ads[3],
                '[ads5]' => $ads[5],
                '[ads6]' => $ads[6],
                '[ads7]' => $ads[7],
                '[quiz]' => file_get_contents('tpl/quiz.html'),

                '[footer]' => file_get_contents('tpl/footer.html'),
            ]);
        }

        $tpl->out();
    }

    static function showCat($canonicalUrl = '')
    {
        global $db, $tpl;

        $id =& $_GET['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::hack(
                'vuz',
                '/гостевая часть/просмотр каталога статей',
                'Некорректный тип id категории GET[id]',
                'BadParam0'
            );
            myErr::err404();
        }

        $db->query('SELECT `name`, `title`, `desc`, `keywords` FROM `knowledge`.`cats` WHERE `id`=?', $id);
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $meta = $db->get_row();

        if ($id === '2') {
            $path   = 'jour';
            $filter = '';
        } else {
            $path = 'reviews';

            $filter = '
                <nav id="articles-nav">
					<ul>
						<li class="sel"><a href="#" city="0">Все города</a></li>
						<li><a href="#" city="26">Москва</a></li>
						<li><a href="#" city="44">Санкт-Петербург</a></li>';
            $db->query(
                '
                SELECT 
                    a.`city`, `cities`.`name` 
                FROM (
                    SELECT DISTINCT `city` FROM `knowledge`.`articles` WHERE `c_id`=3 AND `show_date`<=NOW() AND city NOT IN (26,44)
                ) a LEFT JOIN  `general`.`cities` ON a.`city`=`cities`.`id`
                ORDER BY `cities`.`name`'
            );

            while ($row = $db->get_row()) {
                $filter .= '<li><a href="#" city="'.$row['city'].'">'.$row['name'].'</a></li>';
            }
            $filter .= '
					</ul>
				</nav>';
        }

        $art_list = '';
        $db->query(
            'SELECT `id`, `name`, `about`, `show_date`, `city`
        	FROM `knowledge`.`articles` 
        	WHERE `show_date` < NOW() AND `c_id`=? 
        	ORDER BY `id` DESC',
            $id
        );
        while ($art = $db->get_row()) {
            $art_list .= '
            	<article city="'.$art['city'].'">
					<p class="name"><a href="/'.$path.'/'.$art['id'].'">'.$art['name'].'</a></p>
					<time pubdate="pubdate" datetime="'.$art['show_date'].'">'.date::mysql2Rus($art['show_date']).'</time>
					<p>'.$art['about'].'</p>
				</article>';
        }

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $tpl->start('tpl/articles.html');
        $tpl->replace([
            '[head]'            => get_head($meta['title'], $meta['desc'], $meta['keywords'],$canonicalUrl),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[h1]'       => $meta['name'],
            '[filter]'   => $filter,
            '[articles]' => $art_list,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],
            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html'),
        ]);
        $tpl->out();
    }
}

