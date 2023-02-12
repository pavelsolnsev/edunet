<?php

class vuz_spec
{
    static function render ($u_id, $vuz_id = -1, $subvuz_id = -1, $spec_id = -1)
    {
        global $db, $tpl;
        $id = $vuz_id;
        $sql_buf =
            'SELECT 
                a.*, 
                `dir2specs`.`id` AS dir_id, `dir2specs`.`name` AS dir_name,
                `subjects`.`name` as subject, `subjects`.`rp` as subj_rp,
                `cities`.`name` as city, `cities`.`rp` as city_rp, `cities`.`type`, `metros`.`name` AS metro,
                IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
                (SELECT IF(COUNT(*)>0, 1, 0) FROM `vuz`.`subvuz` WHERE a.`id`=`subvuz`.`vuz_id` LIMIT 2) AS subunits,
                (SELECT COUNT(*) FROM `vuz`.`opinions` WHERE a.`id`=`opinions`.`vuz_id` AND `approved`="1") AS opins
            FROM
            (
                SELECT
                    `vuzes`.`id`,
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

        $db->query($sql_buf);
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $vuz  = $db->get_row();

        if ($vuz['delReason']) {
            myErr::err404();
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


        $buf_sql = 'SELECT 
                a.*, b.*, `okso`.`name`, `okso`.`code`, `okso`.`work`, MOD(ROUND(`okso`.`code`/100), 10) AS lvl, `edu_periods`.`display`,
                '.$t.' AS accr,
                GROUP_CONCAT(
                    DISTINCT CONCAT(`ege_exams`.`name`, " от ", `ege_exams`.`min`, IF(`spec2exams`.`sel` = "1", " или ", ", ")) 
                    ORDER BY `spec2exams`.`sel`, `ege_exams`.`id` SEPARATOR " "
                ) AS exams
                '.$sqlSEL.'
            FROM (
                SELECT 
                    `id`, `subvuz_id`, `free`, `form`, `f_score`, `p_score`, `f`, `s`, `prof`,
                    `internal_exam`,
                    `f_cost`,  `f_adv`, `commercial`,  
                    `s_cost`,  `s_adv`,  
                    `m_twin`, `m_lang`, YEAR(`specs`.`lastEdit`) AS upd_year,
                    `okso_id`, `period` 
                FROM `specs` WHERE `vuz_id` = '.$id.' AND `subvuz_id` = '.$subvuz_id.' AND `id` = '.$spec_id.'
                ) a 
            LEFT JOIN 
                `okso` ON a.`okso_id` = `okso`.`id` 
            LEFT JOIN 
                '.$t1.'
                `spec2exams` ON a.`id`=`spec2exams`.`spec_id` 
            LEFT JOIN
                `general`.`ege_exams` ON `ege_exams`.`id`=`spec2exams`.`exam_id` 
            LEFT JOIN
                `general`.`edu_periods` ON a.`period` = `edu_periods`.`id` 
            LEFT JOIN
                (
                    SELECT `id` AS sv_id, `name` AS sv, `address` 
                        FROM `subvuz` WHERE `vuz_id`='.$id.'
                ) b ON b.`sv_id`=a.`subvuz_id`	
                '.$sqlFROM.'
            GROUP BY a.`id` ORDER BY b.sv_id, `okso`.`code`';
        //            WHERE (a.f = 1 OR a.s = 1 OR MOD(ROUND(`okso`.`code`/100), 10) = 4)

        $db->query($buf_sql);

        if ($db->num_rows()) {
            if (!$vuz['subunits']) {
                $specs .= '<p class="specs-title">Специальности:</p>';
            }

            $spec_vuz         = '';
            $spec_name        = '';
            //$description      = '';
            $work_description = '';
            $spec_name_code   = '<h2>Проходной балл, стоимость обучения на специальность <span></span> </h2>';
            $spec_free        = '';
            $spec_payed       = '';
            $spec_fscore      = '';
            $spec_pscore      = '';


            while ($spec = $db->get_row()) {
                if ($vuz['subunits']) {
                    if ($spec['sv_id'] !== $sv_id) {
                        $sv_id = $spec['sv_id'];
                        if ($sv_id && $vuz['subunits']) {
                            $specs .= '</section>';
                        }

                        //$description = $spec['descr'];
                        $spec_vuz       = 'Специальность '.$spec['name'].' в '.$vuz['abrev'].'';
                        $spec_name      = $spec['name'];
                        $spec_name_code = '';
                        $work_description = $spec['work'];
                        $specs .= '
                            <section class="subunit" itemprop="department" itemscope itemtype="http://schema.org/Organization">';

                        /*
                        $specs .= '
                            <section class="subunit" itemprop="department" itemscope itemtype="http://schema.org/Organization">
                                <header>
                                    <p class="address" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                                        <meta itemprop="addressCountry" content="RU" />
                                        <meta itemprop="addressRegion" value="'.$vuz['subject'].'" />
                                        <meta itemprop="addressLocality" value="'.$vuz['city'].'" />
                                        <meta itemprop="streetAddress" value="'.$spec['address'].'" />';
                        */
                        if ($vuz['subj_id'] !== 77 && $vuz['subj_id'] !== 78) {
                            $specs .= $vuz['subject'].', ';
                        }
                        if ($id !== 410 && $id !== 175) {
                            $specs .= $vuz['type'].'&nbsp;'.$vuz['city'].', ';
                        }
                        /*
                        $specs .= $spec['address'].'
                                    </p>
                                </header>
                                <p class="specs-title">Образовательные программы:</p>';
                        */
                    }
                }

                switch ($spec['form']) {
                    case '1':
                        $form = 'Очная';
                        break;
                    case '2':
                        $form = 'Очно-заочная';
                        break;
                    case '3':
                        $form = 'Заочная';
                        break;
                    case '4':
                        $form = 'Дистанционная';
                        break;
                }

                $vuz['level'] = $spec['lvl'];
                switch ($spec['lvl']) {
                    case '3':
                        $lvl = 'бакалавриат';
                        $lvl_2 = 'в бакалавриате';
                        break;
                    case '4':
                        $lvl = 'магистратура';
                        $lvl_2 = 'в магистратуре';
                        break;
                    case '5':
                        $lvl = 'специалитет';
                        $lvl_2 = 'на специалитете';
                        break;
                    case '6':
                        $lvl = 'аспирантура';
                        $lvl_2 = 'в аспирантуре';
                        break;
                }

                //$new_url = 'https://'.$_SERVER['HTTP_HOST'].'/bla-bla-bla';
                $new_url = '';

                $spec['f_code'] = substr_replace(
                    substr_replace($spec['code'], '.', 2, 0),
                    '.',
                    5,
                    0
                );

                $spec_code = $spec['f_code'];

                if ($spec['lvl'] === '4' || $spec['f'] === '1') {
                    $spec_name_code = '<h2> Проходной балл, количество бюджетных мест, стоимость обучения на специальность '.$spec['name'].'<span> ('.$spec['f_code'].') </span> </h2>';

                    if ($spec['s_cost']) {
                        $cost = number_format(
                                $spec['s_cost'],
                                0,
                                ',',
                                ' '
                            ).' руб';
                    } else {
                        $cost = 'Коммерческих мест нет';
                    }

                    $items++;
                    $specs .= '
                                            <div class="unit-spec" data-lvl="'.(($spec['lvl'] === '4') ? ('m') : ('f')).'" data-form="'.$spec['form'].'">
                                                    <a name="spec-'.$spec['id'].'"></a>
                                                    <p class="name">
                                                         <a href="'.$new_url.'">'.$spec['name'].' </a>   
                                                            <span>('.$spec['f_code'].' – '.$form.', '.$lvl.', 
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
                            $spec_free = 'есть';
                        } else {
                            $specs .= '<div class="col s4 free">Бюджетных мест: <span>'.$spec['free'].'</span></div>';
                            $spec_free = $spec['free'];
                        }
                    } else {
                        $specs .= '<div class="col s4 nofree">Бюджетных мест: <span>нет</span></div>';
                        $spec_free = 'нет';
                    }

                    if ($spec['commercial']) {
                        $spec_payed = '</span> / <span>'.$spec['commercial'];
                    }
                    elseif ($spec['f_adv']) {
                        if ($spec['f_adv'] === '1' && $vuz['vedom']) {
                            $spec_payed = '</span> / <span>есть';
                        } else {
                            $spec_payed = '</span> / <span>'.$spec['f_adv'];
                        }
                    } else {
                        //$spec_payed = 'нет';
                        $spec_payed = '</span><span>';
                    }

                    if ($spec['f_score']) {
                        $spec_fscore = $spec['f_score'];
                    }

                    if ($spec['p_score']) {
                        $spec_pscore = $spec['p_score'];
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
                        <b>Экзамены ЕГЭ:</b> Русский язык от 36, '.$examsEge.$internalExam.'</div>';
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
                    $spec_name_code = '<h2>Проходной балл, количество бюджетных мест, стоимость обучения на специальность '.$spec['name'].'<span> ('.$spec['f_code'].') </span> </h2>';

                    if ($spec['s_cost']) {
                        $cost = number_format(
                                $spec['s_cost'],
                                0,
                                ',',
                                ' '
                            ).' руб';
                    } else {
                        $cost = 'Коммерческих мест нет';
                    }

                    $specs .= '
                                            <div class="unit-spec" data-lvl="s" data-form="'.$spec['form'].'">
                                                    <a name="spec-'.$spec['id'].'s"></a>
                                                    <p class="name">
                                                            '.$spec['name'].' 
                                                            <span>('. $spec['f_code'].' – '.$form.', '.$lvl.', 
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
                $specs .= '</section>';
            }
        } else {
            myErr::err404();
        }

        $sql_exams = '
            select * 
            from 
	        vuz.spec2exams as vs
            left join 
	    general.ege_exams as ge on ge.id = vs.exam_id
            where 
	        vs.spec_id = '.$spec_id;


        $egeList = '
        <h2>Вступительные испытания:</h2>		
        <ul class="spec-pages-list">';
        $db->query($sql_exams);
        if ($db->num_rows()) {
            while ($exam = $db->get_row()) {
                if ($vuz['level'] == 6) {
                    $egeList .= '                
                <li>'.$exam['name'].'</li>';
                } else {
                    $egeList .= '                
                <li>'.$exam['name'].' <span>от '.$exam['min'].'</span></li>';
                    
                }
            }
        }
        if ($egeList != '') {
            $egeList .= '</ul>';
        }


        $title = 'Специальность '.$spec_name.' '.$lvl_2.' в ' .$vuz['abrev'].' ('.$spec_code.'): проходные баллы на бюджет, стоимость обучения '.date("Y");
        $desc  = ''.$spec_name.' в '.$vuz['name'].': бюджетные и платные места, проходной балл ЕГЭ, стоимость обучения. Информация о поступлении на специальность геология ('.$spec_code.') в РГУ нефти и газа '.date("Y").'.';
        $kw    = $vuz['name'].' '.$vuz['abrev'].' специальности бакалавриат проходной балл ЕГЭ стоимость обучения';
        $nav = vuz::get_nav($vuz, 'specs');

        /* ADS */
        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        if (!$vuz['packet']) {
            ads::get($ads, $vuz['subj_id']);

        }
        if ($tpl == null ) {
            $tpl = new tpl();
            $tpl_prefix = '../../vuz.edunetwork.ru/';
        } else {
            $tpl_prefix = '';
        }
        $home = HOME.'/vuz.edunetwork.ru/';
        if ($spec['lvl'] == 6 ) {
            $tpl->start($home."tpl/spec_phd.html");
        } else {
            $tpl->start($home."tpl/sect.html");
        }


        if ($vuz['level'] == 6) {
            $budget_section = '';
        }
        else {
            $budget_section = '
                                    <li>
                                            <div>Проходной балл<br> бюджет / <span>платное</span></div>
                                            <div><span><b>'.$spec_fscore.'</b></span> / <span>'.$spec_pscore.'</span></div>
                                    </li>
            
            ';
        }


        $spec_lines = '
                            <ul>
                                    <li>
                                            <div>Форма<br> обучения</div>
                                            <div><b>'.$form.'</b></div>
                                    </li>
                                    '.$budget_section.'
                                    <li>
                                            <div>Мест<br> бюджет / <span>платное</span></div>
                                            <div><span><b>'.$spec_free.'</b>'.$spec_payed.'</span></div>
                                    </li>
                                    <li>
                                            <div>Стоимость<br> за год</div>
                                            <div><b>'.$cost.'</b></div>							
                                    </li>
                            </ul>
                        ';

        $vuz['h1_text'] =  'Специальность '.$spec_name.' '.$spec_code.' '.$lvl_2.' в '.$vuz['abrev'];

        $head = vuz::get_head($vuz, true, true);

        $quiz = file_get_contents('tpl/quiz.html');
        $quiz = str_replace('[vuz_id]', $vuz['id'], $quiz);


        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),
            '[navbar]'          => $head['nav'],
            '[header]'          => $head['header'],
            //'[nav]'             => $nav,
            '[specs]'           => $specs,
            '[ylead]'           => vuz::leads_form($vuz['id']),
            //'[ads1]'            => $ads[1],
            //'[ads2]'            => $ads[2],
            //'[ads3]'            => $ads[3],
            //'[ads5]'            => $ads[5],
            //'[ads6]'            => $ads[6],
            //'[ads7]'            => $ads[7],
            //'[spec_description]' => $description,

            '[spec_vuz]'        => $spec_vuz,
            '[spec_work]'       => $work_description,
            '[spec_name_code]'  => $spec_name_code,

            '[vuz_abrev]'       => $vuz['abrev'],
            '[vuz_full_caps]'   => $vuz['name'],
            '[spec_lines]'      => $spec_lines,
            '[spec_name]'       => $spec_name,
            '[spec_code]'       => $spec_code,
            '[spec_free]'       => $spec_free,
            '[spec_rate]'       => $spec_fscore,
            '[spec_price]'      => $cost,
            '[spec_ege]'        => $egeList,
            '[quiz]'            => $quiz,
            '[footer]'          => file_get_contents($tpl_prefix.'tpl/footer.html'),
        ]);
        $tpl->out();
    }


}