<?php

use Edunetwork\Common\Domain;
use Edunetwork\Vuz\EgeHelper;
use Edunetwork\Root\AuthHelper;

class general
{

    static function dbg_log ($db, $branch = 'general', $data)
    {
        $db->query(
            'INSERT INTO `system`.`log` (`name`, `data`, `status`) VALUES ('.$branch.','.$data.', "Ok")'
        );

    }

    static function main($u_id)
    {
        global $tpl, $db;

        $ads = [1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads, 0, 4);

        $db->query(
            'SELECT `dirs`, `free`, `gos`, `gos_studs`, `forms`, `fils`, `mag` FROM `e_plus_geo` WHERE `city_id` IS NULL'
        );
        $row = $db->get_row();

        $graph       = '<div class="row">
                    <div class="col s12 m6 l4">
                        <h4>Направления обучения</h4>
                        <div id="chart-direct">';
        $row['dirs'] = explode("\n", $row['dirs']);
        for ($i = 0; $i < 5; $i++) {
            $dirs  = explode("|", $row['dirs'][$i]);
            $graph .= '<p data-val="' . $dirs[1] . '" style="width:1%">' . $dirs[0] . '</p>';
        }
        $graph .= '
                        </div>
                    </div>
                    <div class="col s12 m6 l4">
                        <h4>Форма обучения</h4>
                        <canvas id="chart-forms">' . $row['forms'] . '</canvas>
                    </div>
                    <div class="col s12 m6 l4">
                            <h4>Учебные заведения</h4>
                            <canvas id="chart-fils">' . $row['fils'] . '</canvas>
                    </div>
                    <div class="col s12 m6 l4">
                            <h4>Форма собственности</h4>
                            <canvas id="chart-state">' . $row['gos'] . '</canvas>
                    </div>
                    <div class="col s12 m6 l4 enw-plus-hide">
                            <h4>Обучается на</h4>
                            <canvas id="chart-cost">' . $row['free'] . '</canvas>
                    </div>
                    <div class="col s12 m6 l4 enw-plus-hide">
                            <h4>Студентов в</h4>
                            <canvas id="chart-studs">' . $row['gos_studs'] . '</canvas>
                    </div>
                </div>
                <p class="Ep-more"><a href="#!"><i class="material-icons">expand_more</i><span>Показать еще данные</span></a></p>';

        $db->query(
            '
                SELECT `articles`.`id`, `articles`.`name`, `articles`.`about`
                FROM `knowledge`.`articles`
                WHERE `articles`.`c_id`=3 AND `articles`.`show_date`<NOW() 
                ORDER BY `articles`.`show_date` DESC LIMIT 2
            '
        );
        $arts = '';
        while ($row = $db->get_row()) {
            $arts .= '<div><p><a href="/reviews/' . $row['id'] . '">' . $row['name'] . '</a></p><p>' . $row['about'] . '</p></div>';
        }

        $db->query(
            '
                SELECT `articles`.`id`, `articles`.`name`, `articles`.`about`, `articles`.`add_date` 
                FROM `knowledge`.`articles`
                WHERE `articles`.`c_id`=2 AND `articles`.`show_date`<NOW() 
                ORDER BY `articles`.`show_date` DESC LIMIT 2'
        );
        $jour = '';

        $bubbleDate = strtotime('01/02/2020');
        $lmDate     = date('D, d M Y H:i:s', $bubbleDate);

        while ($row = $db->get_row()) {
            $jour    .= '<div><p><a href="/jour/' . $row['id'] . '">' . $row['name'] . '</a></p><p>' . $row['about'] . '</p></div>';
            $bufDate = strtotime($row['add_date']);
            if ($bubbleDate < $bufDate) {
                $bubbleDate = $bufDate;
                $lmDate     = date('D, d M Y H:i:s', $bubbleDate);
            }
        }

        $title = 'Список вузов России - навигатор абитуриента ' . date("Y");
        $desc  = 'Все вузы России (университеты и институты) с действующими лицензиями, поиск вузов по специальностям, с бюджетными местами, общежитием и военной кафедрой, рейтинги, отзывы';
        $kw    = 'вузы России список высшие учебные заведения университеты институты высшее образование специальности бюджетные места проходные баллы';

        //all RUSSIA vuses
        $canonicalUrl = '//vuz' . DOMAIN;
        $tpl->start('tpl/main.html');
        $tpl->replace([
            '[head]'            => get_head(
                $title,
                $desc,
                $kw,
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>',
                $canonicalUrl
            ),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[graph]' => $graph,
            '[arts]'  => $arts,
            '[jour]'  => $jour,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads4]' => $ads[4],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[quiz]'   => file_get_contents('tpl/quiz.html'),
            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        header("Last-Modified: " . $lmDate . " GMT");
        $tpl->out();
    }

    static function thanks($u_id)
    {
        global $tpl;
        $kw    = '';
        $desc  = '';
        $title = '';

        $tpl->start('tpl/thanks.html');
        $tpl->replace([
            '[head]'            => get_head(
                $title,
                $desc,
                $kw,
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>'
            ),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),
            '[footer]'          => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function limit($u_id)
    {
        global $tpl;
        $kw    = '';
        $desc  = '';
        $title = '';

        $tpl->start('tpl/limit.html');
        $tpl->replace([
            '[head]'            => get_head(
                $title,
                $desc,
                $kw,
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>'
            ),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),
            '[footer]'          => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function professii($u_id)
    {
        global $tpl;
        $tpl->start('tpl/professii.html');
        $kw    = '';
        $desc  = '';
        $title = '';
        $tpl->replace([
            '[head]'   => get_head(
                $title,
                $desc,
                $kw,
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>'
            ),
            '[roof]'   => get_roof(),
            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function profTest($u_id)
    {
        global $tpl;
        $kw    = '';
        $desc  = '';
        $title = '';

        $tpl->start('tpl/prof-test.html');
        $scripts = '
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css"/>
            <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css">
            ' . Domain::generateTplDomain();
        $tpl->replace([
            '[head]'   => get_head($title, $desc, $kw, $scripts),
            '[roof]'   => get_roof(),
            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function professiiArticle($u_id)
    {
        global $tpl;
        $kw = $desc = $title = '';

        $tpl->start('tpl/profession-articles.html');
        $tpl->replace([
            '[head]'   => get_head(
                $title,
                $desc,
                $kw,
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>' . Domain::generateTplDomain()
            ),
            // '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'   => get_roof(),
            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    /**
     * @param $u_id
     */
    static function catalog($u_id)
    {
        if ($_SERVER['REQUEST_URI'] === '/99/' || $_SERVER['REQUEST_URI'] === '/99/phd/') {
            myErr::err404();
        }
        $subjectId = intval($_GET['subject']);

        if (!$subjectId) {
            myErr::err404();
        }

        if (isset($_GET['city'])) {
            $cityId = intval($_GET['city']);
            if (!$cityId) {
                myErr::err404();
            }
        } else {
            $cityId = false;
        }

        if ($_GET['spec'] === '0') {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'), true, 301);
            die;
        }

        /* MOD REWRITE GET PARAMS REMOVE */
        $_SERVER["QUERY_STRING"] = preg_replace(
            ['/subject=\d+&*/', '/city=\d+&*/', '/sort=\w+/', '/lvl=\w+/'],
            '',
            $_SERVER["QUERY_STRING"]
        );
        $_SERVER["QUERY_STRING"] = str_replace("&", "&amp;", $_SERVER["QUERY_STRING"]);

        /*
        $get_params = [
            'form' => $_GET['form'],
            'spec' => $_GET['spec'],
            'lvl'  => $_GET['lvl'],
            'form' => $_GET['form'],
        ];
        */

        if ($_GET['smlog'] && $_GET['smlog'] == 'wonnaplay' && AuthHelper::isSuperAdmin()) {
            $dbg_log = true;
        } else {
            $dbg_log = false;
        }
        require_once 'general_catalog.php';
        general_catalog::catalog_build($u_id, $subjectId, $cityId, $_GET, $dbg_log);
        if ($dbg_log) {
            echo 'data at log';
        }  else {
            echo 'no log added';
        }
    }

    static function numberof($number, $value, $suffix)
    {
        // не будем склонять отрицательные числа
        $number     = abs($number);
        $keys       = [2, 0, 1, 1, 1, 2];
        $mod        = $number % 100;
        $suffix_key = $mod > 4 && $mod < 20 ? 2 : $keys[min($mod % 10, 5)];

        return $value . $suffix[$suffix_key];
    }

    static function ege(int $userId)
    {
        global $tpl, $db;

        $db->hideErr = 0;
        $subjectId   = intval($_GET['subject']);
        if (!$subjectId) {
            myErr::err404();
        }
        $nav = '';
        if ($subjectId === 77) {
            $rp      = 'Москвы';
            $metro   = '';
            $city_id = 26;
        } elseif ($subjectId === 78) {
            $rp      = 'Санкт-Петербурга';
            $metro   = ' spb';
            $city_id = 44;
        } else {
            $msk     = false;
            $city_id = intval($_GET['city']);
            if (!in_array($city_id, [32, 11, 30, 14, 58, 33, 43, 40, 54, 21, 37, 10, 8, 20, 45, 52])) {
                myErr::err404();
            }
            $db->query(
                'SELECT `subjects`.`id`, `subjects`.`rp` AS subj, a.`rp` AS city 
				FROM 
					(SELECT `subject_id`, `rp` FROM `general`.`cities` WHERE `id` = ?) a LEFT JOIN 
					`general`.`subjects` ON a.`subject_id`=`subjects`.`id`',
                $city_id
            );
            $row = $db->get_row();
            $rp  = $row['city'];
            $nav .= '<a href="/' . $row['id'] . '/">Вузы ' . $row['subj'] . '</a> <a href="/' . $row['id'] . '/' . $city_id . '/">Вузы ' . $rp . '</a>';
        }
        if (!$nav) { // Msk || Spb
            $msk = true;
            $nav .= ' <a href="/' . $subjectId . '/">Вузы ' . $rp . '</a>';
        }

        $egeF    = ''; // field 4 filter
        $exShort = $exLong = $finded = '';
        $ads     = [1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => ''];
        if (isset($_GET['form']) && $form =& $_GET['form']) {
            if (!preg_match('/^\d+$/', $form)) {
                myErr::err404();
            }
            if ($form !== '1' && $form !== '2' && $form !== '3' && $form !== '4') {
                myErr::err404();
            }

            ads::get($ads, $subjectId, 4);
            switch ($form) {
                case 1:
                    $formO  = ' selected="selected"';
                    $formOZ = $formZ = $formD = "";
                    break;
                case 2:
                    $formOZ = ' selected="selected"';
                    $formO  = $formZ = $formD = "";
                    break;
                case 3:
                    $formZ = ' selected="selected"';
                    $formO = $formOZ = $formD = "";
                    break;
                case 4:
                    $formD = ' selected="selected"';
                    $formO = $formOZ = $formZ = "";
                    break;
            }

            if (!isset($_GET['exam']) || !is_array($_GET['exam'])) {
                myErr::err404();
            }

            $exams = '';
            array_unique($_GET['exam']);
            foreach ($_GET['exam'] as $exam) //				if($exam > 1 && $exam < 12) {
            {
                if ($exam > 0 && $exam < 12) {
                    $exams .= $exam . ',';
                    $egeF  .= '<input type="hidden" name="exam[]" value="' . $exam . '" />';
                }
            }

            if (!$exams) {
                header('Location: https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'), true, 301);
                die;
            }
            $exams = substr($exams, 0, -1);

            $min_sum = 0;
            $db->query("SELECT `name`, `short`, `min` FROM `general`.`ege_exams` WHERE id IN($exams)");
            //            $db->query("SELECT `name`, `short`, `min` FROM `general`.`ege_exams` WHERE id IN($exams)");
            $max_sum = $db->num_rows() * 100;
            while ($row = $db->get_row()) {
                $exLong  .= $row['name'] . ', ';
                $exShort .= $row['short'] . ', ';
                $min_sum += $row['min'];
            }
            $exLong  = substr($exLong, 0, -2);
            $exShort = substr($exShort, 0, -2);

            $exDiv = '
				<div id="exams-selected" data-score="1">' . EgeHelper::getExDiv() . '</div>';

            if (isset($_GET['ege-min']) && isset($_GET['ege-max']) && $_GET['ege-min'] && $_GET['ege-max']) {
                $egeMin = (int)abs($_GET['ege-min']);
                $egeMax = (int)abs($_GET['ege-max']);
            } else {
                $egeMin = round(($max_sum - $min_sum) / 2) + $min_sum - 45;
                $egeMax = round(($max_sum - $min_sum) / 2) + $min_sum + 45;
            }
            $score = '`specs`.`f_score` BETWEEN ' . $egeMin . ' AND ' . $egeMax . ' ';
            if ($_GET['only-free'] === '1') {
                $score .= ' AND ';
            } else {
                $freeCh = '';
                $score  = '(' . $score . ') OR (`specs`.`p_score` BETWEEN ' . $egeMin . ' AND ' . $egeMax . ') AND ';
            }

            if ($userId) { // compare marks
                $sqlSEL  = ' c.favor, ';
                $sqlFROM = ' LEFT JOIN (SELECT `spec_id` AS favor FROM `favor` WHERE `u_id`=' . $userId . ' AND `s`="0") c ON s.`spec_id`=c.favor';
            } else {
                $sqlSEL = $sqlFROM = '';
            }
            /*
            $SQL = '
                SELECT
                        v.*, `metros`.`name` AS metro,
                        IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id, round(avg(vs.free)) as budg_places,
                        IFNULL(o.ops, 0) AS ops, IFNULL(sp.specs, 0) AS specs,
                        round(avg(vs.f_score)) as budg_scores, round(avg(vs.p_score)) as payed_scores, round(avg(vs.f_cost)) as avg_price,
                        IF(v.`packet`="sert" OR v.`partner`="1", RAND(), 0) AS randseed
                FROM (
                        SELECT
                                `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`subj_id`, `vuzes`.`city_id`,
                                `vuzes`.`gos`, `vuzes`.`hostel`, `vuzes`.`military`, `vuzes`.`vedom`,
                                `vuzes`.`metro_id`, `vuzes`.`rating`, `vuzes`.`noAbitur`, `vuzes`.`ege`,
                                `vuzes`.`short_seo`,`vuzes`.`logo`, `vuzes`.`esi`, vds.name as dir_name,
                                IF(`vuzes`.`packetEnd`>DATE(NOW()), "sert", "") AS packet, `vuzes`.`partner`
                        FROM `vuz`.`vuzes`

                        LEFT JOIN vuz.vuz2direct vvd on vvd.vuz_id = vuzes.id
                        LEFT JOIN vuz.dir2specs vds on vds.id = vvd.dir_id
                        WHERE '.$vWHERE.' AND `vuzes`.`delReason`=""
                ) v
                LEFT JOIN vuz.specs vs on vs.vuz_id = v.id
                LEFT JOIN ';
            */


            // SQL_NO_CACHE SQL_CALC_FOUND_ROWS
            $SQL = '
                SELECT SQL_CALC_FOUND_ROWS
                    v.*, `metros`.`name` AS metro, 
                    IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
                    IFNULL(o.ops, 0) AS ops,
                    ' . $sqlSEL . '
                    s.*, x.`name` AS spec_name,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(e.`short`, IF(b.`sel` = "1", " или ", ", ")) 
                            ORDER BY b.`sel`, e.`id` SEPARATOR " "
                    ) AS exams
                FROM (										
                    SELECT 
                        `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`subj_id`, `vuzes`.`city_id`, 
                        `vuzes`.`gos`, `vuzes`.`hostel`, `vuzes`.`military`, `vuzes`.`vedom`, vds.name as dir_name,
                        `vuzes`.`metro_id`, `vuzes`.`rating`, `vuzes`.`noAbitur` ,`vuzes`.`logo`, `vuzes`.`esi`,
                        IF(`vuzes`.`packetEnd`>DATE(NOW()), "sert", "") AS packet, `vuzes`.`partner`
                    FROM  `vuzes` 
                    LEFT JOIN vuz.vuz2direct vvd on vvd.vuz_id = vuzes.id
                    LEFT JOIN vuz.dir2specs vds on vds.id = vvd.dir_id                         

                    WHERE 
                        `vuzes`.`city_id`=? AND `vuzes`.`delReason`="" AND `vuzes`.`noAbitur`=""
                        ) v 
                    LEFT JOIN 
                        (
                                SELECT 
                                        `specs`.`id` AS spec_id, `specs`.`vuz_id`, `specs`.`okso_id`, `specs`.`free`, `specs`.`form`, 
                                        `specs`.`f_score`, `specs`.`p_score`, `specs`.`f_cost`, `specs`.`internal_exam`, `specs`.`prof`
                                FROM `specs` 
                                WHERE ' . $score . ' `form` = ? AND `f` = "1"
                        ) s ON s.`vuz_id`= v.`id` 
                    LEFT JOIN
                        (SELECT `exam_id`, `spec_id`, `sel` FROM `spec2exams`) b ON b.`spec_id`=s.`spec_id` 
                    LEFT JOIN
                        (SELECT `id`, `short` FROM `general`.`ege_exams`) e ON e.`id`=b.`exam_id` 
                    LEFT JOIN
                        ( 
                            SELECT `id`, `name` FROM `okso` WHERE MOD(FLOOR(code/100), 10)!=4 
                        ) x ON x.`id`=s.`okso_id` ' . $sqlFROM . ' 
                    LEFT JOIN
                        `user2vuz` ON `user2vuz`.`vuz_id`=v.`id` 
                    LEFT JOIN
                        `general`.`metros` ON `metros`.`id`=v.`metro_id` 
                    LEFT JOIN (
                        SELECT `vuz_id`, count(*) AS ops 
                        FROM `vuz`.`opinions`
                        WHERE `approved`="1"
                        GROUP BY `vuz_id`
                        ) o ON o.`vuz_id` = v.`id`
                WHERE 
                    s.`spec_id` IS NOT NULL AND
                    (SELECT `spec_id` FROM `spec2exams` WHERE `spec2exams`.`spec_id`=s.`spec_id` AND sel="0" HAVING INSTR(?, GROUP_CONCAT(`exam_id` ORDER BY `exam_id`))) > 0 AND
                    (
                        (SELECT count(*) FROM `spec2exams` WHERE `spec2exams`.`spec_id`=s.`spec_id` AND sel="1") = 0 OR 
                        (SELECT count(*) FROM `spec2exams` WHERE `spec2exams`.`spec_id`=s.`spec_id` AND sel="1" AND `exam_id` IN (' . $exams . ')) > 0
                    )	
                GROUP BY s.`spec_id` ORDER BY `packet` DESC, v.`id`, `rating`, s.`okso_id` DESC';
            $r   = $db->query($SQL, $city_id, $form, $exams);

            $db->query('SELECT FOUND_ROWS() as c');
            $total = $db->get_row();
            $vuzes = $seo = $tail = '';
            $v_id  = 0;
            if ($db->num_rows($r)) {
                $finded = '<p id="finded">Найдено ' . $total['c'] . ' специальностей в вузах ' . $rp . ' с экзаменами ' . $exLong . '</p>';
                while ($vuz = $db->get_row($r)) {
                    $vuz['subj_id'] = intval($vuz['subj_id']);
                    $catPath        = '/' . $vuz['subj_id'] . '/' . (($msk) ? ('') : ($vuz['city_id'] . '/'));

                    if ($vuz['logo']) {
                        $logo_src = 'https://vuz.edunetwork.ru/files/' . $vuz['id'] . '/logo.' . $vuz['logo'];
                        //$logo_src = '/files/'.$vuz['id'].'/logo.'.$vuz['logo'];
                    } else {
                        $logo_src    = '//static.edunetwork.ru/imgs/tpl/noLogo.png" alt="Нет логотипа';
                        $row['logo'] = '<img src="//static.edunetwork.ru/imgs/tpl/noLogo.png" alt="Нет логотипа" />';
                    }

                    $esi = '';
                    if ($vuz['esi'] !== null) {
                        $vuz['esi'] = (int)$vuz['esi'];
                        if ($vuz['esi'] > 7) {
                            $esi = 'A';
                        } elseif ($vuz['esi'] > 3) {
                            $esi = 'B';
                        } else {
                            $esi = 'C';
                        }
                    }

                    if ($vuz['military'] === '1') {
                        $esi = '-';
                    }

                    $vuz['id'] = (int)$vuz['id'];
                    if ($v_id !== $vuz['id']) {
                        $vuzes .= $tail . '
						<div class="unit">
                        <div class="unit-inner">
                        <div class="card-image">
                            <a href="' . $catPath . 'v' . $vuz['id'] . '/">
                             <img alt="Логотип" itemprop="logo" src="' . $logo_src . '">
                            </a>
                            <div class="unit-esi">
                                <div>
                                    ESI
                                    <div class="unit-esi-popup">Внутренний рейтинг ВУЗов. Рассчитывается на основании формальных показателей деятельности из официальных источников.</div>
                                </div> 
                                <span>' . $esi . '</span>
                            </div> 
                        </div>
                        <div class="unit-wrapper">
							<p class="unit-name">
								<a href="' . $catPath . 'v' . $vuz['id'] . '/">' . $vuz['name'] . '</a>';
                        if ($vuz['packet'] === 'sert') {
                            $vuzes .= (($vuz['partner']) ? ('<span class="partner"></span>') : ('<span class="sert"></span>'));
                        } elseif ($vuz['u_id']) {
                            $vuzes .= '<span class="has-user"></span>';
                        }
                        $vuzes .= '</p>
                                    <ul class="unit-attrs">';
                        if ($vuz['gos']) {
                            $vuzes .= '<li>Государственный</li><li>С бюджетными местами</li>';
                        } else {
                            $vuzes .= '<li>Негосударственный</li>';
                            if ($vuz['id'] === 517 || $vuz['id'] === 189 || $vuz['id'] === 399) {
                                $vuzes .= '<li>С бюджетными местами</li>';
                            }
                        }

                        if ($vuz['hostel'] === '1') {
                            $vuzes .= '<li>С общежитием</li>';
                        }
                        if ($vuz['military'] === '1') {
                            $vuzes .= '<li>С военной кафедрой</li>';
                        }
                        if ($vuz['dir_name'] != '') {
                            $vuzes .= '<li>' . $vuz['dir_name'] . '</li>';
                        }

                        if ($vuz['free']) {
                            if ($vuz['free'] === '1' && $vuz['vedom']) {
                                $places = 'есть';
                            } else {
                                $places = $vuz['free'];
                            }
                        } else {
                            $places = '—';
                        }

                        if ($vuz['f_cost']) {
                            $payed_places = 'есть';
                        } else {
                            $payed_places = '-';
                        }

                        $budg_scores  = $vuz['f_score'] > 0 ? $vuz['f_score'] : '—';
                        $payed_scores = $vuz['p_score'] > 0 ? $vuz['p_score'] : '—';

                        $vuzes .= '</ul>
                        <div class="unit-place unit-block">
                            <span>Бюджетные места: ' . $places . '</span>
                            <span>Платные места: ' . $payed_places . '</span>
                        </div>
                        <div class="unit-middle unit-block">
                            <span>Средний балл ЕГЭ бюджет: ' . $budg_scores . '</span>
                            <span>Средний балл ЕГЭ платные места: ' . $payed_scores . '</span>
                        </div>
                                ';

                        $examsEge     = ' ' . preg_replace('/( или |, )$/u', '', $vuz['exams']);
                        $i_exam       = $vuz['internal_exam'] ? "Внутренний экзамен" : false;
                        $internalExam = $i_exam ? ". $i_exam" : '';

                        $tail = '
							<div class="row unit-stats">
								<div class="col s12 m6 l6 truncate">
								    <b>Экзамены ЕГЭ:</b> ' . $examsEge . $internalExam . '
								</div>
								<div class="col s12 m6 l6 truncate right-align">';
                        if ($vuz['metro']) {
                            $tail .= '<span class="metro' . $metro . '">' . $vuz['metro'] . '</span>';
                        }
                        $tail .= '		<span class="opins"><a href="' . $catPath . 'v' . $vuz['id'] . '/opinions/">Отзывы (' . $vuz['ops'] . ')</a></span>
								</div>
							</div>
						</div></div></div>';

                        $v_id = $vuz['id'];
                    }
                    /*
                     //Убрано в рамках соответствия EDUNET-379 серии 38/307/308/309/310
                    $vuzes .= '
					<div class="unit-spec">
						<p class="spec-name">
							<a href="' . $catPath . 'v' . $vuz['id'] . '/specs/#spec-' . $vuz['spec_id'] . '">' . $vuz['spec_name'] . '</a>
							<i class="material-icons spec-favor' . (($vuz['favor']) ? (' added') : ('')) . '" data-specid="' . $vuz['spec_id'] . '"></i>
						</p>';
                    if ($vuz['prof']) {
                        $vuzes .= '
							<div class="spec-profiles">
								<div class="truncate">
									<span class="hide-on-small-only">Профили: </span>' . $vuz['prof'] . '
								</div>
							</div>';
                    }
                    $vuzes .= '<div class="row spec-stats">';

                    if ($vuz['free']) {
                        if ($vuz['free'] === '1' && $vuz['vedom']) {
                            $vuzes .= '<div class="col s4 free">Бюджетные места: <span>есть</span></div>';
                        } else {
                            $vuzes .= '<div class="col s4 free">Бюджетных мест: <span>' . $vuz['free'] . '</span></div>';
                        }
                    } else {
                        $vuzes .= '<div class="col s4 nofree">Бюджетных мест: <span>—</span></div>';
                    }

                    if ($vuz['f_cost']) {
                        $vuzes .= '<div class="col s4 m4 l4 cost"><span>' . number_format($vuz['f_cost'], 0, ',', ' ') . '</span> рублей в год</div>';
                    } else {
                        $vuzes .= '<div class="col s4 m4 l4 cost">Коммерческих мест <span>нет</span></div>';
                    }
                    $vuzes .= '
                            </div>
                            <div class="row spec-stats">
                                    <div class="col s4 score">Проходной балл: <span>'.($vuz['f_score'] > 0 ? $vuz['f_score'] : '—').'</span></div>
                                    <div class="col s4 score">Проходной балл: <span>'.($vuz['p_score'] > 0 ? $vuz['p_score'] : '—').'</span></div>
                            </div>';
                    $vuzes.= '</div>';*/
                }
                $vuzes = '<div id="units-list">' . $vuzes . $tail . '</div>';
            } else {
//                $robots='<meta name="robots" content="none"/>';
                $robots = '<meta name="robots" content="noindex, nofollow" />';
                $vuzes  = '<div id="no-result"><p>Поиск</p><p>Ничего не найдено</p></div>';
            }

            if (!$robots) {
                $seo = '
					<h2>Куда можно поступить по ЕГЭ (' . $exLong . ') в вузы ' . $rp . '?</h2>
					<p class="ege-text">
						В результатах поиска показаны все специальности в вузах ' . $rp . ' с необходимым для поступления перечнем ЕГЭ (' . $exLong . '). 
						В контрольных цифрах приема указано количество бюджетных мест и стоимость обучения в ' . date(
                        "Y"
                    ) . ' году. 
						Минимальный проходной балл указан по сумме всех предметов на основании результатов приема в ' . (date(
                            "Y"
                        ) - 1) . ' году.
					</p>';
            }
        } else {
            ads::get($ads);

            $exDiv = EgeHelper::getExDiv();

            $seo  = '
				<p class="ege-text">
					Поиск специальностей в вузах ' . $rp . ' по предметам ЕГЭ, добавляйте направления подготовки в избранное и сравнивайте проходные баллы. 
					Выбирайте список ЕГЭ так, чтобы они требовались для поступления в вузы ' . $rp . ' именно в таком количестве и составе. 
					Среди вступительных испытаний для поступления в вуз всегда присутствует русский язык. Обычно для поступления требуется три экзамена ЕГЭ, но реже может быть от двух до четырех. 
					Для вашего удобства мы сделали возможным поиск по экзаменам в количестве от двух до четырех предметов, чтобы можно было найти любой из возможных вариантов. 
				</p>';
            $reps = '';
        }
        #h2_TEXT

        $h1    = 'Вузы ' . $rp . ' по ЕГЭ';
        $title = 'Вузы ' . $rp . ' по предметам ЕГЭ';
        $kw    = 'вузы университеты институты ' . $rp . ' поиск предмет ЕГЭ';
        $desc  = 'Вузы ' . $rp . ' (университеты и институты), поиск по предметам ЕГЭ ';
        if ($exLong) {
            $h1    .= ' (' . $exLong . ')';
            $title .= ': ' . $exLong;
            $kw    .= ' ' . $exLong;
            $desc  .= '(' . $exLong . ')';
        }
        $desc .= ', добавляйте специальности в избранное и сравнивайте проходные баллы.';
        $tpl->start('tpl/ege.html');
        $h2 = '';
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw, $robots),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[nav]' => $nav,

            '[exDiv]' => $exDiv,
            '[frmO]'  => $formO,
            '[frmOZ]' => $formOZ,
            '[frmZ]'  => $formZ,
            '[frmD]'  => $formD,

            '[egeMin]' => $egeMin,
            '[egeMax]' => $egeMax,
            '[sumMin]' => $min_sum,
            '[sumMax]' => $max_sum,

            '[egeF]' => $egeF,

            '[h1]' => $h1,
            '[h2]' => $h2,

            '[finded]' => $finded,
            '[vuzes]'  => $vuzes,

            '[seo]' => $seo,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads4]' => $ads[4],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html'),
        ]);
        $tpl->out();
    }

    static function main_search2()
    {
        global $db;

        $q = $_GET['q'];
        $q = preg_replace('/[^а-яА-ЯёЁ\d\s\-]/u', '', $q);

        if (!$q) {
            die('[]');
        }
        $limit = (($_GET['limit']) ? (min(intval($_GET['limit']), 10)) : (10));

        $out = '';
        $db->query(
            'SELECT
                `vuzes`.`id`, `vuzes`.`name`, `abrev`, `subj_id`, `city_id`, `cities`.`name` AS city, 
                IF(parent_id IS NULL, "v", "f") AS `type`, CHAR_LENGTH(`abrev`) AS len
            FROM 
                `vuz`.`vuzes` LEFT JOIN 
                `general`.`cities` ON `vuzes`.`city_id`=`cities`.`id`
            WHERE `abrev` LIKE CONCAT("%", ?, "%")  AND `delReason`="" ORDER BY `parent_id`, len LIMIT ' . $limit,
            $q
        );

        if ($db->num_rows()) {
            while ($row = $db->get_row()) {
                $out .= '
                            {
                                    "id":"' . $row['id'] . '",
                                    "value":"' . $row['name'] . ' (' . $row['abrev'] . ')",
                                    "category":"' . (($row['type'] == 'v') ? ('Вузы') : ('Филиалы')) . '",
                                    "subj":"' . $row['subj_id'] . '",
                                    "city":"' . $row['city_id'] . '",
                                    "abrev":' . json_encode($row['abrev']) . ',
                                    "city_name":"' . $row['city'] . '"
                            },';
            }
        } else {
            function cmp($a, $b)
            {
                if ($a[1]['type'] == $b[1]['type']) {
                    if ($a[0] < $b[0]) {
                        return (-1);
                    } else {
                        return (1);
                    }
                } else {
                    if ($a[1]['type'] < $b[1]['type']) {
                        return (1);
                    } else {
                        return (-1);
                    }
                }
            }

            require(base_path . "sphinx/php/sphinxapi.php");
            $sphinx = new SphinxClient();
            $sphinx->SetServer('localhost', 9312);
            $sphinx->SetLimits(0, 10);
            $sphinx->SetSortMode(SPH_SORT_RELEVANCE);
            $result = $sphinx->Query($q, 'vuzName');
            if ($result && isset($result['matches'])) {
                $ids = array_keys($result['matches']);

                # Do index
                # v_id=>array(relevance_order, vuz_row_from_db),
                $cnt = sizeof($ids);
                $arr = [];
                for ($i = 0; $i < $cnt; $i++) {
                    $arr[$ids[$i]] = [$i];
                }

                # Get vuz data
                $ids = implode(',', $ids);
                $db->query(
                    'SELECT 
                        `vuzes`.`id`, `vuzes`.`name`, `abrev`, `subj_id`, `city_id`, `cities`.`name` AS city, 
                        IF(parent_id IS NULL, "v", "f") AS `type`
                    FROM `vuz`.`vuzes` LEFT JOIN `general`.`cities` ON `vuzes`.`city_id`=`cities`.`id`
                    WHERE `vuzes`.`id` IN (' . $ids . ') AND `delReason`="" ORDER BY `parent_id`'
                );
                while ($row = $db->get_row()) {
                    $arr[$row['id']][1] = $row;
                }

                # Sort by type, relevance
                uasort($arr, 'cmp');

                foreach ($arr as $v_id => $val) {
                    $out .= '
                        {
                            "id":' . $v_id . ',
                            "value":"' . $val[1]['name'] . ' (' . $val[1]['abrev'] . ')",
                            "category":"' . (($val[1]['type'] == 'v') ? ('Вузы') : ('Филиалы')) . '",
                            "subj":"' . $val[1]['subj_id'] . '",
                            "city":"' . $val[1]['city_id'] . '",
                            "abrev":"' . $val[1]['abrev'] . '",
                            "city_name":"' . $val[1]['city'] . '"
                        },';
                }
            }
        }

        if ($out) {
            $out = substr($out, 0, -1);
        }

        echo '[' . $out . ']';
    }

    static function placeSearch()
    {
        global $db;

        $q = preg_replace('/[^а-яА-ЯёЁ\d\s\-]/u', '', $_GET['q']);
        if (!$q) {
            die('[]');
        }

        if ($_GET['limit']) {
            $limit = min(intval($_GET['limit']), 10);
        } else {
            $limit = 10;
        }

        $out = '';
        $db->query(
            '
      SELECT `cities`.`id`, `cities`.`name` AS city, `subject_id`, `subjects`.`name` AS subj, CHAR_LENGTH(`cities`.`name`) AS len 
      FROM `general`.`cities` LEFT JOIN `general`.`subjects` ON `subjects`.`id`=`subject_id`
      WHERE `cities`.`name` LIKE "%' . $q . '%" ORDER BY len LIMIT ' . $limit
        );
        if ($db->num_rows()) {
            while ($row = $db->get_row()) {
                if ($row['id'] == 26 || $row['id'] == 44) {
                    $row['subj'] = '';
                }
                $out .= '{"id":' . $row['id'] . ', "value":"' . $row['city'] . '", "subj":"' . $row['subj'] . '", "subj_id": ' . $row['subject_id'] . '},';
            }
            $out = substr($out, 0, -1);
        }
        echo '[' . $out . ']';
    }

    static function oksoSearch()
    {
        global $db;

        $lvl = $_GET['lvl'];
        switch ($lvl) {
            case 'p':
                $sql = 'MOD(FLOOR(`okso`.`code`/100), 10) = 6';
                break;
            case 'm':
                $sql = 'MOD(FLOOR(`okso`.`code`/100), 10) = 4';
                break;
            case 'f':
            case 's':
                $sql = 'MOD(FLOOR(`okso`.`code`/100), 10) != 4';
                break;
            default:
                die("[]");
                break;
        }

        $q = $_GET['q'] . $_GET['term'];
        $q = preg_replace('/[^а-яА-ЯёЁ\d\s\-]/u', '', $q);
        if (!$q) {
            die('[]');
        }

        $limit = (($_GET['limit']) ? (min(intval($_GET['limit']), 10)) : (10));;

        $out = '';
        $db->query(
            'SELECT `id`, `name` FROM `okso` WHERE ' . $sql . ' AND `name` LIKE CONCAT("%",?,"%") LIMIT ' . $limit,
            $q
        );
        if ($db->num_rows()) {
            while ($row = $db->get_row()) {
                $out .= '{"id":"' . $row['id'] . '",  "value":"' . $row['name'] . '"},';
            }
            $out = substr($out, 0, -1);
        }

        echo '[' . $out . ']';
    }
}
