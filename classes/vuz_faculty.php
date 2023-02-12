<?php

class vuz_faculty
{
    static function show($u_id = 0, $v_id = 0, $f_id = 0)
    {
        global $db, $tpl;

        $id =& $_GET['vuz'];

        if ($v_id > 0) {
            $id = $v_id;
        } else {
            if(!preg_match('/^\d+$/',$id)) {
                myErr::err404();
            }
            if (!preg_match('/^\d+$/', $f_id)) {
                myErr::err404();
            }
        }

        if (isset($_GET['lvl']) || isset($_GET['form'])) {
            header(
                'Location: https://'.$_SERVER['HTTP_HOST'].preg_replace('/\/\?.+/', '', $_SERVER['REQUEST_URI']).'/',
                true,
                301
            );
            die;
        }
        $id = (int)$id;
        $db->query('
            SELECT 
                a.*, 
                `dir2specs`.`id` AS dir_id, `dir2specs`.`name` AS dir_name,
                `subjects`.`name` as subject, `subjects`.`rp` as subj_rp,
                `cities`.`name` as city, `cities`.`rp` as city_rp, `cities`.`type`, `metros`.`name` AS metro,
                IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
                (SELECT IF(COUNT(*)>1, 1, 0) FROM `vuz`.`subvuz` WHERE a.`id`=`subvuz`.`vuz_id` LIMIT 2) AS subunits,
                (SELECT COUNT(*) FROM `vuz`.`opinions` WHERE a.`id`=`opinions`.`vuz_id` AND `approved`="1") AS opins
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
                    FROM `vuz`.`vuzes` WHERE `vuzes`.`id` = ?
                ) a 
                LEFT JOIN `general`.`subjects` ON a.`subj_id`=`subjects`.`id` 
                LEFT JOIN `general`.`cities` ON a.`city_id`=`cities`.`id` 
                LEFT JOIN `general`.`metros` ON a.`metro_id`=`metros`.`id` 
                LEFT JOIN `vuz`.`vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id` 
                LEFT JOIN `vuz`.`user2vuz` ON a.`id`=`user2vuz`.`vuz_id` 
                LEFT JOIN`vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`
        ',
            $id
        );
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $vuz  = $db->get_row();

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
        $items = 0;
        $sv_id = $f_id;
        $items = 0;
        $db->query(
            'SELECT 
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
                    FROM `specs` WHERE `vuz_id` = ? AND `subvuz_id` = '.$sv_id.' 
                ) a LEFT JOIN 
                `okso` ON a.`okso_id` = `okso`.`id` LEFT JOIN 
                '.$t1.'
                `spec2exams` ON a.`id`=`spec2exams`.`spec_id` LEFT JOIN
                `general`.`ege_exams` ON `ege_exams`.`id`=`spec2exams`.`exam_id` LEFT JOIN
                `general`.`edu_periods` ON a.`period` = `edu_periods`.`id` LEFT JOIN
                (
                        SELECT `id` AS sv_id, `name` AS sv, `address` 
                        FROM `subvuz` WHERE `vuz_id`=?
                ) b ON b.`sv_id`=a.`subvuz_id`	
                '.$sqlFROM.'
            GROUP BY a.`id` ORDER BY b.sv_id, `okso`.`code`',
            $id,
            $id
        );

        $sv_id = 0;
        $faculty = [];

        $spec = [];
        if ($db->num_rows()) {
            if (!$vuz['subunits']) {
                $specs .= '<p class="specs-title">Факультеты:</p>';
            }

            while ($spec = $db->get_row()) {
                if ($vuz['subunits']) {
                    if ($spec['sv_id'] !== $sv_id) {
                        $faculty['sv'] = $spec['sv'];

                        $sv_id = $spec['sv_id'];
                        if ($sv_id && $vuz['subunits']) {
                            $specs .= '</section>';
                        }
                        $specs .= '
                            <section class="subunit" itemprop="department" itemscope itemtype="http://schema.org/Organization">';

                        $specs .= '<h2 class="faculty-title">Программы обучения факультета</h2>';
                    }
                } else {
                    $faculty['sv'] = $spec['sv'];
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

                $new_url = $_SERVER['HTTP_HOST'].preg_replace('/\/\?.+/', '', $_SERVER['REQUEST_URI']);
                //echo $new_url.PHP_EOL;

                $new_url = str_replace('/'.'v'.$vuz['id'].'/faculties/'.$spec['subvuz_id'],'',$new_url);
                //echo $new_url.PHP_EOL.PHP_EOL;
                //http://vuz.edunetwork.loc/77/v366/sv7623/s130541/spec/
                if ($vuz['subj_id'] == 77 || $vuz['subj_id'] == 78) {
                    //$new_url .= $vuz['subj_id'].'/'.'v'.$vuz['id'].'/sv'.$spec['subvuz_id'].'/s'.$spec['id'].'/spec';
                    //$new_url .= 'v'.$vuz['id'].'/sv'.$spec['subvuz_id'].'/s'.$spec['id'].'/spec/';
                    $new_url .= 'v'.$vuz['id'].'/sv'.$spec['subvuz_id'].'/s'.$spec['okso_id'].'/';

                } else {
                    //$new_url .= $vuz['subj_id'].'/'.['city_id'].'/'.'v'.$vuz['id'].'/sv'.$spec['subvuz_id'].'/s'.$spec['id'].'/spec';
                    //$new_url .= ['city_id'].'/'.'v'.$vuz['id'].'/sv'.$spec['subvuz_id'].'/s'.$spec['id'].'/spec/';
                    $new_url .= ['city_id'].'/'.'v'.$vuz['id'].'/sv'.$spec['subvuz_id'].'/s'.$spec['okso_id'].'/';
                }
                //echo $new_url.PHP_EOL;

                $new_url = '<a href="//'.$new_url.'">'.$spec['name'].' </a>';

                $no_url  = $spec['name'];
                // $new_url = $no_url;

                if ($spec['lvl'] === '4' || $spec['f'] === '1') {
                    $items++;
                    $specs .= '
                                            <div class="unit-spec" data-lvl="'.(($spec['lvl'] === '4') ? ('m') : ('f')).'" data-form="'.$spec['form'].'">
                                                    <a name="spec-'.$spec['id'].'"></a>
                                                    <p class="name">
                                                            '.$new_url.'
                                                            <span>('.substr_replace(
                            substr_replace($spec['code'], '.', 2, 0),
                            '.',
                            5,
                            0
                        ).' – '.$form.', '.$lvl.', 
                                                            '.($spec['accr'] ? 'аккредитовано' : '<span class="no-accr">неаккредитовано <a href="/faq-f/8#no-accr"></a></span>').')</span>
                                                            <i class="material-icons spec-favor'.($spec['favor'] && !$spec['fav_s'] ? ' added' : '').'"  data-specid="'.$spec['id'].'"></i>
                                                    </p>
                                                    '.(($spec['prof']) ? (
                            '<div class="spec-profiles">
                                                                            <div class="truncate">
                                                                                    <span class="hide-on-small-only">Профили: </span>'.$spec['prof'].'
                                                                            </div>
                                                                    </div>') : ('')).'
                                                            <div class="row spec-stats">';
                    if ($spec['free']) {
                        if ($spec['free'] === '1' && $vuz['vedom']) {
                            $specs .= '<div class="col s4 free">Бюджетные места: <span>есть</span></div>';
                        } else {
                            $specs .= '<div class="col s4 free">Бюджетных мест: <span>'.$spec['free'].'</span></div>';
                        }
                    } else {
                        $specs .= '<div class="col s4 nofree">Бюджетных мест: <span>нет</span></div>';
                    }

                    if ($spec['f_cost']) {
                        $specs .= '<div class="col s4 m4 l4 cost"><span>'.number_format(
                                $spec['f_cost'],
                                0,
                                ',',
                                ' '
                            ).'</span> рублей в год</div>';
                    } else {
                        $specs .= '<div class="col s4 m4 l4 cost">Коммерческих мест <span>нет</span></div>';
                    }
                    $specs .= '<div class="col s4 m4 l4 srok">'.$spec['display'].'</div>
                                            </div>';
                    if ($spec['lvl'] !== '4') {
                        $specs .=
                            '<div class="row spec-stats">
                                                            <div class="col s4 score">Проходной балл: <span>'.($spec['f_score'] ? $spec['f_score'] : '—').'</span></div>
                                                            <div class="col s4 score">Проходной балл: <span>'.($spec['p_score'] ? $spec['p_score'] : '—').'</span></div>
                                                    </div>';
                    }

                    if ($spec['lvl'] === '4' && ($spec['f_adv'] || $spec['m_lang'] || $spec['m_twin'] == '1')) {
                        $specs .= '<div class="adv">'.strip_tags($spec['f_adv']);

                        if ($spec['m_lang']) {
                            $specs .= ' Язык преподавания: ';
                            switch ($spec['m_lang']) {
                                case 'r':
                                    $specs .= 'русский';
                                    break;
                                case 'e':
                                    $specs .= 'английский';
                                    break;
                                case 're':
                                    $specs .= 'рус + англ.';
                                    break;
                            }
                        }

                        if ($spec['m_twin'] === '1') {
                            $specs .= ' Программа двух дипломов';
                        }
                        $specs .= '</div>';
                    } elseif ($spec['f_adv']) {
                        $specs .= '<div class="adv">'.$spec['f_adv'].'</div>';
                    }

                    $examsEge     = ' '.preg_replace('/( или |, )$/u', '', $spec['exams']);
                    $i_exam       = $spec['internal_exam'] ? "Присутствует внутренний экзамен" : false;
                    $internalExam = $i_exam ? ". $i_exam" : '';

                    $specs .= '<div class="row bot-line">';
                    if ($spec['f'] && $spec['exams']) {
                        $offset = '';
                        $specs  .= '<div class="col s12 m8 l6">
                        <b>Экзамены ЕГЭ:</b> Рус. яз, '.$examsEge.$internalExam.'</div>';
                    } else {
                        $offset = 'offset-m8 offset-l6';
                    }

                    if (isSpecActual($spec['upd_year'], ($vuz['packet'] || $vuz['partner']))) {
                        $specs .= '<div class="col myIcon '.$offset.' s12 m4 l6 update">Информация '.date(
                                "Y"
                            ).' года</div>';
                    } else {
                        $specs .= '<div class="col myIcon '.$offset.' s12 m4 l6 update bad">Информация '.$spec['upd_year'].' года</div>';
                    }

                    $specs .= '
                                                    </div>
                                            </div>';
                }

                if ($spec['s'] === '1') {
                    $specs .= '
                                            <div class="unit-spec" data-lvl="s" data-form="'.$spec['form'].'">
                                                    <a name="spec-'.$spec['id'].'s"></a>
                                                    <p class="name">
                                                            '.$spec['name'].' 
                                                            <span>('.substr_replace(
                            substr_replace($spec['code'], '.', 2, 0),
                            '.',
                            5,
                            0
                        ).' – '.$form.', '.$lvl.', 
                                                            '.(($spec['accr']) ? ('аккредитовано') : ('неаккредитовано')).')</span>
                                                            <i class="material-icons spec-favor'.(($spec['favor'] && $spec['fav_s']) ? (' added') : ('')).'"  data-specid="'.$spec['id'].'"></i>
                                                    </p>
                                                    '.(($spec['prof']) ? (
                            '<div class="spec-profiles">
                                                                            <div class="truncate">
                                                                                    <span class="hide-on-small-only">Профили: </span>'.$spec['prof'].'
                                                                            </div>
                                                                    </div>') : ('')).'
                                                            <div class="row spec-stats">
                                                                    <div class="col s4 nofree">Бюджетных мест: <span>нет</span></div>';
                    if ($spec['s_cost']) {
                        $specs .= '<div class="col s4 m4 l4 cost"><span>'.number_format(
                                $spec['s_cost'],
                                0,
                                ',',
                                ' '
                            ).'</span> рублей в год</div>';
                    } else {
                        $specs .= '<div class="col s4 m4 l4 cost">Коммерческих мест <span>нет</span></div>';
                    }
                    $specs .= '<div class="col s4 m4 l4 srok">'.$spec['display'].'</div>
                                    </div>';

                    $specs .= '<div class="row bot-line">';

                    $offset = 'offset-m8 offset-l6';
                    if (isSpecActual($spec['upd_year'], ($vuz['packet'] || $vuz['partner']))) {
                        $specs .= '<div class="col myIcon '.$offset.' s12 m4 l6 update">Информация '.date(
                                "Y"
                            ).' года</div>';
                    } else {
                        $specs .= '<div class="col myIcon '.$offset.' s12 m4 l6 update bad">Информация '.$spec['upd_year'].' года</div>';
                    }

                    $specs .= '
                                                    </div>
                                            </div>';
                }
            }

            if ($vuz['subunits']) {
                $specs .= '</section>'
                ;
            }
        } else {
            myErr::err404();
        }

        $vuz['h1_text'] = $faculty['sv'].' в '.$vuz['abrev'];
        $vuz['faculties_bread'] = true;
        $head = vuz::get_head($vuz, true, true);
        $title = ''.$faculty['sv'].' в '.$vuz['abrev'].' ' . date("Y") .'';
        $desc = ''.$faculty['sv'].' в '.$vuz['name'].' ('.$vuz['abrev'].'): бюджетные и платные места, проходной балл ЕГЭ, стоимость обучения, список программ обучения. Информация о поступлении на факультет '.$faculty['sv'].' в ' . date("Y") .'.';
        $kw    = $vuz['name'].' '.$vuz['abrev'].' специальности бакалавриат проходной балл ЕГЭ стоимость обучения';

        $nav = vuz::get_nav($vuz, 'faculties');

        /* ADS */
        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        if (!$vuz['packet']) {
            ads::get($ads, $vuz['subj_id']);
        }
        $sql = 'select sum(free) as faculty_places, round(min(f_cost)/1000) as faculty_price, round(avg(f_cost)) as faculty_price_avg,  round(avg(f_score)) as faculty_score  from vuz.specs where subvuz_id = '.$f_id;

        $db->query($sql);
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $stats  = $db->get_row();
        $stats['faculty_places'] = $stats['faculty_places'] == 0 ? '-' : $stats['faculty_places'];
        $stats['faculty_score'] = $stats['faculty_score'] == 0 ? '-' : $stats['faculty_score'];
        $stats['faculty_price'] = $stats['faculty_price'] == 0 ? '-' : $stats['faculty_price'];
        $stats['faculty_price_avg'] = $stats['faculty_price_avg'] == 0 ? '-' : $stats['faculty_price_avg'];

        $basePath     = '/' . $vuz['subj_id'] . '/' . (($vuz['subj_id'] != 77 && $vuz['subj_id'] != 78) ? ($vuz['city_id'] . '/') : ('')).'v'.$vuz['id'].'/faculties/'.$f_id.'/';
        $canonicalUrl = '//vuz'.DOMAIN.$basePath;
        $home         = HOME.'vuz.edunetwork.ru/';

        $tpl->start($home."tpl/faculty.html");

        $quiz = file_get_contents('tpl/quiz.html');
        $quiz = str_replace('[vuz_id]', $vuz['id'], $quiz);

        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw, '', $canonicalUrl),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[navbar]'          => $head['nav'],
            '[header]'          => $head['header'],

            '[faculty_name]'    => $faculty['sv'],
            '[faculty_places]'  => $stats['faculty_places'],
            '[faculty_score]'   => $stats['faculty_score'],
            '[faculty_price]'   => $stats['faculty_price'],
            '[faculty_price_avg]'=> number_format($stats['faculty_price_avg'],0,'',' '),

            '[vuz_abrev]'       => $vuz['abrev'],
            '[nav]'             => $nav,

            '[specs]'           => $specs,

            '[ylead]' => vuz::leads_form($vuz['id']),
            '[ads1]'  => $ads[1],
            '[ads2]'  => $ads[2],
            '[ads3]'  => $ads[3],
            '[ads5]'  => $ads[5],
            '[ads6]'  => $ads[6],
            '[ads7]'  => $ads[7],
            '[quiz]'  => $quiz,

            '[footer]' => file_get_contents('tpl/footer.html'),
        ]);
        $tpl->out();
    }


}