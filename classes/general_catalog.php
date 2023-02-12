<?php

class general_catalog
{
    public static array $cityWithDirections = [
        // EDUNET-530
        106, // Барнаул
        5,   // Владивосток
        65,  // Ижевск
        13,  // Иркутск
        17,  // Кемерово
        25,  // Махачкала
        72,  // Набережные Челны
        35,  // Оренбург
        535, // Севастополь
        27,  // Ставрополь
        93,  // Тольятти
        50,  // Томск
        53,  // Ульяновск
        55,  // Хабаровск
        64,  // Ярославль

        // EDUNET-559
        2, //Астрахань
        74, //Балашиха
        15, //Калининград
        18, //Киров
        22, //Курск
        23, //Липецк
        484, //Магнитогорск
        92, //Новокузнецк
        36, //Пенза
        41, //Рязань
        47, //Сочи
        49, //Тверь
        51, //Тула
        141, //Улан-Удэ
        57, //Чебоксары

        //EDUNET-582
        467, //Абакан
        517, //Альметьевск
        515, //Ангарск
        514, //Арзамас
        513, //Армавир
        1, //Архангельск
        432, //Ачинск
        131, //Балаково
        436, //Березники
        511, //Бийск
        516, //Благовещенск
        507, //Братск
        480, //Великий Новгород
        147, //Видное
        6, //Владикавказ
        444, //Волгодонск
        9, //Вологда
        502, //Дербент
        460, //Дзержинск
        117, //Домодедово
        2240, //Евпатория
        498, //Ессентуки
        84, //Жуковский
        429, //Златоуст
        483, //Йошкар-Ола
        139, //Каменск-Шахтинский
        430, //Камышин
        317, //Каспийск
        2426, //Керчь
        137, //Кисловодск
        490, //Ковров
        75, //Коломна
        488, //Комсомольск-на-Амуре
        122, //Королев
        19, //Кострома
        69, //Красногорск
        486, //Курган
        2269, //Курган-16
        471, //Кызыл
        110, //Люберцы
        24, //Майкоп
        394, //Миасс
        28, //Мурманск
        94, //Муром
        88, //Мытищи
        494, //Назрань
        29, //Нальчик
        496, //Находка
        413, //Невинномысск
        96, //Нефтекамск
        105, //Нижневартовск
        417, //Нижнекамск
        114, //Новомосковск
        107, //Новороссийск
        479, //Новочеркасск
        115, //Норильск
        386, //Ноябрьск
        138, //Обнинск
        113, //Одинцово
        34, //Орел
        124, //Орехово-Зуево
        433, //Орск
        491, //Петрозаводск
        38, //Петропавловск-Камчатский
        99, //Прокопьевск
        39, //Псков
        495, //Пятигорск
        459, //Рубцовск
        476, //Рыбинск
        426, //Северодвинск
        441, //Северск
        125, //Сергиев Посад
        80, //Серпухов
        437, //Старый Оскол
        97, //Стерлитамак
        452, //Сызрань
        489, //Сыктывкар
        473, //Таганрог
        48, //Тамбов
        472, //Тобольск
        477, //Уссурийск
        56, //Ханты-Мансийск
        425, //Хасавюрт
        83, //Химки
        136, //Череповец
        59, //Черкесск
        142, //Шахты
        91, //Электросталь
        61, //Элиста
        461, //Энгельс
        62, //Южно-Сахалинск
    ];

    static function numberof($number, $value, $suffix)
    {
        // не будем склонять отрицательные числа
        $number     = abs($number);
        $keys       = [2, 0, 1, 1, 1, 2];
        $mod        = $number % 100;
        $suffix_key = $mod > 4 && $mod < 20 ? 2 : $keys[min($mod % 10, 5)];

        return $value . $suffix[$suffix_key];
    }

    static function catalog_build($u_id, $subjectId, $cityId, $get_params, $dbg_log = false)
    {
        global $tpl, $db;
        $home   = HOME . 'vuz.edunetwork.ru/';
        $lmDate = 'Thu, 01 Oct 2022 17:52:14';
        $robots = '';

        $db->hideErr = 0;
        /* basic WHERE for vuzes table */
        $vWHERE  = '`delReason`="" AND ';
        $sWHERE  = '';
        $bigCity = $directionCity = false;
        $Y       = date('Y');
        $educCh  = $get_params['form'];

        if ($dbg_log) {
            self::dbg_log($db,'catalog_build', 'stage 1');
        }

        if ($subjectId !== 99) {
            if ($subjectId > 84) {
                myErr::err404();
            }

            $vWHERE .= ' `vuzes`.`subj_id`=' . $subjectId . ' AND ';

            if ($subjectId === 77 || $subjectId === 78) {
                $bigCity = true;
            }
            if ($cityId) {
                if ($subjectId === 77 || $subjectId === 78) {
                    myErr::err404();
                }


                    $db->query(
                        'SELECT `name`, `rp`, `populat` FROM `general`.`cities` WHERE `id` = ? AND `subject_id` = ?',
                        $cityId,
                        $subjectId
                    );
                    if ($db->num_rows()) {
                        $vWHERE .= '`vuzes`.`city_id`=' . $cityId . ' AND ';
                        $city   = $db->get_row();
                        if ($city['populat'] > 3) {
                            $bigCity = true;
                        }
                        if(in_array($cityId, self::$cityWithDirections)) {
                            $directionCity = true;
                        }

                    } else {
                        myErr::err404();
                    }

            }

            if (isset($get_params['direct'])) {
                //echo 'we are here!!!';
                $directId = intval($get_params['direct']);
                if ((!$bigCity && !$directionCity) || !$directId || $get_params['spec']) {
                    myErr::err404();
                }
            } else {
                $directId = false;
            }
        }

        /* PREPARE FROM IF ELSEIF ELSE */
        $vuzes     = '';
        $dir_specs = '';

//        if($subjectId === 99) {
//            $basePath='/dist/';
//        } else {
//            $basePath='/'.$subjectId.'/'.(($cityId) ? ($cityId.'/') : (''));
//        }
        $basePath = '/' . $subjectId . '/' . (($cityId) ? ($cityId . '/') : (''));

        $graph  = '';
        $filTpl = new tpl;
        if ($directId) { // Direct
            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'directId');
            }

            $vWHERE .= '`vuzes`.`id` IN (SELECT `vuz_id` FROM `vuz2direct` WHERE `dir_id`=' . $directId . ') AND ';

            if ($subjectId === 77 || $subjectId === 78) {
                $filTpl->start($home . 'tpl/filter.html');
            } else {
                $filTpl->start($home . 'tpl/filterS.html');
            }

            $filter = $filTpl->replace([
                '[spec]'     => '',
                '[oksoId]'   => '',
                '[basePath]' => $basePath,
                '[gosCh]'    => '',
                '[freeCh]'   => '',
                '[milCh]'    => '',
                '[hosCh]'    => '',
                '[frmO]'     => '',
                '[frmOZ]'    => '',
                '[frmZ]'     => '',
                '[frmD]'     => '',
            ]);
            unset($filTpl);
        } else { // default catalog
            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'default catalog');
            }

            if ($get_params['gos'] === 'y') {
                $vWHERE .= '`vuzes`.`gos`="1" AND ';
                $gosCh  = ' checked="checked"';
            }
            if ($subjectId !== 99) {
                if ($get_params['mil'] === 'y') {
                    $vWHERE .= '`vuzes`.`military`="1" AND ';
                    $milCh  = ' checked="checked"';
                }
                if ($get_params['hos'] === 'y') {
                    $vWHERE .= '`vuzes`.`hostel`="1" AND ';
                    $hosCh  = ' checked="checked"';
                }
            }
            if ($get_params['spec'] && !preg_match('/^\d+$/', $get_params['spec'])) {
                myErr::err404();
            }
            $spec = intval($get_params['spec']);

            $level =& $get_params['lvl'];
            if ($level) {
                if ($level !== 'f' && $level !== 's' && $level !== 'm' && $level !== 'p') {
                    myErr::err404();
                }
            } else {
                $level = 'f';
            }

            //echo $level;

            switch ($level) {
                case 'f':
                    $lvlF = ' selected="selected"';
                    $lvlS = $lvlM = $lvlP = '';
                    break;
                case 's':
                    $sWHERE .= (!$spec ? 'MOD(FLOOR(`okso`.`code`/100), 10) != 4 AND ' : '') . ' `s`="1" AND ';
                    $lvlS   = ' selected="selected"';
                    $lvlF   = $lvlM = $lvlP = '';
                    break;
                case 'm':
                    if (!$spec) {
                        $sWHERE .= 'MOD(FLOOR(`okso`.`code`/100), 10) = 4 AND ';
                    }
                    $lvlM = ' selected="selected"';
                    $lvlF = $lvlS = $lvlP = '';
                    break;
                case 'p':
                    if (!$spec) {
                        $sWHERE .= 'MOD(FLOOR(`okso`.`code`/100), 10) = 6 AND ';
                    }
                    $lvlP = ' selected="selected"';
                    $lvlF = $lvlS = $lvlM = '';
                    if ($subjectId != 77 && $subjectId != 78) {
                        $robots = '<meta name="robots" content="noindex, nofollow" />';
                    }
                    break;
            }
            $form = intval($get_params['form']);
            if (!$form) {
                $form = 1;
            }

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
                default:
                    myErr::err404();
                    break;
            }

            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'form&level');
            }

            if ($bigCity || $directionCity /*|| $subjectId === 99*/) {
                $simple = false;
                /*if($subjectId === 99) {
                    $vWHERE.='`vuzes`.`parent_id` IS NULL AND ';
                    $form=4;
                } else {*/
//                    $form=(int) $_GET['form'];
//
//                    if (!$form) {
//                        $form = 1;
//                    }
//                }
                if(!$directionCity)
                    $sWHERE .= ' `vs`.`form` = "' . $form . '" AND ';

//                switch($form) {
//                    case 1: $formO=' selected="selected"'; $formOZ=$formZ=$formD=""; break;
//                    case 2: $formOZ=' selected="selected"'; $formO=$formZ=$formD=""; break;
//                    case 3: $formZ=' selected="selected"'; $formO=$formOZ=$formD=""; break;
//                    case 4: $formD=' selected="selected"'; $formO=$formOZ=$formZ=""; break;
//                    default: myErr::err404(); break;
//                }

                if ($get_params['free'] === 'y') {
                    $sWHERE .= '`vs`.`free` != 0 AND ';
                    $freeCh = ' checked="checked"';
                }

                if ($spec) {
                    $db->query('SELECT `name`, `code` FROM `vuz`.`okso` WHERE `id` = ?', $spec);
                    if (!$db->num_rows()) {
                        myErr::err404();
                    }
                    $okso = $db->get_row();

                    $sWHERE .= '`vs`.`okso_id` = ' . $spec . ' AND ';

                    if ($u_id) { // compare marks
                        $sqlSEL  = ' c.favor, ';
                        $sqlFROM = ' LEFT JOIN (SELECT `spec_id` AS favor FROM `favor` WHERE `u_id` = ' . $u_id . ' AND `s` = "' . (($level === 's') ? ('1') : ('0')) . '") c ON a.`id` = c.favor';
                    } else {
                        $sqlSEL = $sqlFROM = '';
                    }

                    $prefix = ($level === 's' ? 's' : 'f');
                }

                $checkSubjectId = ($subjectId == 77 || $subjectId == 78) ? true : false;

                $filTpl->start($home . 'tpl/filter.html');
                $filter = $filTpl->replace([
                    "[basePath]"       => $basePath,
                    "[checkSubjectId]" => $checkSubjectId,
                    "[lvlF]"           => $lvlF,
                    "[lvlS]"           => $lvlS,
                    "[lvlM]"           => $lvlM,
                    "[lvlP]"           => $lvlP,
                    "[gosCh]"          => $gosCh,
                    "[freeCh]"         => $freeCh,
                    "[milCh]"          => $milCh,
                    "[hosCh]"          => $hosCh,
                    "[frmO]"           => $formO,
                    "[frmOZ]"          => $formOZ,
                    "[frmZ]"           => $formZ,
                    "[frmD]"           => $formD,
                    "[spec]"           => $okso['name'],
                    "[oksoId]"         => $spec ? $spec : '',
                ]);
            } else {
                $simple = true;

                if ($spec) {
                    $db->query('SELECT `name`, `code` FROM `vuz`.`okso` WHERE `id` = ?', $spec);
                    if (!$db->num_rows()) {
                        myErr::err404();
                    }
                    $okso = $db->get_row();

                    $sWHERE .= '`lic_okso`.`okso_id`="' . $spec . '" AND ';
                }

                $filTpl->start('tpl/filterS.html');
                $filter = $filTpl->replace([
                    "[basePath]" => $basePath,
                    "[lvlF]"     => $lvlF,
                    "[lvlS]"     => $lvlS,
                    "[lvlM]"     => $lvlM,
                    "[lvlP]"     => $lvlP,
                    "[gosCh]"    => $gosCh,
                    "[milCh]"    => $milCh,
                    "[hosCh]"    => $hosCh,
                    "[frmO]"     => $formO,
                    "[frmOZ]"    => $formOZ,
                    "[frmZ]"     => $formZ,
                    "[frmD]"     => $formD,
                    "[spec]"     => $okso['name'],
                    "[oksoId]"   => $spec ? $spec : '',
                ]);
            }
        }

        $sqls = [];

        $vuzesCount = 0;
        $paging     = '';
        $startPos   = 0;
        $sqls[] = $vWHERE;
        $vWHERE     = substr($vWHERE, 0, -4);
        $sqls[] = $vWHERE;

        $sqls[] = $sWHERE;
        $sWHERE     = substr($sWHERE, 0, -4);
        $sqls[] = $vWHERE;

//        if(!$directId && ($subjectId === 77 || $subjectId === 78 || $subjectId === 99)) {
        try {
            $sql_wt = '
                    SELECT COUNT(*) AS c 
                    FROM (
                        SELECT COUNT(*) 
                        FROM 
                            (SELECT `vuzes`.`id` FROM `vuz`.`vuzes` WHERE ' . $vWHERE . ') a 
                            LEFT JOIN 
                            ' . ($simple ? '`lic_okso` ON `lic_okso`.`vuz_id` = a.`id`' : '`specs` as vs ON `vs`.`vuz_id` = a.`id`') . '
                            ' . (($level != 'f') ? ('LEFT JOIN `okso` ON `okso`.`id` = `okso_id`') : ('')) .
                ($sWHERE ? ' WHERE ' . $sWHERE : '') . '
                    GROUP BY a.`id`) z
                    ';

            $sqls[] = $sql_wt;
            $db->queryWithThrow($sql_wt);

            $t     = $db->get_row();
            $total = $t['c'];
        } catch (\Exception $exception) {
            // PR($exception->getMessage());
            $total = 0;
        }

        if ($dbg_log) {
            self::dbg_log($db,'catalog_build', 'SQL executed');
        }

        if ($total > 30) {
            require($home . "classes/paging.php");
            $p        = new paging();
            $paging   = '<div id="paging">' . $p->pages($total, 30) . '</div>';
            $startPos = $p->Page() * 30;
            if ($startPos > $total) {
                myErr::err404();
            }
            unset($p);
            $LIMIT = ' LIMIT ' . $startPos . ', 30';
        }
//        }

        $SQL = '
                SELECT 
                        v.*, `metros`.`name` AS metro, 
                        IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id, round(avg(vs.free)) as budg_places,
                        IFNULL(o.ops, 0) AS ops, IFNULL(sp.specs, 0) AS specs,  
                        round(avg(vs.f_score)) as budg_scores, round(avg(vs.p_score)) as payed_scores, round(avg(vs.f_cost)) as avg_price,
                        IF(v.`packet`="sert" OR v.`partner`="1", RAND(), 0) AS randseed 
                FROM (										
                        SELECT 
                                `vuzes`.`id`, `vuzes`.`name`, `vuzes`.`subj_id`, `vuzes`.`city_id`, `vuzes`.`editTime`,
                                `vuzes`.`gos`, `vuzes`.`hostel`, `vuzes`.`military`, `vuzes`.`vedom`,
                                `vuzes`.`metro_id`, `vuzes`.`rating`, `vuzes`.`noAbitur`, `vuzes`.`ege`,
                                `vuzes`.`short_seo`,`vuzes`.`logo`, `vuzes`.`esi`, vds.name as dir_name,  
                                IF(`vuzes`.`packetEnd`>DATE(NOW()), "sert", "") AS packet, `vuzes`.`partner`
                        FROM `vuz`.`vuzes`
                        
                        LEFT JOIN vuz.vuz2direct vvd on vvd.vuz_id = vuzes.id
                        LEFT JOIN vuz.dir2specs vds on vds.id = vvd.dir_id
                        WHERE ' . $vWHERE . ' AND `vuzes`.`delReason`=""
                ) v 
                LEFT JOIN vuz.specs vs on vs.vuz_id = v.id';
        //
        //avg(vs.f_score) as budg_ege,

        if ($simple) {
            if ($spec || $level !== 'f') {
                $SQL .= '
                LEFT JOIN `lic_okso` ON `lic_okso`.`vuz_id` = v.`id` 
                LEFT JOIN `okso` ON `okso`.`id`=`lic_okso`.`okso_id`';
            }
        } else {
            if (!$spec && $level !== 'f') {
                $SQL .= '
                LEFT JOIN `okso` ON `okso`.`id`= vs.`okso_id`';
            }
        }
        $SQL .= '
            LEFT JOIN `user2vuz` ON `user2vuz`.`vuz_id`=v.`id`
            LEFT JOIN`general`.`metros` ON `metros`.`id`=v.`metro_id` 
            LEFT JOIN (
                    SELECT `vuz_id`, count(*) AS specs ' . ($level === 's' ? ', `s`' : '') . '
                    FROM `vuz`.`specs`
                    GROUP BY `vuz_id`
            ) sp ON sp.`vuz_id` = v.`id` 
            LEFT JOIN (
                    SELECT `vuz_id`, count(*) AS ops 
                    FROM `vuz`.`opinions`
                    WHERE `approved`="1"
                    GROUP BY `vuz_id`
            ) o ON o.`vuz_id` = v.`id`';
        if ($sWHERE) {
            $SQL .= ' WHERE ' . str_replace('`s`', 'sp.`s`', $sWHERE);
        }
        $SQL .= '	GROUP BY v.`id` ORDER BY v.`packet` DESC, v.`partner` DESC, randseed, v.`rating` DESC ' . $LIMIT;

        //echo ($SQL);
        $r = $db->query($SQL);

        if ($dbg_log) {
            self::dbg_log($db,'catalog_build', 'SQL 2 executed');
        }


        $ads = [1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => ''];
        ads::get($ads, $subjectId, 4, $spec);

        $metro = '';
        if ($subjectId === 99) {
            $rp = 'дистанционно';
            if (intval($_GET['form']) === 4) {
                $rp = '';
            }
            $subjCity = 99;
        } else {
            $db->query('SELECT `name`, `rp` FROM `general`.`subjects` WHERE `id` = ?', $subjectId);
            $subject = $db->get_row();
            if ($cityId) {
                $rp       = $city['rp'];
                $subjCity = $city['name']; // for keywords new filter
            } else {
                $rp       = $subject['rp'];
                $subjCity = $subject['name'];
            }
            if ($subjectId === 78) {
                $metro = ' spb';
            }
        }

        $bubbleDate = strtotime('01/02/2020');
        $lmDate     = date('D, d M Y H:i:s', $bubbleDate);
        if ($numberVuzes = $db->num_rows($r)) {
            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'vuzes enumerated');
            }

            while ($vuz = $db->get_row($r)) {
                $bufDate = strtotime($vuz['editTime']);
                if ($bubbleDate < $bufDate) {
                    $bubbleDate = $bufDate;
                    $lmDate     = date('D, d M Y H:i:s', $bubbleDate);
                }

                $vuzesCount++;
                $vuz['subj_id'] = intval($vuz['subj_id']);
                $catPath        = '/' . $vuz['subj_id'] . '/' . (($vuz['subj_id'] !== 77 && $vuz['subj_id'] !== 78) ? ($vuz['city_id'] . '/') : (''));
                // if(intval($_GET['page']) === 1){
                //     if ($vuzesCount == 3) {
                //     $vuzes .= '
                //         <section class="banner_section_mobile">
                //             <a href="https://synergy.ru/lp/it-college/?utm_source=edunetwork_lp&utm_medium=edunetwork_lp&utm_campaign=edunetwork&utm_term=it_college&marketer=okd&produkt=109903289&utm_gen=3">
                //                 <img src="/tpl/imgs/banner-mobile.png" alt="Баннер на день открытых дверей">
                //             </a>
                //         </section>
                //         <section class="banner_section_tablet">
                //             <a href="https://synergy.ru/lp/it-college/?utm_source=edunetwork_lp&utm_medium=edunetwork_lp&utm_campaign=edunetwork&utm_term=it_college&marketer=okd&produkt=109903289&utm_gen=3">
                //                 <img src="/tpl/imgs/banner-tablet.png" alt="Баннер на день открытых дверей">
                //             </a>
                //         </section>';
                //     }
                // }

                if ($vuz['logo']) {
                    $logo_src = 'https://vuz.edunetwork.ru/files/' . $vuz['id'] . '/logo.' . $vuz['logo'];
                    //$logo_src = '/files/'.$vuz['id'].'/logo.'.$vuz['logo'];
                } else {
                    $logo_src    = '//static.edunetwork.ru/imgs/tpl/noLogo.png" alt="Нет логотипа';
                    $row['logo'] = '<img src="//static.edunetwork.ru/imgs/tpl/noLogo.png" alt="Нет логотипа" />';
                }

                //$esi = '—';
                $esi = '-';
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

                //echo 'Военные ' . $vuz['military'];
                /*
                if ($vuz['military'] === '1') {
                    $esi = '-';
                }
                */

                $vuzes .= '
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
                if ($vuz['randseed']) {
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

                $places       = $vuz['budg_places'] == 0 ? "-" : $vuz['budg_places'];
                $payed_places = $vuz['avg_price'] == 0 ? "-" : "есть";
                $budg_scores  = $vuz['budg_scores'] == 0 ? "-" : $vuz['budg_scores'];
                $payed_scores = $vuz['payed_scores'] == 0 ? "-" : $vuz['payed_scores'];

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
                if ($vuz['short_seo']) {
                    $vuzes .= '<p>' . $vuz['short_seo'] . '</p>';
                }

                if ($vuz['noAbitur']) {
                    $vuzes .= '
                        <div class="noAbitur">
                                <p>В вуз запрещен набор абитуриентов</p>
                                <p>Обновлено ' . $vuz['noAbitur'] . '</p>
                        </div>';
                }

                if ($spec && !$simple) {
                    $suffix  = (($level === 's') ? ('s') : ('')); // need for favorites links
                    $sql_sub = '
                        SELECT a.*, ' . $sqlSEL . '`okso`.`name`
                        FROM	
                                (SELECT `id`, `okso_id`, `prof`, `free`, `f_score`, `p_score`, `f_cost` 
                                 FROM `specs` as vs  WHERE `vs`.`vuz_id`= ' . $vuz['id'] . ' AND ' . $sWHERE . ') a LEFT JOIN 
                                 `okso` ON `okso`.`id`=a.`okso_id` ' . $sqlFROM . '
                        GROUP BY a.id ';
                    $db->query($sql_sub);

                    //echo ($sql_sub.'<br>');

                    while ($sp = $db->get_row()) {
                        $vuzes .= '
							<div class="unit-spec">
								<p class="spec-name wowtest">
									<a href="' . $catPath . 'v' . $vuz['id'] . '/specs/#spec-' . $sp['id'] . $suffix . '">' . $sp['name'] . '</a>
									<i class="material-icons spec-favor' . (($sp['favor']) ? (' added') : ('')) . '" data-specid="' . $sp['id'] . '"></i>
								</p>';
                        if ($sp['prof']) {
                            $vuzes .= '
								<div class="spec-profiles">
									<div class="truncate">
										<span class="hide-on-small-only">Профили: </span>' . $sp['prof'] . '
									</div>
								</div>';
                        }

                        $vuzes .= '
								<div class="row spec-stats">';
                        if ($level !== 's') {
                            if ($sp['free']) {
                                if ($sp['free'] === '1' && $vuz['vedom']) {
                                    $vuzes .= '<div class="col s4 free">Бюджетные места: <span>есть</span></div>';
                                } else {
                                    $vuzes .= '<div class="col s4 free">Бюджетных мест: <span>' . $sp['free'] . '</span></div>';
                                }
                            } else {
                                $vuzes .= '<div class="col s4 nofree">Бюджетных мест: <span>нет</span></div>';
                            }
                        }
                        if ($sp[$prefix . '_cost']) {
                            $vuzes .= '<div class="col s4 cost"><span>' . number_format(
                                    $sp[$prefix . '_cost'],
                                    0,
                                    ',',
                                    ' '
                                ) . '</span> рублей в год</div>';
                        }
                        $vuzes .= '</div>';
                        if ($level === 'f') {
                            $vuzes .= '
								<div class="row spec-stats">
									<div class="col s4 score">Проходной балл: <span>' . ($sp['f_score'] ? $sp['f_score'] : '—') . '</span></div>
									<div class="col s4 score">Проходной балл: <span>' . ($sp['p_score'] ? $sp['p_score'] : '—') . '</span></div>
								</div>';
                        }
                        $vuzes .= '</div>';
                    }
                }
                $vuzes .= '
						<div class="row unit-stats">
							<div class="col m12 l6 truncate">';
                //if($vuz['ege']) {$vuzes.='<span class="ege">Средний ЕГЭ: '.number_format($vuz['ege'], 2).'</span>';}
                if ($vuz['metro']) {
                    $vuzes .= '<span class="metro' . $metro . '">' . $vuz['metro'] . '</span>';
                }
                $vuzes .= '	</div>
							<div class="col m12 l6 right-align truncate">
								<span class="specs"><a href="' . $catPath . 'v' . $vuz['id'] . '/'
                    . ($vuz['specs'] ? 'specs/">Специальности (' . $vuz['specs'] . ')' : '#lic_okso">Специальности') .
                    '</a></span>
								<span class="opins"><a href="' . $catPath . 'v' . $vuz['id'] . '/opinions/">Отзывы (' . $vuz['ops'] . ')</a></span>
							</div>
						</div>
					</div></div></div>';
            }
            $vuzes = '<div id="units-list">' . $vuzes . "</div>";

            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'vuzes rendered');
            }

        } else {
            $robots = '<meta name="robots" content="noindex, nofollow" />';
            $vuzes  = '<div id="no-result"><p>Поиск</p><p>Ничего не найдено</p></div>';
            if ($spec && $subjectId !== 99) {
                if ($level === 's') {
                    $catPath = $basePath . 'sec/';
                } elseif ($level === 'm') {
                    $catPath = $basePath . 'mag/';
                } elseif ($level === 'p') {
                    $catPath = $basePath . 'phd/';
                }
                $db->query(
                    '
                    SELECT `id`, `name` FROM `vuz`.`okso` 
                    WHERE 
                            FLOOR(`okso`.`code`/100) = (
                                    SELECT FLOOR(`okso`.`code`/100) FROM `vuz`.`okso` WHERE `okso`.`id`=?
                            ) AND `id`!=?',
                    $spec,
                    $spec
                );
                if ($db->num_rows()) {
                    $dir_specs = '
						<section id="rel-specs" class="bottom-links">
							<h2>Вузы ' . $rp . ' по смежным специальностям</h2>
							<ul>';

                    while ($row = $db->get_row()) {
                        $dir_specs .= '<li><a href="' . $catPath . '?spec=' . $row['id'] . '">' . $row['name'] . '</a></li>';
                    }
                    $dir_specs .= '</ul></section>';
                }
                if ($dbg_log) {
                    self::dbg_log($db,'catalog_build', 'okso rendered');
                }

            }
        }

        $vuzTypes = '';
        if ($subjectId !== 99) {
            /* Opendays */
            if ($bigCity) {
                $dods    = '
						<div id="unitsOnMap" class="dod-map">
							<i class="material-icons">location_on</i>
							<a href="' . $basePath . 'map">Вузы на карте</a>
							<span>Вузы ' . $rp . ' на карте города</span>
						</div>
						<div id="dods" class="dod-map">
							<i class="material-icons">event</i>
							<a href="' . $basePath . 'openDays">Дни открытых дверей</a>
							<span>Календарь дней открытых дверей вузов ' . $rp . '</span> 
						</div>';
                $egeCity = '<li><a href="' . $basePath . 'ege" rel="nofollow"><i class="material-icons">beenhere</i>Вузы ' . $rp . ' по ЕГЭ</a></li>';
            } else {
                $dods = $egeCity = '';
            }

            /* Direct block */
            if ($bigCity || $directionCity) {
                $vuzTypes    = '
				<div id="fastLinks-box" class="fastLinks-box">
					<section id="fast-links" class="bottom-links">
						<h2>Вузы ' . $rp . ' по направлениям</h2>
						<ul>';
                $vuzAddTypes = '
				<div class="fastLinks-box">
					<section id="fast-links-bottom" class="bottom-links">
						<ul>';
                if ($cityId) {
                    $tCity = $cityId;
                } else {
                    $tCity = (($subjectId === 77) ? (26) : (44));
                }
                if ($directId || $directId > 0) {
                    $wdID = ' WHERE `dir_id` <> ' . $directId;
                } else {
                    $wdID = '';
                }
                $sql_tiles = '
                    SELECT b.`dir_id`, `name`, `position`
                    FROM 
                        (
                            SELECT DISTINCT `dir_id` FROM 
                                (
                                        SELECT `id` FROM `vuz`.`vuzes` WHERE `city_id` = ' . $tCity . ' AND `delReason`=""
                                 ) a 
                            LEFT JOIN `vuz`.`vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id`
                            ' . $wdID . ' 
                            HAVING `dir_id` IS NOT NULL
                        ) b 
                    LEFT JOIN `vuz`.`dir2specs` ON b.`dir_id`=`dir2specs`.`id` 
                    ORDER BY b.`dir_id`';
                //var_dump($sql_tiles);
                $db->query($sql_tiles);

                $up_tiles = $lw_tiles = 0;
                while ($t = $db->get_row()) {
                    //var_dump($t);
                    if (empty($t['position'])) {
                        $up_hidden_class = ($up_tiles >= 7) ? ' class = "up_tile_hidden none-btn"' : '';
                        $vuzTypes        .= '<li' . $up_hidden_class . '><a class="direct d' . $t['dir_id'] . '" href="' . $basePath . 'd' . $t['dir_id'] . '/">' . $t['name'] . '</a></li>';
                        $up_tiles++;
                    } elseif ($t['position'] === 'f') {
                        $lw_hidden_class = ($lw_tiles >= 5) ? ' class = "up_tile_hidden none-btn"' : '';
                        $vuzAddTypes     .= '<li' . $lw_hidden_class . '>
                            <a class="direct d' . $t['dir_id'] . '" href="' . $basePath . 'd' . $t['dir_id'] . '/">' . $t['name'] . '</a>
                        </li>';
                        $lw_tiles++;
                    }
                }
                $up_more = $lw_more = '';
                if ($up_tiles > 5) {
                    $up_more = '<a class="fast-links-more"><i class="material-icons">expand_more</i><span>Показать ещё</span></a>';
                }
                if ($lw_tiles > 5) {
                    $lw_more = '<a class="fast-links-more bottom"><i class="material-icons">expand_more</i><span>Показать ещё</span></a>';
                }
                $vuzTypes    .= '</ul>
					</section>
                    ' . $up_more . '
				</div>';
                $vuzAddTypes .= '</ul>
					</section>
                    ' . $lw_more . '
				</div>';

                if ($level === 'p') {
                    $vuzTypes = '';
                    //$vuzAddTypes = ''; // TODO ???
                };
            }

            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'vuzes tiles');
            }


            $navRight = '
					<a href="/dist/" class="valign-wrapper">
						<i class="material-icons small grey-text hide-on-small-only">leak_add</i>
						Образование<br />дистанционно
					</a>';

            /* BOTTOM ARTICLES */
            if (!$startPos && !$spec && !$directId && $subjectId !== 99) {
                if ($cityId) {
                    $db->query(
                        '
                            SELECT  `articles`.`id`, `articles`.`name`, `articles`.`about`
                            FROM `knowledge`.`articles`
                            WHERE `c_id` = 3 AND `city` = ? AND `show_date` <= NOW() 
                            ORDER BY `show_date` 
                            DESC LIMIT 2',
                        $cityId
                    );
                    $t =& $city['rp'];
                } else {
                    $db->query(
                        '
						SELECT `articles`.`id`, `articles`.`name`, `articles`.`about`
						FROM 
							`knowledge`.`articles` 
							LEFT JOIN `general`.`cities` ON `articles`.`city`=`cities`.`id`
						WHERE `articles`.`c_id` = 3 AND `cities`.`subject_id`=? AND `show_date`<=NOW() 
						ORDER BY `articles`.`show_date` 
						DESC LIMIT 2',
                        $subjectId
                    );
                    $t =& $subject['rp'];
                }
                // if ($db->num_rows()) {
                //     $articles = '
                // 		<section id="articles">
                // 			<div class="container">
                // 				<h3>Обзоры и рейтинги вузов '.$t.'</h3>
                // 				<div class="row">';
                //     while ($art = $db->get_row()) {
                //         $articles .= '
                // 			<article class="col s12 m6">
                // 				<h4><a href="/reviews/'.$art['id'].'">'.$art['name'].'</a></h4>
                // 				<p>'.$art['about'].'</p>
                // 			</article>';
                //     }
                //     $articles .= '
                // 				</div>
                // 				<p class="all-articles"><a href="/reviews/">Все статьи на эту тему</a></p>
                // 			</div>
                // 		</section>';
                // }
            }
        } else {
            $dods = $navRight = '';
        }

        /* SEO ELEMENTS :( */
        /* Direct selected */
        $findAttrs = '';
        if ($directId) {

            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'direction selected');
            }

            $db->query('SELECT `name`, `ids`, `dp` FROM `vuz`.`dir2specs` WHERE `id` = ?', $directId);
            if (!$db->num_rows()) {
                myErr::err404();
            }
            $direction = $db->get_row();
            $dir_specs = '
				<section id="rel-specs" class="bottom-links">
					<h2>Все вузы ' . $rp . ' по ' . $direction['dp'] . ' специальностям</h2>
					<ul>';
            $db->query('SELECT `id`, `name` FROM `vuz`.`okso` WHERE `id` IN (' . $direction['ids'] . ')');
            while ($row = $db->get_row()) {
                $dir_specs .= '<li><a href="' . $basePath . '?spec=' . $row['id'] . '">' . $row['name'] . '</a></li>';
            }
            $dir_specs .= '</ul></section>';
            // UPDATE `vuzes` JOIN (SELECT vuz_id, ROUND(AVG(f_cost)) AS cost FROM specs WHERE form="1" AND vuz_id IN (SELECT DISTINCT vuz_id FROM `vuz2direct` WHERE dir_id!=2) GROUP BY vuz_id) zz ON zz.vuz_id=vuzes.id SET vuzes.cost=zz.cost
            if ($directId !== 2) { // mil
                $db->query(
                    '
					SELECT SUBSTRING(`abrev`, 1, 16) AS abrev, `ege`, `cost` 
					FROM `vuz`.`vuzes` 
					WHERE 
						`delReason`="" AND `city_id`=? AND `ege` IS NOT NULL AND
						`id` IN (SELECT `vuz_id` FROM `vuz`.`vuz2direct` WHERE `dir_id`=?)
					ORDER BY `ege` DESC',
                    $tCity,
                    $directId
                );
                if ($db->num_rows() > 1) {
                    while ($row = $db->get_row()) {
                        $graph .= $row['abrev'] . '|' . $row['ege'] . '|' . $row['cost'] . "\n";
                    }
                    $graph = '<div id="direct-graph"><canvas id="canvas">' . $graph . '</canvas></div>';
                }
            }
            $Y        = date('Y');
            $title    = $direction['name'] . ' ' . $rp . ' – список ' . $Y;
            $keywords = $direction['name'] . ' университеты институты ' . $rp . ' факультеты специальности проходной балл бюджетные места стоимость обучения';
            $desc     = 'Все ' . $direction['name'] . ' ' . $rp . ' (университеты и институты), поиск государственных вузов с бюджетными местами ' . $Y . ' и проходному баллу ' . ($Y - 1);
            $h1       = $direction['name'] . ' ' . $rp;

            $seoText = '<section id="bottom-text">Список содержит все вузы ' . $rp . ' (университеты и институты), в которых можно получить высшее образование соответствующего профиля подготовки. Если Вам необходимо узнать количество бюджетных мест в ' . $Y . ' году, проходных баллах ЕГЭ в ' . $direction['name'] . ' ' . $rp . ', необходимо выбрать одно из направлений подготовки выше.</section>';
        } elseif ($freeCh || $milCh || $hosCh) {
            $findAttrs .= ' с ';
            if ($freeCh) {
                $findAttrs .= 'бюджетными местами, ';
            }
            if ($hosCh) {
                $findAttrs .= 'общежитием, ';
            }
            if ($milCh) {
                $findAttrs .= 'военной кафедрой, ';
            }
            $findAttrs = mb_substr($findAttrs, 0, -2, "UTF-8");
        }

        if ($gosCh) {
            $tGos  = 'государственные ';
            $tGos1 = 'государственных ';
        } else {
            $tGos = $tGos1 = '';
        }

        if (!$directId && !$spec) {
            $h1 = $title = $desc = $keywords = '';
            if ($level === 's') {
                $h1       .= 'второе высшее образование в ';
                $title    .= 'второе высшее образование – ';
                $keywords .= 'второе высшее образование ';
                $desc     .= 'второе высшее образование в ';
            } elseif ($level === 'm') {
                $h1       .= 'магистратура в ';
                $title    .= 'магистратура – ';
                $keywords .= 'магистратура ';
                $desc     .= 'магистратура в ';
            } elseif ($level === 'p') {
                $h1       .= 'аспирантура в ';
                $title    .= 'аспирантура – ';
                $keywords .= 'аспирантура ';
                $desc     .= 'аспирантура в ';
            }


            $gos      = 'государственные ';
            $gos1     = 'государственных ';
            $desc     .= 'Специальности, конкурс, стоимость обучения и проходные баллы в ';
            $keywords .= 'Специальность конкурс стоимость обучение проходной балл ' . ($gosCh ? $gos : null) . 'вуз университет институт ';
        }

        /* Checkbox unchecked */
        if (!$directId && !$spec && !($educCh || $gosCh || $freeCh || $milCh || $hosCh)) {
            if ($level === 'f') {
                $h1    .= 'вузы ';
                $title .= 'вузы ';
                $desc  .= 'вузы ';
            } else {
                $h1    .= 'вузах ';
                $title .= 'вузах ';
                $desc  .= 'вузах ';
            }

            $h1       .= $rp;
            $title    .= "$rp (университеты и институты) – список $Y";
            $desc     .= "$rp (университеты и институты) $Y";
            $keywords .= $rp;
        }

        $arrFormNames = [
            1 => 'очно',
            2 => 'очно-заочно',
            3 => 'заочно',
            4 => 'дистанционно',
        ];
        $formName     = $arrFormNames[$form];

        if (!$directId && !$spec && ($educCh || $gosCh || $freeCh || $milCh || $hosCh)) {
            if ($gosCh) {
                $title .= $gos;
                if ($level === 'f') {
                    $h1   .= $gos;
                    $desc .= $gos;
                } else {
                    $h1   .= $gos1;
                    $desc .= $gos1;
                }
            }

            if ($level === 'f') {
                $h1    .= 'вузы ';
                $title .= 'вузы ';
                $desc  .= 'вузы ';
            } else {
                $h1    .= 'вузах ';
                $title .= 'вузах ';
                $desc  .= 'вузах ';
            }

            $h1       .= $rp;
            $title    .= "$rp (университеты и институты)";
            $keywords .= $rp;

            $params = trim($findAttrs);

            $title    .= " $formName $params – список $Y";
            $h1       .= " $formName $params";
            $desc     .= "$rp (университеты и институты) $Y $formName $params";
            $keywords .= " $formName $params";
        }

        if (!$directId && !$spec) {
            $h1    = firstCharUp($h1);
            $title = firstCharUp($title);
            $desc  = firstCharUp($desc);
        }

        #SEARCH RESULTS
        $finded = '';
        if ($spec) {

            if ($dbg_log) {
                self::dbg_log($db,'catalog_build', 'search results');
            }

            $vuzov = rodpad($total, ['вузов', 'вуза', 'вуз', 'вузов']);
            $db->query(
                'SELECT MOD(FLOOR(`okso`.`code`/100), 10) AS lvl, `code` FROM `vuz`.`okso` WHERE `okso`.`id`=?',
                $spec
            );
            $row = $db->get_row();

            switch ($level) {
                case 'f':
                    $specLvl = (($row['lvl'] === '3') ? ('бакалавриат') : ('специалитет'));;
                    $specCode = substr_replace(substr_replace($okso['code'], '.', 2, 0), '.', 5, 0);
                    $title    = 'Специальность «' . $okso['name'] . '» ' . $formName . ' – ' . $tGos . ' вузы ' . $rp . $findAttrs;
                    $keywords = $okso['name'] . ' ' . $specCode . ' ' . $tGos . 'вузы ' . $rp . ' университеты институты ' . $findAttrs;
                    $desc     = 'Все ' . $tGos . 'вузы ' . $rp . ' со специальностью ' . $okso['name'] . ' (' . $specCode . ') ' . $findAttrs . ($findAttrs ? ' ' . $formName : $formName);
                    $h1       = $tGos . 'вузы ' . $rp . ' ' . $findAttrs . ' со специальностью «' . $okso['name'] . '» ' . $formName;
                    $h1       = firstCharUp($h1);
                    if ($total) {
                        $finded = '<p id="finded">' . firstCharUp(
                                $specLvl
                            ) . ' «' . $okso['name'] . '», найдено ' . $total . ' ' . $vuzov . ' ' . $rp . '</p>';
                    }
                    break;
                case 's':
                    $specCode = substr_replace(substr_replace($okso['code'], '.', 2, 0), '.', 5, 0);
                    $title    = 'Второе высшее образование «' . $okso['name'] . '» – ' . $tGos . ' вузы ' . $rp . $findAttrs;
                    $keywords = $okso['name'] . ' второе высшее образование ' . $tGos . 'вузы ' . $rp . ' университеты институты ' . $findAttrs;
                    $desc     = 'Второе высшее образование по специальности ' . $okso['name'] . ' (' . $specCode . ') в ' . $tGos1 . ' вузах ' . $rp . ' ' . $findAttrs;
                    $h1       = 'Второе высшее образование «' . $okso['name'] . '» в ' . $tGos1 . 'вузах ' . $rp . ' ' . $findAttrs;
                    if ($total) {
                        $finded = '<p id="finded">Второе высшее «' . $okso['name'] . '», найдено ' . $total . ' ' . $vuzov . ' ' . $rp . '</p>';
                    }
                    break;
                case 'm':
                    $title    = 'Магистратура «' . $okso['name'] . '» – ' . $tGos . 'вузы ' . $rp . $findAttrs;
                    $keywords = $okso['name'] . ' магистратура ' . $tGos . 'вузы ' . $rp . ' университеты институты ' . $findAttrs;
                    $desc     = 'Магистратура по направлению (программа) ' . $okso['name'] . ' (' . $row['code'] . ') в ' . $tGos1 . ' вузах ' . $rp . ' ' . $findAttrs;
                    $h1       = 'Магистратура «' . $okso['name'] . '» в ' . $tGos1 . 'вузах ' . $rp . ' ' . $findAttrs;
                    if ($total) {
                        $finded = '<p id="finded">Магистратура «' . $okso['name'] . '», найдено ' . $total . ' ' . $vuzov . ' ' . $rp . '</p>';
                    }
                    break;
                case 'p':
                    $title    = 'Аспирантура «' . $okso['name'] . '» – ' . $tGos . 'вузы ' . $rp . $findAttrs;
                    $keywords = $okso['name'] . ' аспирантура ' . $tGos . 'вузы ' . $rp . ' университеты институты ' . $findAttrs;
                    $desc     = 'Аспирантура по направлению (программа) ' . $okso['name'] . ' (' . $row['code'] . ') в ' . $tGos1 . ' вузах ' . $rp . ' ' . $findAttrs;
                    $h1       = 'Аспирантура «' . $okso['name'] . '» в ' . $tGos1 . 'вузах ' . $rp . ' ' . $findAttrs;
                    if ($total) {
                        $finded = '<p id="finded">Аспирантура «' . $okso['name'] . '», найдено ' . $total . ' ' . $vuzov . ' ' . $rp . '</p>';
                    }
                    break;
            }
            $seoText = '';
            if (!$robots) { // result not empty
                $vuzes .= '<p id="spec-link">Подробнее о специальности <a href="/specs/' . $spec . '">' . $okso['name'] . '</a></p>';
            }
        }

        /* DEFAULT SEO */
        # TITLE
        if (!$title) {
            switch ($level) {
                case 'f':
                    $title = 'Вузы ' . $rp . ' (университеты и институты) – список ' . date("Y");
                    break;
                case 's':
                    $title = 'Второе высшее образование – вузы ' . $rp;
                    break;
                case 'm':
                    $title = 'Магистратура – вузы ' . $rp;
                    break;
                case 'p':
                    $title = 'Аспирантура – вузы ' . $rp;
                    break;
            }
        }
        # H1 TEXT
        if (!$h1) {
            switch ($level) {
                case 'f':
                    $h1 = 'Вузы ' . $rp;
                    break;
                case 's':
                    $h1 = 'Второе высшее в вузах ' . $rp;
                    break;
                case 'm':
                    $h1 = 'Магистратура в вузах ' . $rp;
                    break;
                case 'p':
                    $h1 = 'Аспирантура в вузах ' . $rp;
                    break;
            }
        }

        # Keywords
        if (!$keywords) {
            switch ($level) {
                case 'f':
                    $keywords = 'вузы ' . $rp . ' университеты институты высшее образование учебные заведения список специальности отзывы ' . date(
                            "Y"
                        );
                    break;
                case 's':
                    $keywords = 'второе высшее образование вузы ' . $rp . ' университеты институты список специальности отзывы ' . date(
                            "Y"
                        );
                    break;
                case 'm':
                    $keywords = 'магистратура вузы ' . $rp . ' университеты институты список специальности отзывы ' . date(
                            "Y"
                        );
                    break;
                case 'p':
                    $keywords = 'аспирантура вузы ' . $rp . ' университеты институты список специальности отзывы ' . date(
                            "Y"
                        );
                    break;
            }
        }

        # DESC
        if (!$desc) {
            switch ($level) {
                case 'f':
                    $desc = 'Все вузы ' . $rp . ' (университеты и институты) с результатами мониторинга Минобрнауки, факультеты и специальности, проходные баллы ЕГЭ, дни открытых дверей, отзывы студентов';
                    break;
                case 's':
                    $desc = 'Второе высшее образование в вузах ' . $rp . ': факультеты, специальности и стоимость обучения';
                    break;
                case 'm':
                    $desc = 'Магистратура в вузах ' . $rp . ': направления обучения и программы подготовки, стоимость обучения';
                    break;
                case 'p':
                    $desc = 'Аспирантура в вузах ' . $rp . ': направления обучения и программы подготовки, стоимость обучения';
                    break;
            }
        }

        #SEO_TEXT
        if (!$seoText && !$_GET['page']) {
            $seoText = '
				<section id="bottom-text">
					<h2>';

            if (!$findAttrs && !$spec && !$gosCh) { // Only main location page
                $seoText .= 'Высшее образование в вузах ' . $rp;
            } else {
                $seoText .= 'Высшее образование ' . (($spec) ? ('по специальности «' . $okso['name'] . '» ') : (' ')) . $formName . ' в ' . $tGos1 . ' вузах ' . $rp . ' ' . $findAttrs;
            }

            $strVuz = self::numberof($total, 'вуз', ['ом', 'ами', 'ами']);
            $total  = $total ?: null;

            $seoText .= "</h2>
				<p>Специальности, профили обучения, конкурс и проходные баллы в $tGos вузы $rp (университеты и институты)"
                . (empty($formName) ? "" : " $formName") .
                "$findAttrs, которые представлены $total $strVuz, имеющим"
                . (substr($numberVuzes, -1) === '1' ? '' : 'и') .
                " действующую лицензию на образовательную деятельность в $Y году.</p>";

            if (!$findAttrs && !$spec && !$gosCh && !$_GET['form']) { // Only main location page
                $seoText .= "		
					<p>На нашем сайте вы можете проверить институты $rp по множеству параметров
					 от наличия действующей лицензии и аккредитации до запрета приема, 
					 а также узнать их рейтингу по версии EduNetwork (ESI).</p>
					 <p>На нашем сайте вы найдете университеты $rp с официальными данными 
					 о приемной комиссии, контакты, график работы, отзывы студентов, расписание дней открытых дверей, 
					 фото и другую полезную для абитуриентов информацию. Контрольные цифры приема в вузы $rp $Y года, 
					 все специальности и реализуемые профили подготовки, вступительные экзамены ЕГЭ, стоимость обучения,
					 обновляются ежегодно модераторами EduNetwork.</p>
					 <p>Мы стремимся сделать все, чтобы предоставить абитуриентам самый полный и 
					 актуальный список высших учебных заведений $rp, который позволит выбрать вуз вашей мечты.</p>
				";
            }
            $seoText .= '</section>';
        }

        #NAV
        if ($subjectId !== 99 && ($cityId || $spec || $directId || $level != 'f' || $freeCh || $milCh || $hosCh || $gosCh)) {
            $nav = ' <a href="/' . $subjectId . '/">Вузы ' . $subject['rp'] . '</a>';
            if ($cityId && ($spec || $directId || $level != 'f' || $freeCh || $milCh || $hosCh || $gosCh)) {
                $nav .= ' <a href="/' . $subjectId . '/' . $cityId . '/">Вузы ' . $city['rp'] . '</a>';
                if ($spec) {
                    if ($level === 's') {
                        $nav .= ' <a href="' . $basePath . 'sec/">Второе высшее</a>';
                    } elseif ($level === 'm') {
                        $nav .= ' <a href="' . $basePath . 'mag/">Магистратура</a>';
                    }
                }
            }
        }

        //echo 'page => '. $get_params['page'].'<br>';
        $helper = ($get_params['page'] ? '' : file_get_contents($home . 'tpl/helper.html'));
        if ($subjectId === 77) {
            $t = 26;
        } elseif ($subjectId === 78) {
            $t = 44;
        } else {
            $t = 'dist';
        }

        if (!$get_params['page'] && !$robots && !$directId && !$spec) {
            if ($subjectId === 77 || $subjectId === 78 || $cityId) {
                if ($cityId) {
                    $t = $cityId;
                }
                $db->query(
                    'SELECT `dirs`, `free`, `gos`, `gos_studs`, `forms`, `fils`, `mag` FROM `vuz`.`e_plus_geo` WHERE `city_id`=?',
                    $t
                );
                if ($db->num_rows()) {
                    $h2  = "Статистика университетов $rp - бюджетные места, направления и формы обучения";
                    $row = $db->get_row();

                    $graph       = '
						<section id="enw-plus">
							<div class="row">
								<div class="col s12 m6 l4">
									<h4>' . (($t === 26 || $t === 44) ? ('Направления обучения') : ('Численность студентов')) . '</h4>
									<div id="chart-direct">';
                    $row['dirs'] = explode("\n", $row['dirs']);
                    $cnt         = sizeof($row['dirs']);
                    for ($i = 0; $i < $cnt; $i++) {
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
						<p class="Ep-more"> <a href="#!"><i class="material-icons">expand_more</i><span>Показать еще данные</span></a></p>
					</section>';
                }
            }
        }

        if ($dbg_log) {
            self::dbg_log($db,'catalog_build', 'stats rendered');
        }


        if ($level !== 'f' && !$directId) {
            $seoText = '';
        }

        if (
            $vuzesCount < 4 &&
            (
                isset($_GET['gos'])
                || isset($_GET['mil'])
                || isset($_GET['hos'])
                || isset($_GET['free'])
            )
        ) {
            $robots = '<meta name="robots" content="noindex, nofollow" />';
        }

        // if($subjectId === 77){
        //     $adsBanner = '
        //         <section class="banner_section">
        //             <a href="https://synergy.ru/lp/it-college/?utm_source=edunetwork_lp&utm_medium=edunetwork_lp&utm_campaign=edunetwork&utm_term=it_college&marketer=okd&produkt=109903289&utm_gen=3">
        //                 <img src="/tpl/imgs/banner.png" alt="Баннер на день открытых дверей">
        //             </a>
        //         </section>
        //     ';
        // }

        $articles = '';

        $tpl->start($home . 'tpl/catalog.html');

        //form=1&gos=y&free=y
        //?form=1&gos=y&free=y&mil=y&hos=y
        //"form":"2","gos":"y","free":"y","mil":"y","hos":"y"
        $canonicalSuf = '';
        $amp          = '?';
        if ($get_params['form']) {
            $canonicalSuf = $amp . 'form=' . $get_params['form'];
            $amp          = '&';
        }
        if ($get_params['gos']) {
            $canonicalSuf .= $amp . 'gos=' . $get_params['gos'];
            $amp          = '&';
        }
        if ($get_params['free']) {
            $canonicalSuf .= $amp . 'free=' . $get_params['free'];
            $amp          = '&';
        }
        if ($get_params['mil']) {
            $canonicalSuf .= $amp . 'mil=' . $get_params['mil'];
            $amp          = '&';
        }
        if ($get_params['hos']) {
            $canonicalSuf .= $amp . 'hos=' . $get_params['hos'];
        }
        $canonicalDir = '';
        if ($get_params['direct']) {
            $canonicalDir .= 'd' . $get_params['direct'] . '/';
        }

        $canonicalPrf = '';
        switch ($get_params['lvl']) {
            case 'f' :
                break;
            case 's' :
                $canonicalPrf = 'sec/';
                break;
            case 'm' :
                $canonicalPrf = 'mag/';
                break;
            case 'p' :
                $canonicalPrf = 'phd/';
                break;
        }
        if ($dbg_log) {
            self::dbg_log($db,'catalog_build', 'template started');
        }

        //.json_encode($get_params)
        $canonicalUrl = '//vuz' . DOMAIN . $basePath . $canonicalDir . $canonicalPrf . $canonicalSuf;
        $tpl->replace([
            '[head]'            => get_head(
                $title,
                $desc,
                $keywords,
                $robots . '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>',
                $canonicalUrl
            ),
            '[second-gtm-code]' => getSecondGtmCode(),
            "[roof]"            => get_roof($subjCity),
            "[nav]"             => $nav,
            "[h1]"              => $h1,
            "[h2]"              => $h2,
            "[navR]"            => $navRight,
            "[vuzes]"           => $vuzes,
            "[articles]"        => $articles,
            "[seo]"             => $seoText,
            "[dods]"            => $dods,
            "[vuzTypes]"        => $vuzTypes,
            "[vuzAddTypes]"     => $vuzAddTypes,
            "[egeCity]"         => $egeCity,
            '[filter]'          => $filter,
            '[finded]'          => $finded,
            '[paging]'          => $paging,
            '[dir_specs]'       => $dir_specs,
            '[graph]'           => $graph,
            '[helper]'          => $helper,
            '[ads1]'            => $ads[1],
            '[ads2]'            => $ads[2],
            '[ads3]'            => $ads[3],
            '[ads4]'            => $ads[4],
            '[ads5]'            => $ads[5],
            '[ads6]'            => $ads[6],
            '[ads7]'            => $ads[7],
            // "[ads8]"            => $adsBanner,
            '[quiz]'            => file_get_contents($home . 'tpl/quiz.html'),
            '[footer]'          => file_get_contents($home . 'tpl/footer.html'),
        ]);
        header("Last-Modified: " . $lmDate . " GMT");

        $tpl->out();

        if ($dbg_log) {
            self::dbg_log($db,'catalog_build', 'template rendered');
        }

        /*
        if ($directId > 0 ) {
            echo
                '<p>
                    nailed it:
                    '.json_encode($sqls, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'
                </p>
                <p>
                    '.$sql_wt.'
                </p>
                ';
        }
        */
    }

}
