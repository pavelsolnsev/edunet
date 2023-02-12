<?php

class vuz_faculties
{
    static function show($u_id)
    {
        global $db, $tpl;

        $HT_BR = '<br>';
        $id =& $_GET['vuz'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::err404();
        }
        //echo $id;
        if (isset($_GET['lvl']) || isset($_GET['form'])) {
            header(
                'Location: https://'.$_SERVER['HTTP_HOST'].preg_replace('/\/\?.+/', '', $_SERVER['REQUEST_URI']).'/',
                true,
                301
            );
            die;
        }
        $id = (int)$id;

        $sql = '
            SELECT 
                a.*, 
                `dir2specs`.`id` AS dir_id, `dir2specs`.`name` AS dir_name,
                `subjects`.`name` as subject, `subjects`.`rp` as subj_rp,
                `cities`.`name` as city, `cities`.`rp` as city_rp, `cities`.`type`, `metros`.`name` AS metro,
                IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
                (SELECT IF(COUNT(*)>0, 1, 0) FROM `vuz`.`subvuz` WHERE a.`id`=`subvuz`.`vuz_id` LIMIT 2) AS subunits,
                (SELECT COUNT(*) FROM `vuz`.`opinions` WHERE a.`id`=`opinions`.`vuz_id` AND `approved`="1") AS opins,
                (SELECT 1 FROM `vuz`.`specs` WHERE `specs`.`vuz_id`=a.`id` LIMIT 1) AS spec
            FROM 
                (
                    SELECT
                            `vuzes`.`id`, `vuzes`.`editTime`,
                            `vuzes`.`name`, `vuzes`.`abrev`, `vuzes`.`logo`,
                            `vuzes`.`gos`, `vuzes`.`hostel`, `vuzes`.`military`, `vuzes`.`vedom`,
                            `vuzes`.`subj_id`, `vuzes`.`city_id`, `vuzes`.`metro_id`,
                            IF(`vuzes`.`packetEnd`>DATE(NOW()), "sert", "") AS packet,
                            IF(`vuzes`.`partner`, (SELECT CONCAT(`text`, "|", `textColor`, "|", `bgColor`) FROM `vuz`.`vuz_events` WHERE `vuz_id`=`vuzes`.`id` AND `start`<=NOW() AND `end`>NOW() LIMIT 1), "") AS event, 
                            `vuzes`.`partner`, `vuzes`.`textColor`, `vuzes`.`promo`, 
                            `vuzes`.`noAbitur`, `vuzes`.`esi`, `vuzes`.`parent_id`, `vuzes`.`acr_num`
                    FROM `vuz`.`vuzes` WHERE `vuzes`.`id` = '.$id.'
                ) a 
                LEFT JOIN `general`.`subjects` ON a.`subj_id`=`subjects`.`id` 
                LEFT JOIN `general`.`cities` ON a.`city_id`=`cities`.`id`
                LEFT JOIN `general`.`metros` ON a.`metro_id`=`metros`.`id` 
                LEFT JOIN `vuz`.`vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id` 
                LEFT JOIN `vuz`.`user2vuz` ON a.`id`=`user2vuz`.`vuz_id` 
                LEFT JOIN `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`';

        //echo $sql.$HT_BR;

        $db->query($sql);
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $vuz  = $db->get_row();

        //echo json_encode($vuz, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).$HT_BR;

        $vuz['h1_text'] = 'Факультеты '.$vuz['name'].' '.$vuz['abrev'];
        $head = vuz::get_head($vuz, true, true);

        if ($vuz['delReason']) {
            myErr::err404();
        }

        if ($vuz['editTime']) {
            $lmDate = date('D, d M Y H:i:s', strtotime($vuz['editTime']));
            header("Last-Modified: " . $lmDate . " GMT" );
        }

        if ($u_id) { // compare marks
            $sqlSEL  = ', c.favor, c.fav_s';
            $sqlFROM = ' LEFT JOIN (SELECT `spec_id` AS favor, `s` AS fav_s FROM `favor` WHERE `u_id`='.$u_id.') c ON a.`id`=c.favor';
        }

        if ($vuz['parent_id']) {
            $db->query('SELECT `acr_num` FROM `vuz`.`vuzes` WHERE `id`=? AND `acr_num` IS NOT NULL', $vuz['parent_id']);
            if ($db->num_rows()) {
                $vuz['acr_num'] = true;
            }
        }
        if ($vuz['acr_num']) {
            $t  = 'IFNULL(z.`code`, 0)';
            $t1 = '(SELECT `acr_okso`.`code` FROM `acr_okso` WHERE `acr_okso`.`vuz_id`='.($vuz['parent_id'] ? $vuz['parent_id'] : $id).') z ON z.code=FLOOR(`okso`.`code`/100) LEFT JOIN';
        } else {
            $t  = '0';
            $t1 = '';
        }
        $specs = '';
        $sv_id = 0;
        $items = 0;

        $sql = '
            SELECT * FROM vuz.vuzes as vv 
            LEFT JOIN  vuz.subvuz as vs on vs.vuz_id = vv.id
            WHERE vv.id = '.$id;


        $sql = '
            SELECT 
                    a.*, b.*, `okso`.`name`, `okso`.`code`, MOD(ROUND(`okso`.`code`/100), 10) AS lvl, `edu_periods`.`display`,
                    '.$t.' AS accr,
                    GROUP_CONCAT(
                            DISTINCT CONCAT(`ege_exams`.`short`, IF(`spec2exams`.`sel` = "1", " или ", ", ")) 
                            ORDER BY `spec2exams`.`sel`, `ege_exams`.`id` SEPARATOR " "
                    ) AS exams
                    '.$sqlSEL.'
            FROM (
                    SELECT 
                            `id`, `subvuz_id`, `free`, `form`, `f_score`, `p_score`, `f`, `s`, `prof`,
                            `internal_exam`,
                            `f_cost`,  `f_adv`,  
                            `s_cost`,  `s_adv`,  
                            `m_twin`, `m_lang`, YEAR(`specs`.`lastEdit`) AS upd_year,
                            `okso_id`, `period` 
                    FROM `specs` WHERE `vuz_id` = '.$id.'
                ) a LEFT JOIN 
                `okso` ON a.`okso_id` = `okso`.`id` LEFT JOIN 
                '.$t1.'
                `spec2exams` ON a.`id`=`spec2exams`.`spec_id` LEFT JOIN
                `general`.`ege_exams` ON `ege_exams`.`id`=`spec2exams`.`exam_id` LEFT JOIN
                `general`.`edu_periods` ON a.`period` = `edu_periods`.`id` LEFT JOIN
                (
                        SELECT `id` AS sv_id, `name` AS sv, `address` 
                        FROM `subvuz` WHERE `vuz_id`= '.$id.'
                ) b ON b.`sv_id`=a.`subvuz_id`	
                '.$sqlFROM.'
            GROUP BY a.`id` ORDER BY b.sv_id, `okso`.`code`';


        $db->query($sql);

        //echo $sql.$HT_BR;


        if ($db->num_rows()) {
            if (!$vuz['subunits'] ) {
                //echo 'wow'.'<br>';
                $specs .= '<p class="specs-title">Факультеты:</p>';
            }
            $i = 0;
            $items = 0;
            while ($spec = $db->get_row()) {
                if ($i < 1) {
                    //echo json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
                    $i++;
                }

                if ($vuz['subunits']) {
                    if ($spec['sv_id'] !== $sv_id) {
                        $sv_id = $spec['sv_id'];
                        if ($sv_id && $vuz['subunits']) {
                            $specs .= '</section>';
                        }
                        $items++;
                        $specs .= '
                            <section class="subunit" itemprop="department" itemscope itemtype="http://schema.org/Organization">
                                <header>
                                    <h2 itemprop="name"><a href="'.$spec['sv_id'].'/">'.$spec['sv'].'</a></h2>
                                    <p class="address" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                                        <meta itemprop="addressCountry" content="RU" />
                                        <meta itemprop="addressRegion" value="'.$vuz['subject'].'" />
                                        <meta itemprop="addressLocality" value="'.$vuz['city'].'" />
                                        <meta itemprop="streetAddress" value="'.$spec['address'].'" />';
                        if ($vuz['subj_id'] !== 77 && $vuz['subj_id'] !== 78) {
                            $specs .= $vuz['subject'].', ';
                        }
                        if ($id !== 410 && $id !== 175) {
                            $specs .= $vuz['type'].'&nbsp;'.$vuz['city'].', ';
                        }
                        $specs .= $spec['address'].'
                                    </p>
                                </header>';
                    }
                }
                else {
                    $sv_id = $spec['sv_id'];
                }

                switch ($spec['form']) {
                    case '1':
                        $form = 'очно';
                        break;
                    case '2':
                        $form = 'очно-заочно';
                        break;
                    case '3':
                        $form = 'заочно';
                        break;
                    case '4':
                        $form = 'дистанционно';
                        break;
                }

                switch ($spec['lvl']) {
                    case '3':
                        $lvl = 'бакалавриат';
                        break;
                    case '4':
                        $lvl = 'магистратура';
                        break;
                    case '5':
                        $lvl = 'специалитет';
                        break;
                    case '6':
                        $lvl = 'аспирантура';
                        break;
                }

                $new_url = '//'.$_SERVER['HTTP_HOST'].preg_replace('/\/\?.+/', '', $_SERVER['REQUEST_URI']);
                $new_url = str_replace('/specs','/sv'.$sv_id.'/s'.$spec['id'].'/spec',$new_url);

                $new_url = '<a href="'.$new_url.'">'.$spec['name'].' </a>';
                $no_url  = $spec['name'];
                // $new_url = $no_url;

            }

            if ($vuz['subunits']) {
                //$items = count($vuz['subunits']);
                $specs .= '</section>'
                ;
            }
        } else {
            myErr::err404();
        }

        $title = 'Факультеты ' .$vuz['abrev']. ' ' . date("Y") .'';
        $desc = 'Факультеты '.$vuz['name'].' ('.$vuz['abrev'].'): бюджетные и коммерческие места, проходной балл ЕГЭ, стоимость обучения. Информация о поступлении на факультеты '.$vuz['abrev'].'';
        $kw    = $vuz['name'].' '.$vuz['abrev'].' специальности бакалавриат проходной балл ЕГЭ стоимость обучения';

        $nav = vuz::get_nav($vuz, 'faculties');

        /* ADS */
        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        if (!$vuz['packet']) {
            ads::get($ads, $vuz['subj_id']);
        }
        $basePath     = '/' . $vuz['subj_id'] . '/' . (($vuz['subj_id'] != 77 && $vuz['subj_id'] != 78) ? ($vuz['city_id'] . '/') : ('')).'v'.$vuz['id'].'/faculties/';
        $canonicalUrl = '//vuz'.DOMAIN.$basePath;
        $home         = HOME.'vuz.edunetwork.ru/';

        $tpl->start($home."tpl/faculties.html");

        $quiz = file_get_contents('tpl/quiz.html');
        $quiz = str_replace('[vuz_id]', $vuz['id'], $quiz);

        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw, '', $canonicalUrl),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[navbar]' => $head['nav'],
            '[header]' => $head['header'],

            '[nav]' => $nav,

            '[specs]' => $specs,

            '[ylead]' => vuz::leads_form($vuz['id']),
            '[ads1]'  => $ads[1],
            '[ads2]'  => $ads[2],
            '[ads3]'  => $ads[3],
            '[ads5]'  => $ads[5],
            '[ads6]'  => $ads[6],
            '[ads7]'  => $ads[7],
            '[quiz]'  => $quiz,
            '[footer]'=> file_get_contents('tpl/footer.html'),
        ]);
        $tpl->out();
    }
}