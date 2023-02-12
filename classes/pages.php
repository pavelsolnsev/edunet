<?php

class pages
{

    static function favor()
    {
        global $db;
        $u_id = user::check_session();
        if (!$u_id) {
            die('auth');
        }

        $sec     = ((strstr($_POST['spec_id'], 's')) ? ("1") : ("0"));
        $spec_id = (int)$_POST['spec_id'];
        $del     = (($_POST['del']) ? (true) : (false));

        if ($del) {
            $db->query('DELETE FROM `favor` WHERE `u_id`=? AND `spec_id`=? AND `s`=?', $u_id, $spec_id, $sec);
        } else {
            $db->query('SELECT 1 FROM `favor` WHERE `u_id`=? LIMIT 5', $u_id);
            if ($db->num_rows() == 5) {
                die('limit');
            }
            $db->query(
                'INSERT IGNORE INTO `favor`(`u_id`, `spec_id`, `s`, `added`) VALUES (?, ?, ?, DATE(NOW()))',
                $u_id,
                $spec_id,
                $sec
            );
        }
        die('Ok');
    }

    static function favorPage($u_id)
    {
        if (!$u_id) {
            header("Location: https://secure.edunetwork.ru/#https://vuz.edunetwork.ru/favor", 302);
            die;
        }

        global $tpl, $db;

        $title = 'Мои специальности';
        $desc  = '';
        $kw    = '';

        $nothing = '<div class="nothing">
						<p>Пока вы не добавили специальности к сравнению</p>
						<p>Но можете это сделать в любой момент нажав на <i class="material-icons">favorite_border</i> рядом с названием специальности</p>
					</div>';
        $leg1    = $leg2 = $legItem = $data1 = $data2 = '';
        $i       = 1;
        $tr1     = [
            '<td>Форма</td>',
            '<td>Срок</td>',
            '<td class="free">Бюджетных мест</td>',
            '<td>Стоимость</td>',
            '<td class="f_score">Проходной балл (бюджет)</td>',
            '<td class="p_score">Проходной балл (платное)</td>',
            '<td>Экзамены</td>',
            '<td>Дополнительно</td>'
        ];
        $tr2     = [
            '<td>Уровень</td>',
            '<td>Форма</td>',
            '<td>Срок</td>',
            '<td class="free">Бюджетных мест</td>',
            '<td>Стоимость</td>',
            '<td>Дополнительно</td>'
        ];
        $db->query(
            '
            SELECT 
                `specs`.`id`, `specs`.`vuz_id`, `specs`.`form`, `specs`.`free`, `specs`.`prof`,
                `specs`.`f_score`, `specs`.`p_score`, `specs`.`f_cost`, `specs`.`s_cost`, 
                `specs`.`f`, `specs`.`s`, `specs`.`f_adv`, `specs`.`s_adv`, 
                `okso`.`name` AS okso_name, SUBSTRING(`okso`.`code`, 4, 1) AS lvl, b.`s` AS sec, `edu_periods`.`display`,
                `specs`.`m_lang`, `specs`.`m_twin`,
                GROUP_CONCAT(
					DISTINCT CONCAT(`ege_exams`.`short`, IF(`spec2exams`.`sel` = "1", " или ", ", ")) 
					ORDER BY `spec2exams`.`sel`, `ege_exams`.`id` SEPARATOR " "
				) AS exams,
                `vuzes`.`abrev`, `vuzes`.`subj_id`, `vuzes`.`city_id`,`cities`.`name` AS city, `vuzes`.`parent_id` 
            FROM 
                (SELECT `spec_id`, `s` FROM `favor` WHERE `u_id`=?) b LEFT JOIN
                `specs` ON `specs`.`id`=b.`spec_id` LEFT JOIN 
                `okso` ON `okso`.`id`=`specs`.`okso_id` LEFT JOIN
                `vuzes` ON `specs`.`vuz_id`=`vuzes`.`id` LEFT JOIN 
                `spec2exams` ON `specs`.`id`=`spec2exams`.`spec_id` LEFT JOIN
                `general`.`ege_exams` ON `ege_exams`.`id`=`spec2exams`.`exam_id` LEFT JOIN
				`general`.`edu_periods` ON `specs`.`period` = `edu_periods`.`id` LEFT JOIN 
                `general`.`cities` ON `cities`.`id`=`vuzes`.`city_id`
            GROUP BY `specs`.`id`, sec
            ORDER BY MOD(`lvl`, 2) DESC, `sec`, `form`',
            $u_id
        );
        if ($db->num_rows()) {
            $addTr = function ($s) {
                return ('<tr>' . $s . '</tr>');
            };
            while ($row = $db->get_row()) {
                $url = '/' . $row['subj_id'] . '/';
                if ($row['subj_id'] != 77 && $row['subj_id'] != 78) {
                    $url .= $row['city_id'] . '/';
                }
                $url     .= 'v' . $row['vuz_id'] . '/';
                $legItem = '
                    <tr>
					    <td class="color' . $i . '">' . $i . '</td>
						<td class="leg-data">
							<p class="leg-unit">' . $row['abrev'] . (($row['parent_id']) ? ('') : (' <i>(' . $row['city'] . ')</i>')) . ' <a href="' . $url . '">call_made</a></p>
							<p class="leg-spec"><a href="' . $url . 'specs/#spec-' . $row['id'] . '">' . $row['okso_name'] . '</a></p>
							' . (($row['prof']) ? ('<p class="leg-prof">' . $row['prof'] . '</p>') : ('')) . '
						</td>
						<td><a href="#" class="del-favor material-icons" data-specId="' . $row['id'] . '">close</a></td>
					</tr>';

                switch ($row['form']) {
                    case '1':
                        $form = 'очная';
                        break;
                    case '2':
                        $form = 'очно-заочная';
                        break;
                    case '3':
                        $form = 'заочная';
                        break;
                    case '4':
                        $form = 'дистанционная';
                        break;
                }

                if ($row['lvl'] !== '4' && $row['sec'] === '0') {
                    $leg1   .= $legItem;
                    $tr1[0] .= '<td>' . $form . '</td>';
                    $tr1[1] .= '<td>' . $row['display'] . '</td>';
                    $tr1[2] .= '<td>' . ($row['free'] ? $row['free'] : '—') . '</td>';
                    $tr1[3] .= '<td>' . ($row['f_cost'] ? number_format(
                                $row['f_cost'],
                                0,
                                ',',
                                ' '
                            ) . ' ₽&#47;год' : '—') . '</td>';
                    $tr1[4] .= '<td>' . ($row['f_score'] ? $row['f_score'] : '—') . '</td>';
                    $tr1[5] .= '<td>' . ($row['p_score'] ? $row['p_score'] : '—') . '</td>';
                    $tr1[6] .= '<td>' . ($row['exams'] ? 'Русс. яз, ' . preg_replace(
                                '/( или |, )$/u',
                                '',
                                $row['exams']
                            ) : 'Русс. яз') . '</td>';
                    $tr1[7] .= '<td>' . ($row['f_adv'] ? $row['f_adv'] : '—') . '</td>';
                } else {
                    if (!$leg2 && ($row['lvl'] === '4' || $row['sec'] !== '0')) {
                        $cnt = $i;
                        $i   = 1;
                    }
                    $leg2 .= $legItem;
                    if ($row['lvl'] === '4') {
                        $lvl    = 'Магистратура';
                        $prefix = 'f';
                    } else {
                        $lvl    = 'Второе высшее';
                        $prefix = 's';
                    }
                    $tr2[0] .= '<td>' . $lvl . '</td>';
                    $tr2[1] .= '<td>' . $form . '</td>';
                    $tr2[2] .= '<td>' . $row['display'] . '</td>';
                    $tr2[3] .= '<td>' . ($row['free'] ? $row['free'] : '—') . '</td>';
                    $tr2[4] .= '<td>' . ($row[$prefix . '_cost'] ? number_format(
                                $row[$prefix . '_cost'],
                                0,
                                ',',
                                ' '
                            ) . ' ₽&#47;год' : '—') . '</td>';
                    if ($row['lvl'] === '4') {
                        if ($row['m_lang']) {
                            $row[$prefix . '_adv'] .= ' Язык преподавания: ';
                            switch ($row['m_lang']) {
                                case 'r':
                                    $row[$prefix . '_adv'] .= 'русский';
                                    break;
                                case 'e':
                                    $row[$prefix . '_adv'] .= 'английский';
                                    break;
                                case 're':
                                    $row[$prefix . '_adv'] .= 'рус + англ.';
                                    break;
                            }
                        }
                        if ($row['m_twin'] === '1') {
                            $row[$prefix . '_adv'] .= ' Программа двух дипломов';
                        }
                    }
                    $tr2[5] .= '<td>' . ($row[$prefix . '_adv'] ? $row[$prefix . '_adv'] : '—') . '</td>';
                }
                $i++;
            }
            if (!$cnt) {
                $cnt = $i;
            }
            $legTpl  = '<table class="legends highlight"><tbody>%s</tbody></table>';
            $dataTpl = '<table class="spec-data responsive-table striped">
                        <thead>
							<tr>
								<th>&nbsp;</th>
							    %s
							</tr>
						</thead>
						<tbody>%s</tbody>
                    </table>';
            if ($leg1) {
                $leg1 = sprintf($legTpl, $leg1);
                $tr1  = array_map($addTr, $tr1);
                for ($t = '', $j = 1; $j < $cnt; $j++) {
                    $t .= '<th class="color' . $j . '">' . $j . '</th>';
                }
                $data1 = sprintf($dataTpl, $t, join('', $tr1));
            } else {
                $leg1 = $nothing;
            }
            if ($leg2) {
                $leg2 = sprintf($legTpl, $leg2);
                $tr2  = array_map($addTr, $tr2);
                for ($t = '', $j = 1; $j < $i; $j++) {
                    $t .= '<th class="color' . $j . '">' . $j . '</th>';
                }
                $data2 .= sprintf($dataTpl, $t, join('', $tr2));
            } else {
                $leg2 = $nothing;
            }
        } else {
            $leg1 = $leg2 = $nothing;
        }

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $tpl->start('tpl/favor.html');
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[f]' => $leg1 . $data1,
            '[s]' => $leg2 . $data2,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],
            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function checkVuz($u_id)
    {
        global $tpl, $db;
        require_once("classes/vuz.php");

        $title = 'Проверка вуза онлайн';
        $desc  = 'Проверить вуз онлайн по десяти показателям. Лицензия и аккредитация, запреты приема, наличие общежития и военной кафедры, данные мониторинга вузов Минобрнауки';
        $kw    = 'Проверка вуз онлайн лицензия аккредитация запрет приема набора абитуриентов общежитие военная кафедра мониторинг вузов, отзывы, оценка посетителей';

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $tpl->start('tpl/checkVuz.html');
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[ylead]' => vuz::leads_form(),

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],
            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function checkVuzResult()
    {
        global $db;

        $v_id = (int)abs($_POST['id']);
        if (!$v_id) {
            die('Err:badId');
        }
        $db->query(
            '
            SELECT 	
                a.*, `subjects`.`name` AS subj, `cities`.`name` AS city, `esi_log`.`val`,
                (SELECT `monit`.`val` FROM `monit` WHERE `monit`.`vuz_id`=a.`id` AND `year`="21" AND `label`="eff") AS eff 
            FROM (		
	            SELECT 
	                `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`fullName`, `vuzes`.`gos`, `vuzes`.`hostel`, `vuzes`.`military`, 
	                `vuzes`.`city_id`, `vuzes`.`lic_num`, `vuzes`.`lic_start`, `vuzes`.`acr_num`, `vuzes`.`acr_start`, `vuzes`.`parent_id`, `vuzes`.`vedom`,
	                `vuzes`.`noAbitur`, `vuzes`.`delReason`, `vuzes`.`esi`, `vuzes`.`esi_marks` FROM `vuzes` WHERE `id`=?
	            ) a LEFT JOIN		
            `vuz`.`esi_log` ON `esi_log`.`vuz_id`=a.`id` LEFT JOIN    
            `general`.`cities` ON a.`city_id`=`cities`.`id`  LEFT JOIN
            `general`.`subjects` ON `cities`.`subject_id`=`subjects`.`id`',
            $v_id
        );
        if (!$db->num_rows()) {
            die('Err:noVuz');
        }
        $vuz = $db->get_row();

        $out = '
            <p id="vuz-name"><b>' . $vuz['name'] . '</b> ' . $vuz['subj'] . (($vuz['city_id'] != 26 && $vuz['city_id'] != 44) ? (', ' . $vuz['city']) : ('')) . '</p>
            <p id="vuz-fn"><i>' . $vuz['fullName'] . '</i></p>';
        if ($vuz['esi'] !== null) {
            $vuz['esi'] = (int)$vuz['esi'];
            $out        .= '<div id="esi-block" class="card horizontal">
						<div class="card-image">
						    <div id="esi-mark" class="mark';
            if ($vuz['esi'] > 7) {
                $out .= 'A"></div><p>Надежный';
            } elseif ($vuz['esi'] > 3) {
                $out .= 'B"></div><p>Стабильный';
            } else {
                $out .= 'C"></div><p>Негативный';
            }

            $out .=
                '<br/>2022 год</p>
                        </div>
						<div class="card-stacked">
							<div class="card-content">
								<p>
									ESI (Индекс стабильности EduNetwork) — присваивается учебным заведениям ежегодно, рассчитывается на основании формальных показателей деятельности из официальных источников.<br />
									Оценивает перспективу существования учебного заведения в течение ближайших лет, по мнению проекта EduNetwork, по шкале от <b>А</b> — «Надежный» до <b>С</b> — «Негативный». 
								</p>
							</div>
						</div>
					</div>';
        }

        if ($vuz['delReason']) {
            $out .= '<p class="vuzClosed">' . $vuz['delReason'] . '</p>';
        } else {
            if ($vuz['parent_id']) {
                $db->query(
                    'SELECT `lic_num`, `lic_start`, `acr_num`, `acr_start` FROM `vuzes` WHERE `id`=?',
                    $vuz['parent_id']
                );
                $parent = $db->get_row();

                $vuz['lic_num']   = $parent['lic_num'];
                $vuz['lic_start'] = $parent['lic_start'];

                $vuz['acr_num']   = $parent['acr_num'];
                $vuz['acr_start'] = $parent['acr_start'];
            }
            if ($vuz['lic_num']) {
                $out .= '<p class="check-ok">Лицензия действительна. Номер лицензии ' . $vuz['lic_num'] . ' от ' . $vuz['lic_start'] . ' действительна Бессрочно</p>';
            } else {
                $out .= '<p class="check-bad">Нет данных действующей лицензии</p>';
            }

            if ($vuz['acr_num']) {
                $out .= '<p class="check-ok">Аккредитация действительна. Номер аккредитации ' . $vuz['acr_num'] . ' от ' . $vuz['acr_start'] . '</p>';
            } else {
                $out .= '<p class="check-bad">Нет данных действующей аккредитации</p>';
            }

            if ($vuz['noAbitur']) {
                $out .= '<p class="check-bad">В вуз запрещен набор абитуриентов. Обновлено ' . $vuz['noAbitur'] . '</p>';
            } else {
                $out .= '<p class="check-ok">Действующих запретов приема не обнаружено</p>';
            }

            if ($vuz['vedom']) {
                $out .= '<p class="check-i">Ведомственные вузы не проходят процедуру мониторинга</p>';
            } else {
                if ($vuz['val'] === null) {
                    $out .= '<p class="check-bad">Результаты мониторинга вуза за 2021 год не найдены</p>';
                } else {
                    $val      = explode('|', $vuz['val']);
                    $ind_func = function ($v) {
                        switch ($v) {
                            case '0':
                                $v = 'bad';
                                break;
                            case '1':
                                $v = 'norm';
                                break;
                            case '2':
                                $v = 'ok';
                                break;
                        }
                        return ($v);
                    };
                    $out      .= '
                            <ul id="monit-ind">
                                <li class="check-' . $ind_func($val[0]) . '">Средний балл ЕГЭ студентов, принятых на обучение по очной форме</li>
                                <li class="check-' . $ind_func($val[1]) . '">Научно-исследовательская деятельность</li>
                                <li class="check-' . $ind_func($val[2]) . '">Удельный вес численности иностранных студентов</li>
                                <li class="check-' . $ind_func($val[3]) . '">Доходы образовательной организации</li>
                                <li class="check-' . $ind_func($val[4]) . '">Отношение заработной платы профессорско-преподавательского состава к средней заработной плате региона</li>
                                <li class="check-' . $ind_func($val[5]) . '">Численность преподавателей, имеющих ученые степени кандидата или доктора наук, на 100 студентов</li>
                            </ul>';
                    if ($vuz['esi_marks'] % 10) {
                        $out .= '<p class="check-bad">Сокращение численности студентов более чем на 20% за 2 года</p>';
                    }
                    if ($vuz['esi_marks'] / 10 >= 1) {
                        $out .= '<p class="check-bad">Более 80% студентов учатся заочно и дистанционно</p>';
                    }
                }
            }

            $out .= (($vuz['gos'] === "1") ? ('<p class="check-ok">Государственный вуз</p>') : ('<p class="check-i">Негосударственный вуз</p>'));
            $out .= (($vuz['hostel'] === "1") ? ('<p class="check-ok">Общежитие</p>') : ('<p class="check-i">Нет общежития</p>'));
            $out .= (($vuz['military'] === "1") ? ('<p class="check-ok">Военная кафедра</p>') : ('<p class="check-i">Нет военной кафедры</p>'));

            $db->query(
                'SELECT count(*) AS cnt, ROUND(AVG(`score`),1) AS score FROM `opinions` WHERE `vuz_id`=? AND `approved`="1" AND `score` IS NOT NULL',
                $v_id
            );
            $row = $db->get_row();
            if ($row['cnt'] >= 4) {
                $row['score'] = (float)$row['score'];
                if ($row['score'] >= 4) {
                    $out .= '<p class="check-ok">';
                } elseif ($row['score'] >= 2.5) {
                    $out .= '<p class="check-norm">';
                } else {
                    $out .= '<p class="check-bad">';
                }
                $out .= 'Оценка ' . $row['score'] . ' из 5, исходя из ' . $row['cnt'] . ' ' . rodpad(
                        $row['cnt'],
                        ['отзывов', 'отзывов', 'отзыва', 'отзывов']
                    ) . '</p>';

                $db->query(
                    'SELECT ROUND(SUM(`score` IS NULL)*100/COUNT(*)) AS nulled FROM `opinions` WHERE `vuz_id`=? AND `approved`="1"',
                    $v_id
                );
                $row1           = $db->get_row();
                $row1['nulled'] = (int)$row1['nulled'];
                if ($row1['nulled'] > 30) {
                    $out .= '<p class="check-bad">Зафиксированы массовые попытки накрутки положительных отзывов</p>';
                } elseif ($row1['nulled'] > 10) {
                    $out .= '<p class="check-norm">Зафиксированы попытки накрутки положительных отзывов</p>';
                } else {
                    $out .= '<p class="check-ok">Не зафиксировано попыток накрутки положительных отзывов</p>';
                }
            }

            if ($v_id === 381 || $v_id === 570) {
                $out .= '<p class="check-i">Вправе проводить дополнительные вступительные испытания для всех специальностей (<a href="http://www.consultant.ru/document/cons_doc_LAW_140174/f01e721e7f3d20f5deaa79625b838c73f5792a67/" target="_blank">закон</a>)</p>';
            }
        }

        die($out);
    }

    static function dods()
    {
        global $tpl, $db;

        $city = (int)$_GET['city'];
        $subj = (int)$_GET['subj'];
        if ($city === 77 || $city === 78) {
            $url = $city;
            if ($city === 77) {
                $rp   = 'Москвы';
                $city = 26;
            } else {
                $rp   = 'Санкт-Петербурга';
                $city = 44;
            }
        } else {
            $db->query(
                'SELECT `rp` FROM `general`.`cities` WHERE `id`=? AND `subject_id`=? AND `populat`>3',
                $city,
                $subj
            );
            if (!$db->num_rows()) {
                myErr::err404();
            }
            $url = $subj . '/' . $city;
            $row = $db->get_row();
            $rp  = $row['rp'];
        }

        $db->query(
            '
			SELECT a.*, `vuzes`.`name` AS vuz, `vuzes`.`abrev`, `vuzes`.`logo`, `subvuz`.`name` AS sv 
			FROM 
			    (		
                    SELECT 
                        `openDays`.`id`, `openDays`.`name`, DATE(`openDays`.`start`) AS date, `openDays`.`address`, 
                        `openDays`.`online`, `openDays`.`url`,
                        `openDays`.`vuz_id`, `openDays`.`subvuz_id`
                    FROM `vuz`.`openDays` WHERE `openDays`.`start`>=DATE(NOW())
                ) a LEFT JOIN
                `vuz`.`vuzes` ON a.`vuz_id`=`vuzes`.`id` LEFT JOIN 
                `vuz`.`subvuz` ON a.`subvuz_id`=`subvuz`.`id`	
            WHERE `vuzes`.`city_id`=?
            GROUP BY a.`id`
            ORDER BY a.`date`, a.`vuz_id` LIMIT 30',
            $city
        );

        if ($cnt = $db->num_rows()) {
            $dods = '';
            $d    = 0;
            while ($row = $db->get_row()) {
                if ($row['logo']) {
                    $row['logo'] = '<img class="img-responsive" src="/files/' . $row['vuz_id'] . '/logo.' . $row['logo'] . '" alt="Логотип ВУЗа" />';
                } else {
                    $row['logo'] = '<img src="//static.edunetwork.ru/imgs/tpl/noLogo.png" alt="Нет логотипа" />';
                }

                if ($d !== $row['date']) {
                    $dods .= '<p class="date">' . date::mysql2Rus($row['date']) . '</p>';
                    $d    = $row['date'];
                }
                $dods .= '
						<div class="dod card horizontal">
						    <div  class="card-image">
                                ' . $row['logo'] . '
                            </div>
							<div class="card-stacked">
							    <div class="card-content" itemscope itemtype="https://schema.org/Event">
                                    <meta itemprop="startDate" content="' . $row['date'] . '" />
                                    <meta itemprop="endDate" content="' . $row['date'] . '" />
                                    <meta itemprop="name" content="' . $row['abrev'] . '" />
                                    
                                    <p class="unit-name"><a href="/' . $url . '/v' . $row['vuz_id'] . '/#openDays">' . $row['vuz'] . '</a></p>';
                if ($row['sv']) {
                    $dods .= '<p class="myIcon leftIcon subunit">' . $row['sv'] . '</p>';
                }
                if ($row['name']) {
                    $dods .= '<p class="dod-name">' . $row['name'] . '</p>';
                }
                $dods .= (($row['online']) ? ('<p class="myIcon leftIcon online">Онлайн-мероприятие</p>') : ('<p class="myIcon leftIcon addr">' . $row['address'] . '</p>')) . '
                                </div>    
							</div>
						</div>';
            }
        } else {
            $dods = '<div id="nomore" class="myIcon">Мероприятий пока не запланировано</div>';
        }

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $title = 'Дни открытых дверей в вузах ' . $rp . ' в ' . date("Y") . ' году';
        $desc  = 'Календарь дней открытых дверей в вузах ' . $rp . ' (университетах и институтах) в ' . date(
                "Y"
            ) . ' году';
        $kw    = 'дни открытых дверей вузы ' . $rp . ' календарь университеты институты ' . date("Y");
        $seo   = 'Только актуальная информация! Дни открытых дверей в вузах ' . $rp . ' ' . date(
                "Y"
            ) . '(университетах и институтах) добавляются в календарь непосредственно официальными представителями вузов на проекте и редакцией портала.';

        $tpl->start('tpl/dod-city.html');
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[id]'      => $city,
            '[url]'     => $url,
            '[rp]'      => $rp,
            '[seoText]' => $seo,

            "[dods]" => $dods,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function specsList()
    {
        global $db, $tpl;

        //echo 'specList';
        //echo 'id -> '.$_GET['id'];

        $list = '';
        $db->query(
            '
            SELECT a.*, `okso_gr`.`name` AS gr 
            FROM 
                  (
                    SELECT `id`, `code`, `name`, FLOOR(`code`/10000) AS g
                    FROM  `okso`
                  ) a 
            LEFT JOIN`okso_gr` ON `okso_gr`.`id`=a.g
            ORDER BY a.`code`'
        );
        $g = 0;
        while ($row = $db->get_row()) {
            if ($g !== $row['g']) {
                $list .= '
                <li class="group">' . $row['gr'] . '</li>';
                $g    = $row['g'];
            }
            $list .= '
                <li>
                    <span>' . substr_replace(substr_replace($row['code'], '.', 2, 0), '.', 5, 0) . '</span> 
                    &mdash; <a href="/specs/' . $row['id'] . '">' . $row['name'] . '</a>
                </li>';
        }
        $list .= '
                <li class="group"></li>';

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $title = 'Специальности вузов России';
        $desc  = 'Специальности высшего образования в вузах России для студентов. Бакалавриат, специалитет и магистратура. Актуальный справочник ОКСО.';
        $kw    = 'специальности вузов, специальности для студентов, направления подготовки, бакалавриат, специалитет, магистратура, классификатор специальностей, ОКСО, перечень специальностей, специальности впо, учебная специальность, код специальности';

        $home = HOME . 'vuz.edunetwork.ru/';
        $tpl->start($home . 'tpl/specsList.html');

        $tpl->replace(
            [
                '[head]'            => get_head($title, $desc, $kw),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),
                '[ads1]'            => $ads[1],
                '[ads2]'            => $ads[2],
                '[ads3]'            => $ads[3],
                '[ads5]'            => $ads[5],
                '[ads6]'            => $ads[6],
                '[ads7]'            => $ads[7],
                '[specs]'           => $list,
                '[quiz]'            => file_get_contents($home . 'tpl/quiz.html'),
                '[footer]'          => file_get_contents($home . 'tpl/footer.html')
            ]
        );

        $tpl->out();
    }

    static function specDesc()
    {
        global $db, $tpl;
        $id = (int)$_GET['id'];

        //echo 'specDesc';

        $db->query(
            '
                SELECT 
                    a.`id`, a.`code`, a.`name`, a.`egeSet`, 
                    a.`descr`, a.`work`,
                    `okso_gr`.`name` AS gr
                FROM 
                    (SELECT `id`, `code`, `name`, `egeSet`, `descr`,`work` FROM `okso` WHERE `id`=?) a LEFT JOIN 
                `okso_gr` ON `okso_gr`.`id`=FLOOR(a.`code`/10000)',
            $id
        );
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $okso = $db->get_row();

        $reps = '';
        $t    = (int)substr($okso['code'], 3, 1);

        /*
        if($t === 4) {
            $lvl='магистратура';
            $path='mag/';
        } else {
            if($t === 3) {
                $lvl='бакалавриат';
                $path='';
            } else { // 5
                $lvl='специалитет';
                $path='';
            }
        }
        */

        switch ($t) {
            case 3 :
                $lvl  = 'бакалавриат';
                $path = '';
                break;
            case 4 :
                $lvl  = 'магистратура';
                $path = 'mag/';
                break;
            case 5 :
                $lvl  = 'специалитет';
                $path = '';
                break;
            case 6 :
                $lvl  = 'аспирантура';
                $path = 'phd/';
                break;
        }

        $code = substr_replace(substr_replace($okso['code'], '.', 2, 0), '.', 5, 0);

        $profs = '';
        $db->query('SELECT `name` FROM `okso_profiles` WHERE `okso_id` = ? ORDER BY `id`', $okso['id']);
        if ($cnt = $db->num_rows()) {
            while ($row = $db->get_row()) {
                $profs .= '<li>' . $row['name'] . '</li>';
            }
            $profs = '
                <section id="profs">
                    <h2>Профили</h2>
                    <ul>' . $profs . '</ul>';
            if ($cnt > 6) {
                $profs .= '<a href="#" class="more-link">Показать все</a>';
            }
            $profs .= '
                </section>';
        }

        $okso['code'] = (int)$okso['code'];
        $db->query(
            '
            SELECT `id`, `code`, `name` FROM `okso`
            WHERE ( 
                `code` = IFNULL((SELECT MIN(`code`) FROM `okso` WHERE `code`>?), 0) OR 
                `code` = IFNULL((SELECT MAX(`code`) FROM `okso` WHERE `code`<?), 0)
            )',
            $okso['code'],
            $okso['code']
        );
        $nav1 = $nav2 = '';
        while ($nav = $db->get_row()) {
            $nav['code'] = (int)$nav['code'];
            if ($okso['code'] > $nav['code']) {
                $nav1 = '<p>Предыдущая специальность</p>
					   <a href="/specs/' . $nav['id'] . '" class="spec-prev">' . $nav['name'] . '</a>';
            } else {
                $nav2 = '<p>Следующая специальность</p>
					   <a href="/specs/' . $nav['id'] . '" class="spec-next">' . $nav['name'] . '</a>';
            }
        }

        $mag = '';
        if ($t !== 4 && $t !== 6) {
            $db->query(
                'SELECT `id`, `name` FROM `vuz`.`okso` WHERE FLOOR(`code`/10000)=? AND MOD(FLOOR(`code`/100), 10)=4',
                floor($okso['code'] / 10000)
            );
            if ($db->num_rows()) {
                while ($row1 = $db->get_row()) {
                    $mag .= '<li><a href="/specs/' . $row1['id'] . '">' . $row1['name'] . ' (магистратура)</a></li>';
                }
                $mag = '<section>
                    <h2>Продолжить обучение в магистратуре</h2>
                    <nav><ul>' . $mag . '</ul></nav>    
                  </section>';
            }
        }

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $title = $okso['name'] . ' (' . $code . ') ' . $lvl;
        $desc  = $okso['name'] . ' (' . $code . ') ' . $lvl . ': описание программы и профессиональных навыков, кем работать, где учиться';
        $kw    = $okso['name'] . ', ' . $lvl . ', ' . $code . ', описание специальности, где учиться, кем работать';

        $tpl->start('tpl/specDesc.html');
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[name]'  => $okso['name'],
            '[lvl]'   => $lvl,
            '[code]'  => $code,
            '[gr]'    => $okso['gr'],
            '[profs]' => $profs,
            '[desc]'  => $okso['descr'],
            '[work]'  => $okso['work'],
            '[mag]'   => $mag,
            '[path]'  => $path,
            '[id]'    => $id,

            '[reps]' => $reps,

            '[nav<]' => $nav1,
            '[nav>]' => $nav2,

            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function faq()
    {
        global $db, $tpl;

        $id =& $_GET['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::err404();
        }

        $cat =& $_GET['cat'];
        if ($cat && $cat != 1 && $cat != 2 && $cat != 3) {
            myErr::err404();
        } else {
            $cat = 0;
        }

        $db->query(
            '
        SELECT `id`, `name`, `text`, `title`, `desc`, `keywords`, `p_id`
        FROM `faq` WHERE `id`=?',
            $id
        );
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $faq = $db->get_row();

        $tree = '';
        if ($faq['p_id']) {
            if ($faq['p_id'] < 4) {
                myErr::err404();
            }
            $db->query('SELECT `p_id` FROM `faq` WHERE `id`=?', $faq['p_id']);
            $t    = $db->get_row();
            $root = $t['p_id'];
        } else {
            $root = $id;
        }

        switch ($root) {
            case '1':
                $path = 'f';
                $navF = ' class="sel"';
                $navS = $navM = $navP = '';
                break;
            case '2':
                $path = 's';
                $navS = ' class="sel"';
                $navF = $navM = $navP = '';
                break;
            case '3':
                $path = 'm';
                $navM = ' class="sel"';
                $navF = $navS = $navP = '';
                break;
            case '29':
                $path = 'p';
                $navP = ' class="sel"';
                $navF = $navS = $navM = '';
                break;
        }

        $i = 0;
        $r = $db->query('SELECT `id`, `name` FROM `faq` WHERE `p_id`=?', $root);
        while ($hdr = $db->get_row($r)) {
            if (!$i) {
                $tree .= '<div class="col s6">';
            }
            $tree .= '<ul><li>' . $hdr['name'] . '</li>';
            $db->query('SELECT `id`, `name` FROM `faq` WHERE `p_id`=?', $hdr['id']);
            while ($name = $db->get_row()) {
                if ($name['id'] == $id) {
                    $tree .= '<li id="sel">' . $name['name'] . '</li>';
                } else {
                    $tree .= '<li><a href="/faq-' . $path . '/' . $name['id'] . '">' . $name['name'] . '</a></li>';
                }
            }
            $tree .= '</ul>';
            $i++;
            if ($i == 3) {
                $tree .= '</div>';
                $i    = 0;
            }
        }

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $tpl->start('tpl/faq.html');
        $tpl->replace([
            '[head]'            => get_head($faq['title'], $faq['desc'], $faq['keywords']),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            "[f]"    => $navF,
            "[s]"    => $navS,
            "[m]"    => $navM,
            "[p]"    => $navP,
            "[tree]" => $tree,

            "[name]" => $faq['name'],
            "[text]" => $faq['text'],

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function map()
    {
        global $db, $tpl;
        $id   = (int)$_GET['city'];
        $subj = (int)$_GET['subj'];
        if ($id === 77 || $id === 78) {
            $id  = (($id === 77) ? (26) : (44));
            $sql = '';
        } else {
            $sql = ' AND `subject_id`=' . $subj;
        }

        $db->query('SELECT `subject_id`, `rp` FROM `general`.`cities` WHERE `id`=? ' . $sql . ' AND `populat`>3', $id);
        if (!$db->num_rows()) {
            myErr::err404();
        }

        $city = $db->get_row();
        $path = $city['subject_id'] . '/';
        if ($id !== 26 && $id !== 44) {
            $path .= $id . '/';
        }

        $marks = '<script type="text/javascript">var m=new Array();';

        if ($_GET['photogr'] === '1') {
            $db->query(
                '
                SELECT a.*, `vuzCoords`.`long`, `vuzCoords`.`lat`
                FROM 
                    (
                        SELECT `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`abrev`, `vuzes`.`address`, `vuzes`.`noAbitur`
                        FROM `vuzes` WHERE `vuzes`.`city_id`=? AND `vuzes`.`delReason`=""
                    ) a LEFT JOIN
                    `vuzCoords` ON a.`id`=`vuzCoords`.`vuz_id` LEFT JOIN 
                    `gallery` ON a.`id`=`gallery`.`vuz_id`
                WHERE `vuzCoords`.`long` IS NOT NULL  AND `gallery`.`id` IS NULL
                GROUP BY a.`id`',
                $id
            );
        } else {
            $db->query(
                '
                SELECT a.*, `vuzCoords`.`long`, `vuzCoords`.`lat`
                FROM 
                    (
                        SELECT `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`abrev`, `vuzes`.`address`, `vuzes`.`noAbitur`
                        FROM `vuzes` WHERE `vuzes`.`city_id`=? AND `vuzes`.`delReason`=""
                    ) a LEFT JOIN
                    `vuzCoords` ON a.`id`=`vuzCoords`.`vuz_id`
                WHERE `vuzCoords`.`long` IS NOT NULL',
                $id
            );
        }/*
            $db->query('
                    SELECT a.*, `vuzCoords`.`long`, `vuzCoords`.`lat`
        FROM
            (
                SELECT `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`abrev`, `vuzes`.`address`, `vuzes`.`noAbitur`
                FROM `vuzes` WHERE `vuzes`.`city_id`=? AND `vuzes`.`delReason`=""
            ) a LEFT JOIN
            `vuzCoords` ON a.`id`=`vuzCoords`.`vuz_id`
        WHERE `vuzCoords`.`long` IS NOT NULL', $id);*/
        while ($row = $db->get_row()) {
            $marks .= 'm.push(new Array("' .
                str_replace('"', '', $row['abrev']) . '", "' .
                str_replace('"', '', $row['name']) . '", [' .
                $row['long'] . ',' . $row['lat'] . '], "' .
                str_replace('"', '', $row['address']) . '", "' .
                $path . 'v' . $row['id'] . '/", "' . (($row['noAbitur']) ? ('1') : ('0')) . '"));';
        }
        $marks .= '</script>';

        $vuzTypes = '
                            <div id="fastLinks-box">
                                    <section id="fast-links" class="bottom-links">
                                            <h2>Вузы ' . $city['rp'] . ' по направлениям</h2>
                                            <ul>';
        $db->query(
            '
                            SELECT b.`dir_id`, `name` 
                            FROM 
                                    (
                                            SELECT DISTINCT `dir_id` FROM 
                                                    (
                                                            SELECT `id` FROM `vuzes` WHERE `city_id`=? AND `delReason`=""
                                                     ) a LEFT JOIN 
                                                     `vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id` 
                                            HAVING `dir_id` IS NOT NULL
                                    ) b LEFT JOIN 
                                    `dir2specs` ON b.`dir_id`=`dir2specs`.`id` 
                            ORDER BY b.`dir_id`',
            $id
        );
        while ($t = $db->get_row()) {
            $vuzTypes .= '<li><a class="direct d' . $t['dir_id'] . '" href="/' . $path . 'd' . $t['dir_id'] . '/">' . $t['name'] . '</a></li>';
        }
        $vuzTypes .= '</ul>
                </section>
            </div>';

        $title = 'Вузы ' . $city['rp'] . ' на карте';
        $kw    = 'Вузы, ' . $city['rp'] . ', на карте, города';
        $desc  = 'Карта на которой отмечены все вузы ' . $city['rp'];

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $tpl->start('tpl/city-map.html');
        $tpl->replace([
            '[head]'            => get_head(
                $title,
                $desc,
                $kw,
                '<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>'
            ),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[marks]' => $marks,
            '[types]' => $vuzTypes,
            '[rp]'    => $city['rp'],
            '[path]'  => $path,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

    static function cities()
    {
        global $db, $tpl;

        $db->query(
            '
            SELECT `subjects`.`id` AS s_id, `subjects`.`name` AS subj, `cities`.`id` AS c_id, `cities`.`name` AS city
            FROM `general`.`subjects` LEFT JOIN `general`.`cities` ON `subjects`.`id`=`cities`.`subject_id`	
            WHERE `vuz_exists`="1" AND `subjects`.`id` NOT IN (77,78) 
            ORDER BY `subjects`.`name`, `cities`.`capital` DESC, `cities`.`name`'
        );

        $last = false;
        $tree = '';
        while ($row = $db->get_row()) {
            if ($last != $row['s_id']) {
                if ($last) {
                    $tree .= '</ul></section>';
                }

                $tree .= '<section><p><b>' . $row['subj'] . '</b></p><ul class="cities">';
                $last = $row['s_id'];
            }
            $tree .= '<li><a href="/' . $row['s_id'] . '/' . $row['c_id'] . '/">' . $row['city'] . '</a></li>';
        }
        $tree .= '</ul></section>';

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads);

        $title = 'Вузы в городах России';
        $kw    = 'вузы субъект город список России';
        $desc  = 'Вузы по городам и субъектам России';

        $tpl->start('tpl/cities.html');
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[tree]' => $tree,

            '[ads1]' => $ads[1],
            '[ads2]' => $ads[2],
            '[ads3]' => $ads[3],
            '[ads5]' => $ads[5],
            '[ads6]' => $ads[6],
            '[ads7]' => $ads[7],

            '[quiz]' => file_get_contents('tpl/quiz.html'),

            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

}
