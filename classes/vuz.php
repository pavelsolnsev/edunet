<?php

class vuz
{

    const dbg = true;

    static function leads_form($vuz_id = 0)
    {
        $ret = '
            <!--noindex-->
                <div id="ylead-form">
                    <div id="ylead-form-holder">
                        <h3>Подобрать программу обучения</h3>
                        <form autocomplete="off" class="row" name="lead-form">
                            <p class="col s6 m3">
                                <input type="text" id="ylead-name" placeholder="Имя" required pattern="[A-Za-zа-яА-ЯёЁ\s\-]{1,32}" class="validate">
                            </p>
                            <p class="col s6 m3">
                                <input type="text" id="ylead-phone" placeholder="Телефон" required pattern="[\+\(\)\-\d\s]{8,12}" class="validate">
                            </p>
                            <p class="col s6 m3">
                                <input type="email" id="ylead-email" placeholder="Email" required class="validate">
                            </p>
                            <input type="text" hidden id="ylead-land" value="edu_vuz" required>
                            <input type="text" hidden id="ylead-vuz-id" value="' . $vuz_id . '" required>
                            <p class="col s6 m3">
                                <button type="submit" class="btn">Отправить</button>
                            </p>
                            <p class="col s12">
                                <span id="conf-text">Нажимая на кнопку, я соглашаюсь с политикой конфиденциальности и на получение рассылок</span>
                            </p>
                        </form>
                    </div>
                </div>
            <!--/noindex-->';
        return ($ret);
    }

    static function get_head(&$vuz, $page = false, $fyi = false)
    {
        if (!vuz::dbg) {
            if ($vuz['subj_id'] != $_GET['subj'] || ($_GET['city'] && ($vuz['city_id'] != $_GET['city']))) {
                myErr::err404();
            }
            if (!$_GET['city'] && $_GET['subj'] != 77 && $_GET['subj'] != 78) {
                myErr::err404();
            }
            if (($_GET['subj'] == 77 || $_GET['subj'] == 78) && (strpos(
                        $_SERVER['REQUEST_URI'],
                        '/0/'
                    ) || $_GET['city'] != 0)) {
                myErr::err404();
            }
        }

        $vuz['subj_id'] = (int)$vuz['subj_id'];
        $vuz['id']      = (int)$vuz['id'];
        $ret            = [];
        /* Navbar */
        $path   = '/' . $vuz['subj_id'] . '/';
        $navbar = '<a href="' . $path . '">Вузы ' . $vuz['subj_rp'] . '</a>';
        if ($vuz['subj_id'] !== 77 && $vuz['subj_id'] !== 78) {
            $path   .= $vuz['city_id'] . '/';
            $navbar .= ' <a href="' . $path . '">Вузы ' . $vuz['city_rp'] . '</a>';
        }
        if ($page) {
            $navbar .= ' <a href="' . $path . 'v' . $vuz['id'] . '/">' . $vuz['abrev'] . '</a>';
        }
        if (isset($vuz['faculties_bread']) && $vuz['faculties_bread']) {
            $navbar .= ' <a href="' . $path . 'v' . $vuz['id'] . '/faculties/">Факультеты</a>';
        }


        $ret['nav'] = $navbar;

        $event = $lnkColor = '';
        if ($vuz['partner'] && $vuz['textColor']) {
            $ret['lead'] = vuz::leads_form($vuz['id']);
            $lnkColor    = ' style="color:#' . $vuz['textColor'] . '"';
            $bg          = ' class="ver2" style="background-image: url(\'/files/' . $vuz['id'] . '/head/bg.jpg\'); color: #' . $vuz['textColor'] . '"';
            $logo        = '';

            if ($vuz['event']) {
                $vuz['event'] = explode("|", $vuz['event']);
                $event        = '<div class="unit-event"><div style="background:#' . $vuz['event'][2] . '; color:#' . $vuz['event'][1] . '">' . $vuz['event'][0] . '</div></div>';
            }
        } else {
            $bg = $vuz['promo'] = $ret['lead'] = '';
        }

        if (!$event) {
            /* Vuz logo */
            if ($vuz['logo']) {
                $logo = '<img alt="Логотип ' . $vuz['abrev'] . '" itemprop="logo" src="/files/' . $vuz['id'] . '/logo.' . $vuz['logo'] . '" />';
            } else {
                $logo = '<img alt="Нет логотипа" src="//static.edunetwork.ru/imgs/tpl/noLogo.png" />';
            }
        }

        /* Status bar */
        $warnings = (($vuz['delReason']) ? ('<div id="unit-closed" class="valign-wrapper"><p>' . $vuz['delReason'] . '</p></div>') : (''));
        if ($vuz['noAbitur']) {
            $warnings .= '
                        <div class="noabitur">
                                <p>В вуз запрещен набор абитуриентов</p>
                                <p>Обновлено ' . $vuz['noAbitur'] . '</p>
                        </div>';
        } elseif (!$vuz['delReason']) {
            $status = '';
            if (!$vuz['textColor']) {
                $status = '<ul>';
                if ($vuz['packet'] === 'sert') {
                    $status .= '<li class="sert"><span>Подтвержденный</span></li>';
                } elseif ($vuz['u_id']) {
                    $status .= '<li class="has-user"><span>Есть представитель</span></li>';
                }

                if ($vuz['metro']) {
                    $status .= '<li class="metro truncate' . (($vuz['subj_id'] === 77) ? ('') : (' spb')) . '">' . $vuz['metro'] . '</li>';
                }

                if ($vuz['esi'] !== null && $vuz['esi'] < 4) {
                    $status .= '</ul>
                <div class="low_esi">
                        <p>
                                Вуз демонстрирует низкие показатели<br />
                                нажмите на <a href="/checkVuz#' . $vuz['id'] . '" class="check-unit">Проверить вуз</a> чтобы узнать подробности
                        </p>
                                    </div>';
                } else {
                    $status .= '<li class="check-unit"><a href="/checkVuz#' . $vuz['id'] . '">Проверить вуз</a></li>
                            </ul>';
                }
            }

            $status .= '<ul id="unit-attrs">';
            if ($vuz['gos']) {
                $status .= '<li><a href="' . $path . '?gos=y"' . $lnkColor . '>Государственный</a></li>
                                                    <li><a href="' . $path . '?free=y"' . $lnkColor . '>Бюджетные&nbsp;места</a></li>';
            } else {
                $status .= '<li>Негосударственный</li>';
                if ($vuz['id'] === 517 || $vuz['id'] === 189 || $vuz['id'] === 399) { /* РосНОУ */
                    $status .= '<li><a href="' . $path . '?free=y"' . $lnkColor . '>Бюджетные&nbsp;места</a></li>';
                }
            }
            if ($vuz['hostel']) {
                $status .= '<li><a href="' . $path . '?hos=y"' . $lnkColor . '>Общежитие</a></li>';
            }
            if ($vuz['military']) {
                $status .= '<li><a href="' . $path . '?mil=y"' . $lnkColor . '>Военная&nbsp;кафедра</a></li>';
            }
            if ($vuz['dir_id']) {
                $status .= '<li><a href="' . $path . 'd' . $vuz['dir_id'] . '/"' . $lnkColor . '>' . $vuz['dir_name'] . '</a></li>';
            }
            $status .= '</ul>
                    <span itemscope itemprop="identifier" itemtype="https://schema.org/PropertyValue">
                            <meta itemprop="propertyID" content="Company Number" />
                            <meta itemprop="value" content="' . $vuz['id'] . '" />
                    </span>';
        }

        $status .= $warnings;

        if ($fyi) {
            //$h1_text = 'Всякие Специальности в '.$vuz['abrev'];
            $h1_text = $vuz['h1_text'];
            //$h1_text = 'Специальность '.$spec['name'].' в '.$vuz['abrev'].'';
        } else {
            $h1_text = $vuz['name'];
        }
        //<h1 itemprop="name">Специальности в '.$vuz['abrev'].'</h1>

        $ret['header'] = '
                    <div id="unit-header"' . $bg . '>
                            <div class="container">
                                    <header class="card horizontal">
                                            <div class="card-stacked">
                                                    <div class="card-content">
                                                            <h1 itemprop="name">' . $h1_text . '</h1>
                                                            ' . $status . '
                                                    </div>
                                            </div>
                                            <div class="card-image hide-on-small-only" >' . $logo . '</div>
                                            ' . $event . '
                                    </header>
                            </div>
                        </div>
                    </div>
        ';

        return ($ret);
    }

    static function get_nav(&$vuz, $page)
    {
        global $db;
        $basePath = preg_replace('/^([\d\/]+v\d+\/).*/', '$1', $_SERVER['REQUEST_URI']);
        $nav      = ($page === 'main' ? '<li class="sel" itemprop="alternateName">' . $vuz['abrev'] . '</li>' : '<li><a href="' . $basePath . '">' . $vuz['abrev'] . '</a></li>');

        if ($page === "faculties") {
            $nav .= '<li class="sel">Факультеты</a>';
        } elseif ($vuz['faculties']) {
            $nav .= '<li><a href="' . $basePath . 'faculties/">Факультеты</a></li>';
        } else {
            if ($vuz['spec']) {
                $nav .= '<li><a href="' . $basePath . 'faculties/" rel="nofollow">Факультеты</a></li>';
            }
        }

        if ($page === 'specs') {
            $nav .= '<li class="sel">Специальности</li>';
        } else {
            //$nav.='<li><a href="'.$basePath.'specs/">Специальности</a></li>';

            if ($vuz['spec']) {
                $nav .= '<li><a href="' . $basePath . 'specs/">Специальности</a></li>';
            } else {
                $nav .= '<li><a id="spec_scroll" href="' . $basePath . '#lic_okso">Специальности</a></li>';
            }
        }

        if ($page === "opinions") {
            $nav .= '<li class="sel">Отзывы</a>';
        } else {
            if ($page === "main") {
                $vuz['opins'] = preg_replace('/^[\d\.]+\|/', '', $vuz['opins']);
            }
            if ($vuz['opins']) {
                $nav .= '<li><a href="' . $basePath . 'opinions/">Отзывы (' . $vuz['opins'] . ')</a></li>';
            } elseif (!$vuz['delReason']) {
                $nav .= '<li><a href="' . $basePath . 'opinions/" rel="nofollow">Отзывы</a></li>';
            }
        }
        return ($nav);
    }

    static function show($v_id = 0)
    {
        global $db, $tpl, $host;
        $id = &$_GET['vuz'];

        if ($v_id > 0) {
            $id = $v_id;
        } else {
            if (!preg_match('/^\d+$/', $id)) {
                myErr::err404();
            }
        }
        $id = (int)$id;
        $db->query(
            '
            SELECT 
                a.*, 
                `subjects`.`name` as subject, `subjects`.`rp` as subj_rp,
                `cities`.`name` as city, `cities`.`rp` as city_rp, `cities`.`type`, 
                `metros`.`name` AS metro,
                `e_plus`.`dirs`, `e_plus`.`forms`, `e_plus`.`mag`, 
                IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
                GROUP_CONCAT(DISTINCT `old_names`.`name` SEPARATOR "|") AS old_names,
                `dir2specs`.`id` AS dir_id, `dir2specs`.`name` AS dir_name,
                (SELECT 1 FROM `vuz`.`monit` WHERE `vuz_id`=a.`id` LIMIT 1) AS monit,
                (SELECT 1 FROM `vuz`.`openDays` WHERE `vuz_id`=a.`id` AND `openDays`.`start`>NOW() LIMIT 1) AS dods,
                (SELECT 1 FROM `vuz`.`gallery` WHERE `vuz_id`=a.`id` LIMIT 1) AS gallery,
                (SELECT 1 FROM `vuz`.`vuz2article` WHERE `vuz2article`.`vuz_id`=a.`id` LIMIT 1) AS articles,
                (SELECT 1 FROM `vuz`.`specs` WHERE `specs`.`vuz_id`=a.`id` LIMIT 1) AS spec,
                (SELECT CONCAT(AVG(`opinions`.`score`), "|", COUNT(*)) FROM `vuz`.`opinions` WHERE `opinions`.`vuz_id`=a.`id` AND `approved`="1") AS opins
            FROM 
                (
                    SELECT
                        `vuzes`.`id`, `vuzes`.`editTime`,
                        `vuzes`.`fullName`, `vuzes`.`name`, `vuzes`.`abrev`, `vuzes`.`logo`,
                        `vuzes`.`gos`, `vuzes`.`hostel`, `vuzes`.`military`,
                        `vuzes`.`subj_id`, `vuzes`.`city_id`, `vuzes`.`metro_id`,
                        `vuzes`.`post`, `vuzes`.`address`, `vuzes`.`email`, `vuzes`.`site`, 
                        `vuzes`.`phone`, `vuzes`.`telNew`,
                        `vuzes`.`lic_num`, `vuzes`.`lic_start`, 
                        `vuzes`.`acr_num`, `vuzes`.`acr_start`,	
                        `vuzes`.`priem_address`, `vuzes`.`priem_index`, `vuzes`.`priem_site`, 
                        `vuzes`.`priem_phone`, `vuzes`.`priem_email`,
                        `vuzes`.`about`, `vuzes`.`parent_id`, 
                        `vuzes`.`schedule`, `vuzes`.`priem_start`, `vuzes`.`priem_end`,
                        `vuzes`.`delReason`, `vuzes`.`noAbitur`, `vuzes`.`esi`,
                        IF(`vuzes`.`packetEnd`>DATE(NOW()), "sert", "") AS packet, 
                        IF(`vuzes`.`partner`, (SELECT CONCAT(`text`, "|", `textColor`, "|", `bgColor`) FROM `vuz`.`vuz_events` WHERE `vuz_id`=`vuzes`.`id` AND `start`<=NOW() AND `end`>NOW() LIMIT 1), "") AS event, 
                        `vuzes`.`partner`, `vuzes`.`textColor`, `vuzes`.`promo`
                    FROM `vuz`.`vuzes` WHERE `vuzes`.`id`=?
                    ) a 
                    LEFT JOIN
                        `general`.`subjects` ON a.`subj_id`=`subjects`.`id` 
                    LEFT JOIN
                        `general`.`cities` ON a.`city_id`=`cities`.`id` 
                    LEFT JOIN
                        `general`.`metros` ON a.`metro_id`=`metros`.`id` 
                    LEFT JOIN
                        `vuz`.`user2vuz` ON a.`id`=`user2vuz`.`vuz_id` 
                    LEFT JOIN
                        `vuz`.`e_plus` ON a.`id`=`e_plus`.`vuz_id` 
                    LEFT JOIN
                        `vuz`.`vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id` 
                    LEFT JOIN
                        `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id` 
                    LEFT JOIN
                        `vuz`.`old_names` ON a.`id`=`old_names`.`vuz_id`
            GROUP BY a.`id`',
            $id
        );

        if (!$db->num_rows()) {
            myErr::err404();
        }

        $vuz  = $db->get_row();
        $head = vuz::get_head($vuz);

        if ($vuz['editTime']) {
            $lmDate = date('D, d M Y H:i:s', strtotime($vuz['editTime']));
            header("Last-Modified: " . $lmDate . " GMT");
        }

        $canonicalUrl = '';
        /* DODS */
        $dods = '';
        if ($vuz['dods']) {
            $db->query(
                '
                                SELECT a.`name`, a.`address`, a.`date`, a.`time`, a.`online`, a.`url`, `subvuz`.`name` as subvuz 
                                FROM 
                                        (
                                                SELECT 
                                                        `openDays`.`name`, `openDays`.`address`, `openDays`.`subvuz_id`, `openDays`.`start`,
                                                        `openDays`.`online`, `openDays`.`url`,
                                                        DATE(`openDays`.`start`) as date, SUBSTR(`openDays`.`start`,12,5) as time
                                                FROM `openDays` WHERE `openDays`.`vuz_id`=? AND `openDays`.`start`>NOW()
                                        ) a LEFT JOIN 
                                        `vuz`.`subvuz` ON a.`subvuz_id`=`subvuz`.`id`
                                ORDER BY a.`start` LIMIT 5',
                $id
            );
            if ($db->num_rows()) {
                require_once(base_path . "classes/date.php");

                $dods = $date = '';
                $c    = 0;
                while ($day = $db->get_row()) {
                    if ($day['date'] !== $date) {
                        $dods .= '<p class="date">' . date::mysql2Rus($day['date']) . '</p>';
                        $date = $day['date'];
                        $c++;
                    }

                    $dods .= '
                                    <div class="dod myIcon leftIcon" itemscope itemtype="https://schema.org/Event">
                                            <div class="inner">
                                                    <meta itemprop="startDate" content="' . $day['date'] . '" />
                                                    <meta itemprop="endDate" content="' . $day['date'] . '" />
                                                    <p class="title" itemprop="name">' . (($day['name']) ? ($day['name']) : ('День открытых дверей')) . '</p>';
                    if ($day['subvuz']) {
                        $dods .= '<p class="myIcon subunit">' . $day['subvuz'] . '</p>';
                    }

                    $dods .= '<p class="place myIcon">' . $day['time'];
                    if ($day['online']) {
                        $dods .= ' <span class="myIcon online">Мероприятие онлайн</a>';
                    } else {
                        $dods .= '
                                                    <span itemprop="location" itemscope itemtype="https://schema.org/Place">
                                                            <span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                                                                    <span itemprop="streetAddress" class="myIcon dod-addr">' . $day['address'] . '</span>
                                                                    <meta itemprop="addressLocality" content="' . $vuz['city'] . '" />
                                                            </span>
                                                    </span>';
                    }
                    $dods .= '</p>';
                    if ($day['url']) {
                        $dods .= '<p class="myIcon leftIcon dod-url"><a itemprop="url" href="' . $day['url'] . '">Ссылка на мероприятие</a></p>';
                    }
                    $dods .= '
                                            </div>
                                    </div>';
                }
                unset($day);
                if ($c > 1) {
                    $dods .= '<p id="more-dods"><a href="#" class="more-link">Показать еще дни открытых дверей</a></p>';
                }


                /*
                                    <noindex>
                                    <div style="margin-bottom:2rem; background: #fee; padding: 1rem; overflow: hidden; line-height: 1.5">
                                            <i class="material-icons" style="color: #c62828; font-size: 3rem;float:left; margin-right: .5rem">report</i>Внимание! В связи с эпидемией, Дни открытых дверей могут быть отменены. Пожалуйста, перед посещением, уточняйте информацию в вузе.
                                    </div></noindex>
                */
                $dods = '
                            <section id="dods">
                                    <a name="openDays"></a>
                                    <h2>Дни открытых дверей ' . $vuz['abrev'] . '</h2>
                                    ' . $dods . '
                            </section>';
            }
        }

        /* E+ */
        $Ep = '';
        if ($vuz['dirs']) {
            $Ep = '<section id="enw-plus">
                            <h2>EduNetwork&plus;</h2> <a href="data/" class="more-link">' . $vuz['abrev'] . ' в цифрах</a>
                            <div class="row">
                                    <div class="col s12 m6 l4">
                                            <h4>Направления обучения</h4>
                                            <div id="chart-direct">';
            if ($vuz['dirs']) {
                $t   = explode("\n", $vuz['dirs']);
                $cnt = sizeof($t);
                for ($i = 0; $i < $cnt; $i++) {
                    $t1 = explode('|', $t[$i]);
                    $Ep .= '<p data-val="' . $t1[1] . '" style="width:1%">' . $t1[0] . '</p>';
                }
            }
            $Ep .= '					
                                                    </div>
                                            </div>
                                            <div class="col s12 m6 l4">
                                                    <h4>Формы обучения</h4>
                                                    <canvas id="chart-form">' . $vuz['forms'] . '</canvas>
                                            </div>
                                            <div class="col s12 m6 l4">
                                                    <h4>Уровни образования</h4>
                                                    <canvas id="chart-lvl">' . $vuz['mag'] . '</canvas>
                                            </div>
                                    </div>
                                    <p class="Ep-more"><a href="data/"><i class="material-icons">expand_more</i>Показать еще данные</a></p>
                            </section>';
        }

        /* MONITORINGS */
        $monit = '';
        if ($vuz['monit']) {
            $arr = $m = [];
            $db->query('SELECT `year`, `label`, IFNULL(`val`, "—") AS val FROM `vuz`.`monit` WHERE `vuz_id`=?', $id);
            while ($row = $db->get_row()) {
                $arr[$row['year']][$row['label']] = $row['val'];
            }

            $y  = date("y") - 1;
            $y1 = $y - 5;
            for ($i = $y1; $i <= $y; $i++) {
                if (isset($arr[$i]['msg'])) {
                    $monit .= '<p><b>Результат 20' . $i . ' года:</b> ';
                    switch ($arr[$i]['msg']) {
                        case 'i':
                            $monit .= 'данные для проведения мониторинга или их часть, не предоставлены или не соответствуют требованиям Межведомственной комиссии';
                            break;
                        case 'h':
                            $monit .= 'результаты мониторинга не показаны для вузов которые по результатам мониторинга 2015 года набрали менее 4-х баллов из 7';
                            break;
                        case 'r':
                            $monit .= 'решением Межведомственной комиссии ' . $vuz['abrev'] . ' отнесен к группе вузов нуждающихся в реорганизации';
                            break;
                    }
                    $monit .= '</p>';
                } else {
                    $m[$i] = is_array($arr[$i]);
                }
            }
            if (in_array(true, $m)) {
                $monit .= '
                                <table>
                                        <thead>
                                                <tr>
                                                        <td>Показатель</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>20' . $i . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                        </thead>
                                        <tbody>
                                                <tr>
                                                        <td>ESI <a class="esi-hint" href="/checkVuz#' . $id . '"></a> &#47; Показатель эффективности (до 2020 года)</td>';
                for ($i = $y; $i > $y1; $i--)
                    if ($m[$i])
                        switch ($arr[$i]['eff']) {
                            case 'A':
                                $monit .= '<td class="monit-A">' . $arr[$i]['eff'] . '</td>';
                                break;
                            case 'B':
                                $monit .= '<td class="monit-B">' . $arr[$i]['eff'] . '</td>';
                                break;
                            case 'C':
                                $monit .= '<td class="monit-C">' . $arr[$i]['eff'] . '</td>';
                                break;
                            default:
                                $monit .= '<td>' . $arr[$i]['eff'] . '</td>';
                                break;
                        }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Средний балл ЕГЭ по всем специальностям и формам обучения</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['ege'] . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Средний балл ЕГЭ зачисленных на бюджет</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['free'] . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Средний балл ЕГЭ зачисленных на коммерческой основе</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['pay'] . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Средний по всем специальностям минимальный балл ЕГЭ зачисленных на очное отделение</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['min'] . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Количество студентов</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . ($arr[$i]['o'] + $arr[$i]['oz'] + $arr[$i]['z']) . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Очное отделение</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['o'] . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Очно-заочное отделение</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['oz'] . '</td>';
                    }
                }
                $monit .= '
                                                </tr>
                                                <tr>
                                                        <td>Заочное отделение</td>';
                for ($i = $y; $i > $y1; $i--) {
                    if ($m[$i]) {
                        $monit .= '<td>' . $arr[$i]['z'] . '</td>';
                    }
                }
                $monit .= '
                                                        </tr>
                                                </tbody>
                                        </table>
                                        
                                        <p id="monit-link"><a href="#" class="more-link">Показать больше данных</a></p>';
            }
            unset($arr, $m, $y, $y1);

            $monit = '
                        <section id="monit">
                            <h2>Результаты мониторинга Минобрнауки для ' . $vuz['abrev'] . '</h2>' .
                $monit . '
                        </section>';
        }

        /* PRIEM */
        if ($vuz['priem_address']) {
            $priem = '
                    <section id="priem">
                            <h2>Приемная комиссия ' . $vuz['abrev'] . '</h2>
                            <div class="row contacts">
                                    <div class="col s12 m8 l9">
                                            <p class="myIcon leftIcon address">' . $vuz['priem_index'] . ', ';
            if ($vuz['subj_id'] !== 77 && $vuz['subj_id'] !== 78) {
                $priem .= $vuz['subject'] . ', ';
            }
            if ($id !== 410) {
                $priem .= $vuz['type'] . '&nbsp;' . $vuz['city'] . ', ';
            } // МФТИ

            $priem .= $vuz['priem_address'] . '
                                                    </p>';
            if ($vuz['priem_start']) {
                $priem .= '<p class="myIcon leftIcon season"></i>';
                if ($vuz['priem_start'] === '1') {
                    $priem .= 'Приемная комиссия работает круглогодично';
                } elseif ($vuz['priem_start']) {
                    $priem .= 'Приемная комиссия работает в период с ' . $vuz['priem_start'] . ' по ' . $vuz['priem_end'];
                }
            }
            $priem .= '	</p>
                        <p class="myIcon leftIcon site">';
            if ($vuz['priem_site']) {
                if (strlen($vuz['priem_site']) > 62) {
                    $tmp = substr($vuz['priem_site'], 0, 60) . '...';
                } else {
                    $tmp = $vuz['priem_site'];
                }

                $priem .= '<a href="http://' . $vuz['priem_site'] . '" target="_blank">' . $tmp . '</a>';
            } else {
                $priem .= 'Нет данных';
            }
            $priem .= '		</p>';
            if ($vuz['priem_email']) {
                $priem .= '<p class="myIcon leftIcon email">' . $vuz['priem_email'] . '</p>';
            }

            $priem .= '
                                            </div>
                                            <div class="col s12 m4 l3">';
            $tels  = explode("\n", $vuz['priem_phone']);
            foreach ($tels as $tel) {
                $tel   = explode("@", $tel);
                $priem .= '
                        <p class="myIcon leftIcon phone">
                                <a href="tel:+7' . $tel[0] . $tel[1] . '">
                                        +7 (' . $tel[0] . ') ' . substr($tel[1], 0, (6 - strlen($tel[0]))) . '-' .
                    substr($tel[1], -4, 2) . '-' . substr($tel[1], -2, 2) .
                    '</a>' . (($tel[2]) ? ('<br />доб. ' . $tel[2]) : ('')) .
                    '</p>';
            }
            $priem .= '</div>
                            </div>';
            if ($vuz['schedule']) {
                $schedule = '<div class="schedule">
                                    <p class="myIcon leftIcon">Режим работы:</p>';

                $sch  = explode("|", $vuz['schedule']);
                $c    = sizeof($sch);
                $days = ['Пн.', 'Вт.', 'Ср.', 'Чт.', 'Пт.', 'Сб.', 'Вс.'];
                for ($i = 0; $i < $c; $i++) {
                    $schedule .= '<p>';
                    $t        = substr($sch[$i], 0, 7);
                    for ($j = 0; $j < 7; $j++) {
                        if (substr($t, $j, 1) == '1') {
                            $schedule .= $days[$j] . ', ';
                        }
                    }
                    $schedule = substr($schedule, 0, -2);

                    $schedule .= ' c ' . substr($sch[$i], 7, 2) . ':' . substr($sch[$i], 9, 2) .
                        ' до ' . substr($sch[$i], 11, 2) . ':' . substr($sch[$i], 13, 2);
                    if ($adv = substr($sch[$i], 15)) {
                        $schedule .= ' доп. ' . $adv;
                    }
                    $schedule .= '</p>';
                }
                $schedule .= '</div>';
                $priem    .= $schedule;
            }
            $priem .= '
                            </section>';
        } else {
            $priem = '';
        }

        /* LAST OPINIONS */
        $opins = '';
        $t     = explode('|', $vuz['opins']);
        if ($t[1]) {
            $db->query(
                '
                SELECT a.*, CONCAT(`users`.`f_name`, " ", `users`.`surname`) AS user 
                FROM (
                                SELECT `u_id`, `anonym`, `time`, SUBSTRING(`text`, 1, 500) AS text, `score` 
                                FROM `vuz`.`opinions`
                                WHERE `opinions`.`vuz_id`=? AND `opinions`.`approved`="1" AND `score` IS NOT NULL 
                                ORDER BY `opinions`.`id` DESC LIMIT 2
                         ) a LEFT JOIN 
                        `auth`.`users` ON a.`u_id` = `users`.`id`',
                $id
            );
            if ($db->num_rows()) {
                require_once(base_path . "classes/date.php");
                $opins = '';
                while ($opin = $db->get_row()) {
                    $opins .= '
                                    <section class="opinion">
                                            <header class="truncate">
                                                    <div class="star-rate">' . $opin['score'] . '</div>' .
                        (($opin['anonym'] === "1") ? ("Анонимный отзыв") : ($opin['user'])) . '
                                    <time pubdate="pubdate" datetime="' . $opin['time'] . '">' . date::timeSt2normal(
                            $opin['time']
                        ) . '</time>
                                            </header>
                                            <div class="text">' . $opin['text'] . ((mb_strlen(
                                $opin['text'],
                                "UTF-8"
                            ) > 490) ? (' ...') : ('')) . '</div>
                                    </section>';
                }
                $opins = '
                                    <section id="last-opins" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                                            <meta itemprop="itemReviewed" content="' . $vuz['name'] . '" />
                                            <meta itemprop="worstRating" content="1" />
                                            <meta itemprop="bestRating" content="5" />
                                            <meta itemprop="ratingValue" content="' . (round($t[0] * 2) / 2) . '" />
                                            <meta itemprop="reviewCount" content="' . $t[1] . '" />
                                    
                                            <h2>Последние отзывы ' . $vuz['abrev'] . '</h2>' .
                    $opins .
                    '<p id="all-opins"><a href="opinions/" class="more-link">Все отзывы</a></p>
                                    </section>';
            }
        }

        /* GALLERY */
        $gallery = '';
        if ($vuz['gallery'] && !$vuz['delReason']) {
            $db->query(
                'SELECT `gallery`.`id`, `gallery`.`enw` 
                FROM `vuz`.`gallery` 
                WHERE `vuz_id` = ? 
                ORDER BY `gallery`.`enw` DESC',
                $id
            );

            if ($db->num_rows()) {
                $tmp = '';
                while ($row = $db->get_row()) {
                    $imgUrl = '/files/gallery/' . $id . '/' . $row["id"] . '.jpg';
                    if (!file_exists($host->getRoot() . $imgUrl)) {
                        $imgUrl = '/files/' . $id . '/gallery/' . $row["id"] . '.jpg';
                    }
                    $tmp .= '
                        <div class="cell">
                            <img itemprop="photo" class="materialboxed ' . (($row['enw'] === '1') ? (' enw-photo') : ('')) . '" data-caption="' . $vuz['name'] . '" src="' . $imgUrl . '" />
                        </div>';
                }
                $gallery = '
                    <section id="gallery">
                            <h2>Галерея ' . $vuz['abrev'] . '</h2>
                            <div id="gallery-holder">
                                    ' . $tmp . '
                            </div>
                    </section>';
            }
        }

        /* General info */
        $addr = $vuz['post'] . ', ';
        if ($vuz['subj_id'] !== 77 && $vuz['subj_id'] !== 78) {
            $addr .= $vuz['subject'] . ', ';
        }
        if ($id !== 410) { // МФТИ
            $addr .= $vuz['type'] . ' ' . $vuz['city'] . ', ';
        }
        $addr .= $vuz['address'] . '
        <meta itemprop="postalCode" value="' . $vuz['post'] . '" />
        <meta itemprop="addressRegion" value="' . $vuz['subject'] . '" />
        <meta itemprop="addressLocality" value="' . $vuz['city'] . '" />
        <meta itemprop="streetAddress" value="' . $vuz['address'] . '" />';

        if ($vuz['telNew'] === '1') {
            $html  = '+7 (%s) %s-%s-%s %s';
            $tel   = explode("@", $vuz['phone']);
            $phone = sprintf(
                $html,
                $tel[0],
                substr($tel[1], 0, (6 - strlen($tel[0]))),
                substr($tel[1], -4, 2),
                substr($tel[1], -2, 2),
                $tel[2]
            );
            $phone = '<a href="tel:+7' . $tel[0] . $tel[1] . '">' . $phone . '</a>';
            if ($tel[2]) {
                $phone .= ' доб.&nbsp;' . $tel[2];
            }
        } else {
            $phone = (($vuz['phone']) ? ($vuz['phone']) : ('Нет данных'));
        }

        /*  site */
        if ($vuz['site']) {
            if (strlen($vuz['site']) > 62) {
                $tmp = substr($vuz['site'], 0, 60) . '...';
            } else {
                $tmp = $vuz['site'];
            }
            $site = (($vuz['delReason']) ? ($tmp) : ('<a href="http://' . $vuz['site'] . '" target="_blank">' . $tmp . '</a>'));
        } else {
            $site = 'Нет данных';
        }

        /* Filials & Colleges*/
        $filials = '';
        if ($vuz['parent_id']) {
            $db->query(
                '
                    SELECT `vuzes`.`name`, `vuzes`.`city_id`, `vuzes`.`subj_id`
                    FROM `vuz`.`vuzes`
                    WHERE `vuzes`.`id`=?',
                $vuz['parent_id']
            );
            $row            = $db->get_row();
            $row['subj_id'] = (int)$row['subj_id'];
            //$t='/'.$row['subj_id'].(($row['subj_id'] !== 77 && $row['subj_id'] !== 78)?('/'.$row['city_id']):('')).'/v'.$vuz['parent_id'].'/';
            $filials = '
                    <p class="bold">Головной вуз</p>
                    <p itemprop="parentOrganization" itemscope itemtype="http://schema.org/EducationalOrganization">
                            <span itemscope itemprop="identifier" itemtype="https://schema.org/PropertyValue">
                                    <meta itemprop="propertyID" content="Company Number" />
                                    <meta itemprop="value" content="' . $vuz['parent_id'] . '" />
                            </span>
                            <a itemprop="url" href="/' . $row['subj_id'] . (($row['subj_id'] !== 77 && $row['subj_id'] !== 78) ? ('/' . $row['city_id']) : ('')) . '/v' . $vuz['parent_id'] . '/" itemprop="url">' . $row['name'] . '</a>
                    </p>';
        } else {
            $db->query(
                '
                    SELECT a.`id`, `cities`.`name`, a.`city_id`, a.`subj_id`
                    FROM (
                                    SELECT `vuzes`.`id`, `vuzes`.`city_id`, `vuzes`.`subj_id`
                                    FROM `vuz`.`vuzes` WHERE `vuzes`.`parent_id`=?
                            ) a LEFT JOIN
                            `general`.`cities` ON a.`city_id`=`cities`.`id`
                    ORDER BY `cities`.`name`',
                $id
            );
            if ($cnt = $db->num_rows()) {
                $filials = '';
                while ($filial = $db->get_row()) {
                    $filial['subj_id'] = (int)$filial['subj_id'];
                    $filials           .= '
                        <li itemprop="subOrganization" itemscope itemtype="http://schema.org/EducationalOrganization">
                                <span itemscope itemprop="identifier" itemtype="https://schema.org/PropertyValue">
                                        <meta itemprop="propertyID" content="Company Number" />
                                        <meta itemprop="value" content="' . $filial['id'] . '" />
                                </span>
                                <a itemprop="url" href="/' . $filial['subj_id'] . (($filial['subj_id'] !== 77 && $filial['subj_id'] !== 78) ? ('/' . $filial['city_id']) : ('')) . '/v' . $filial['id'] . '/">' . $filial['name'] . '</a>
                        </li>';
                }
                if ($cnt > 3) {
                    /* Sklonenie */
                    $filials .= '<li><a id="all-filials" class="more-link" href="#">Еще ' . ($cnt - 3) . ' ' . rodpad(
                            $cnt - 3,
                            ['филиалов', 'филиала', 'филиал', 'филиалов']
                        ) . '</a></li>';
                }

                $filials = '
                    <p class="bold">Филиалы  ' . $vuz['abrev'] . '</p>
                    <ul id="filials">' . $filials . '</ul>';
            }
        }

        $colleges = '';
        $db->query(
            '
            SELECT `colleges`.`id`, `colleges`.`name`, `colleges`.`city_id`, `colleges`.`subj_id`
            FROM `college`.`colleges`
            WHERE `colleges`.`vuzId`=?',
            $id
        );
        if ($cnt = $db->num_rows()) {
            $colleges = '';
            while ($col = $db->get_row()) {
                $colleges .= '<li><a itemprop="subOrganization" href="https://college' . DOMAIN . '/' . $col['subj_id'] . (($col['subj_id'] != 77 && $col['subj_id'] != 78) ? ('/' . $col['city_id']) : ('')) . '/c' . $col['id'] . '/">' . $col['name'] . '</a></li>';
            }
            if ($cnt > 3) {
                $colleges .= '<li><a id="all-colleges" class="more-link" href="#">Еще ' . ($cnt - 3) . ' ' . rodpad(
                        $cnt - 3,
                        ['колледжей', 'колледжа', 'колледж', 'колледжей']
                    ) . '</a></li>';
            }

            $colleges = '
                <p class="bold">Колледжи ' . $vuz['abrev'] . '</p>
                <ul id="colleges">' . $colleges . '</ul>';
        }

        /* LIC AND ACR */
        $lic = $acr = '';
        if ($vuz['parent_id']) {
            $db->query(
                '
                    SELECT `vuzes`.`lic_num`, `vuzes`.`lic_start`, `vuzes`.`acr_num`, `vuzes`.`acr_start`
                    FROM `vuzes` WHERE `id` = ?',
                $vuz['parent_id']
            );
            if ($db->num_rows()) {
                $t                = $db->get_row();
                $vuz['lic_num']   = $t['lic_num'];
                $vuz['lic_start'] = $t['lic_start'];
                $vuz['acr_num']   = $t['acr_num'];
                $vuz['acr_start'] = $t['acr_start'];
            }
        }

        $lic = ($vuz['lic_num'] ? '№ ' . $vuz['lic_num'] . ' действует Бессрочно с ' . $vuz['lic_start'] : 'Нет данных');
        $acr = ($vuz['acr_num'] ? '№ ' . $vuz['acr_num'] . ' действует с ' . $vuz['acr_start'] : 'Нет данных');

        if ($vuz['acr_num']) {
            $t  = 'IFNULL(z.`code`, 0)';
            $t1 = '(SELECT `acr_okso`.`code` FROM `acr_okso` WHERE `acr_okso`.`vuz_id`=' . ($vuz['parent_id'] ? $vuz['parent_id'] : $id) . ') z ON z.code=FLOOR(`okso`.`code`/100) LEFT JOIN';
        } else {
            $t  = '0';
            $t1 = '';
        }
        $lic_okso = '';
        $db->query(
            '
                SELECT  
                       `okso`.`code`, `okso`.`name`, MOD(FLOOR(`okso`.`code`/100), 10) as lvl, 
                       ' . $t . ' AS accr, IF(s.okso_id, 1, 0) AS realize
                FROM 
                        (SELECT `okso_id` FROM `lic_okso` WHERE `vuz_id`=?) a LEFT JOIN 
                        `okso` ON a.`okso_id`=`okso`.`id` LEFT JOIN 
                        ' . $t1 . '
        (SELECT DISTINCT `specs`.`okso_id` FROM `specs` WHERE `specs`.`vuz_id`=?) s ON s.`okso_id`=a.`okso_id`
                ORDER BY realize DESC, `okso`.`code`',
            $id,
            $id
        );
        if ($db->num_rows()) {
            $lic_okso = '
        <section id="lic_okso" name="lic_okso">
            <h2>Лицензированные специальности</h2>
            <table class="striped">
                <thead>
                    <tr>
                        <th class="hide-on-small-only">Код</th>
                        <th>Специальность</th>
                        <th>Уровень</th>
                    </tr>
                </thead>
                <tbody>';
            while ($row = $db->get_row()) {
                switch ($row['lvl']) {
                    case '3':
                        $lvl = 'Бакалавриат';
                        break;
                    case '4':
                        $lvl = 'Магистратура';
                        break;
                    case '5':
                        $lvl = 'Специалитет';
                        break;
                    case '6':
                        $lvl = 'Аспирантура';
                        break;
                }
                $lic_okso .= '
                    <tr' . ($vuz['spec'] && !$row['realize'] ? ' class="not-realize"' : '') . '>
                            <td class="hide-on-small-only">' . substr_replace(
                        substr_replace($row['code'], '.', 2, 0),
                        '.',
                        5,
                        0
                    ) . '</td>
                            <td>' . $row['name'];
                if ((!$vuz['spec'] || $row['realize']) && (!$vuz['acr_num'] || !$row['accr'])) {
                    $lic_okso .= '<span class="no-accr">неаккредитовано <a href="/faq-f/8#no-accr"></a></span>';
                }
                $lic_okso .= '</td>
                                        <td>' . $lvl . '</td>
                                </tr>';
            }
            $lic_okso .= '
                                </tbody>
                        </table>
                        <p><a href="#" class="show-all more-link">Показать еще</a></p>
                        <p><a href="#" class="show-no-realize more-link">Показать нереализуемые программы</a></p>
                </section>';
        }

        /* VUZ OLD NAMES */
        if ($vuz['old_names']) {
            $t         = explode("|", $vuz['old_names']);
            $t         = '<li>' . implode("</li><li>", $t) . '</li>';
            $old_names = '
                <div id="oldnames">
                        <p class="bold">Предыдущие названия ' . $vuz['abrev'] . '</p>
                        <ul>' . $t . '</ul>	
                </div>';
        } else {
            $old_names = '';
        }

        $arts = '';
        /* VUZ ARTICLES */
        //     $arts='';
        //     if($vuz['articles']) {
        //             $db->query('
        //                     SELECT `articles`.`id`, `articles`.`name`, `articles`.`about` 
        //                     FROM (
        //                                     SELECT `vuz2article`.`art_id` FROM `vuz`.`vuz2article` WHERE `vuz2article`.`vuz_id`=?
        //                             ) a LEFT JOIN			
        //                             `knowledge`.`articles` ON a.`art_id`=`articles`.`id`
        //                     ORDER BY `articles`.`id` DESC', $id);
        //             while($art=$db->get_row()) {
        //                     $arts.='<div><p><a href="/reviews/'.$art['id'].'">'.$art['name'].'</a></p><p>'.$art['about'].'</p></div>';
        //             }

        // $arts='
        //                     <section id="unit-articles">
        //                             <h2>Обзоры вуза</h2>'.
        //                             $arts.
        //                     '</section>';
        //     }

        /* ABOUT TEXT */
        if ($vuz['about']) {
            $about = '
                <section id="about" itemprop="description">
                        <h2>О ' . $vuz['abrev'] . '</h2>
                        ' . $vuz['about'] . '
                </section>';
        } else {
            $about = '';
        }

        /* ADS */
        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        if (!$vuz['packet']) {
            ads::get($ads, $vuz['subj_id']);
        }

        /* NAV */
        $nav = vuz::get_nav($vuz, 'main');

        /* SEO */
        $title = $vuz['abrev'] . '. ' . $vuz['name'];
        $desc  = $vuz['name'] . ' (' . $vuz['abrev'] . '). Адрес, официальный сайт, приемная комиссия, дни открытых дверей, лицензия, аккредитация, фото';
        $kw    = $vuz['abrev'] . ', ' . $vuz['name'] . ', общая информация, приемная комиссия, филиалы, колледжи при вузе, фотографии вуза';

        $home = HOME . 'vuz.edunetwork.ru/';
        $tpl->start($home . 'tpl/vuz.html');
        if ($vuz['subj_id'] == 77 || $vuz['subj_id'] == 78) {
            $canonicalUrl = '//vuz' . DOMAIN . '/' . $vuz['subj_id'] . '/v' . $id;
        } else {
            $canonicalUrl = '//vuz' . DOMAIN . '/' . $vuz['subj_id'] . '/' . $vuz['city_id'] . '/v' . $id;
        }

        $quiz = file_get_contents('tpl/quiz.html');
        $quiz = str_replace('[vuz_id]', $vuz['id'], $quiz);

        $tpl->replace(
            [
                '[head]'            => get_head(
                    $title,
                    $desc,
                    $kw,
                    '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>',
                    $canonicalUrl
                ),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),
                '[navbar]'          => $head['nav'],
                '[header]'          => $head['header'],
                '[nav]'             => $nav,
                '[Ep]'              => $Ep,
                '[dods]'            => $dods,
                '[monit]'           => $monit,
                '[priem]'           => $priem,
                '[okso]'            => $lic_okso,
                '[gallery]'         => $gallery,
                '[opins]'           => $opins,

                '[fullName]' => $vuz['fullName'],
                '[tel]'      => $phone,
                '[addr]'     => $addr,
                '[site]'     => $site,
                '[email]'    => $vuz['email'],

                '[filials]'   => $filials,
                '[colleges]'  => $colleges,
                '[lic]'       => $lic,
                '[acr]'       => $acr,
                '[old_names]' => $old_names,

                '[articles]' => $arts,
                '[about]'    => $about,

                '[ylead]' => vuz::leads_form($vuz['id']),

                '[ads1]' => $ads[1],
                '[ads2]' => $ads[2],
                '[ads3]' => $ads[3],
                '[ads5]' => $ads[5],
                '[ads6]' => $ads[6],
                '[ads7]' => $ads[7],
                '[quiz]' => $quiz,

                '[footer]' => file_get_contents($home . 'tpl/footer.html'),
            ]
        );
        $tpl->out();
    }

    public static function faculties(int $userId)
    {
        require_once 'vuz_faculties.php';
        vuz_faculties::show($userId);
    }

    public static function faculty(int $userId = 0, int $vusId = 0, int $fucultyId = 0)
    {
        require_once 'vuz_faculty.php';
        vuz_faculty::show($userId, $vusId, $fucultyId);
    }

    public static function specs(int $userId)
    {
        require_once 'vuz_specs.php';
        vuz_specs::show($userId);
    }

    static function specokso($u_id, $debug = false)
    {
        $id        =& $_GET['vuz'];
        $subvuz_id =& $_GET['sv'];
        $okso_id   =& $_GET['spec'];
        if (!preg_match('/^\d+$/', $id) && !$debug) {
            myErr::err404();
        }

        if (isset($_GET['lvl']) || isset($_GET['form'])) {
            header(
                'Location: https://' . $_SERVER['HTTP_HOST'] . preg_replace(
                    '/\/\?.+/',
                    '',
                    $_SERVER['REQUEST_URI']
                ) . '/',
                true,
                301
            );
            die;
        }
        require_once 'vuz_specokso.php';
        vuz_specokso::render($u_id, $id, $subvuz_id, $okso_id);
    }

    public static function spec($u_id, $debug = false)
    {
        $id        =& $_GET['vuz'];
        $subvuz_id =& $_GET['sv'];
        $spec_id   =& $_GET['s'];
        if (!preg_match('/^\d+$/', $id) && !$debug) {
            myErr::err404();
        }

        if (isset($_GET['lvl']) || isset($_GET['form'])) {
            header(
                'Location: https://' . $_SERVER['HTTP_HOST'] . preg_replace(
                    '/\/\?.+/',
                    '',
                    $_SERVER['REQUEST_URI']
                ) . '/',
                true,
                301
            );
            die;
        }
        require_once 'vuz_spec.php';
        vuz_spec::render($u_id, $id, $subvuz_id, $spec_id);
    }

    static function opinions($u_id)
    {
        global $db, $tpl;

        $id =& $_GET['vuz'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::err404();
        }

        $db->query(
            '
                    SELECT 
                            a.*, 
                            `dir2specs`.`id` AS dir_id, `dir2specs`.`name` AS dir_name,
                            `subjects`.`name` as subject, `subjects`.`rp` as subj_rp,
                            `cities`.`name` as city, `cities`.`rp` as city_rp, `cities`.`type`, `metros`.`name` AS metro,
                            IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
                            (SELECT 1 FROM `vuz`.`specs` WHERE a.`id`=`specs`.`vuz_id` LIMIT 1) AS spec
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
                                            `vuzes`.`noAbitur`, `vuzes`.`esi`
                                    FROM `vuz`.`vuzes` WHERE `vuzes`.`id`=?
                            ) a LEFT JOIN
                            `general`.`subjects` ON a.`subj_id`=`subjects`.`id` LEFT JOIN
                            `general`.`cities` ON a.`city_id`=`cities`.`id` LEFT JOIN
                            `general`.`metros` ON a.`metro_id`=`metros`.`id` LEFT JOIN
                            `vuz`.`vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id` LEFT JOIN
                            `vuz`.`user2vuz` ON a.`id`=`user2vuz`.`vuz_id` LEFT JOIN
                            `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`',
            $id
        );
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $vuz  = $db->get_row();
        $head = vuz::get_head($vuz, true);
        $nav  = vuz::get_nav($vuz, 'opinions');

        if ($u_id) {
            $db->query('SELECT `vuz_id` FROM `vuz`.`opinions` WHERE `u_id`=?', $u_id);
            if ($db->num_rows() < 3) {
                while ($op = $db->get_row()) {
                    if ($op['vuz_id'] === $id) {
                        $state = "opined";
                        break;
                    }
                }
            } else {
                $state = "flood";
            }

            if (!$state) {
                $state = 'ok';
            }
        } else {
            $state = 'not-auth';
        }

        /* ADS */
        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        if (!$vuz['packet']) {
            ads::get($ads, $vuz['subj_id']);
        }

        $db->query(
            '
                    SELECT a.*, CONCAT(`users`.`f_name`, " ", `users`.`surname`) AS username 
                    FROM
                            (
                                    SELECT 
                                            `opinions`.`id`, `opinions`.`anonym`, `opinions`.`time`, `opinions`.`text`, 
                                            `opinions`.`score`,`opinions`.`u_id`,
                                            `opinions`.`answer_time`, `opinions`.`answer_text`, `opinions`.`answer_approved`
                                    FROM `vuz`.`opinions`
                                    WHERE `opinions`.`vuz_id`=? AND `opinions`.`approved`="1"
                            ) a LEFT JOIN
                            `auth`.`users` ON a.`u_id` = `users`.`id`
                    ORDER BY a.`id` DESC',
            $id
        );
        if ($cnt = $db->num_rows()) {
            $sum  = $total = $good = $neut = $bad = $nulled = 0;
            $msgs = '';
            while ($comm = $db->get_row()) {
                $comm['score'] = (int)$comm['score'];
                $cls           = '';
                if ($comm['score']) {
                    $sum += $comm['score'];
                    $total++;
                    if ($comm['score'] > 3) {
                        $good++;
                        $cls = 'good';
                    } elseif ($comm['score'] === 3) {
                        $neut++;
                        $cls = 'neut';
                    } else {
                        $bad++;
                        $cls = 'bad';
                    }
                    $rate = '
                                            <div itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                                                    <meta itemprop="worstRating" content="1" />
                                                    <meta itemprop="bestRating" content="5" />
                                                    <meta itemprop="ratingValue" content="' . $comm['score'] . '" />
                                                    <div class="star-rate"></div>
                                            </div>';
                } else {
                    $nulled++;
                    $cls  = 'nulled';
                    $rate = '<p>Оценка аннулирована. Причина: накрутка отзывов</p>';
                }

                $msgs .=
                    '<section class="opinion ' . $cls . '" id="' . $comm['id'] . '" itemprop="review" itemscope itemtype="https://schema.org/Review">
                                    <header class="truncate">' . $rate;

                $msgs .= '<span itemprop="author">' . (($comm['anonym'] === "1") ? ('Анонимный отзыв') : ($comm['username'])) . '</span>';
                $msgs .= '<meta itemprop="datePublished" content="' . $comm['time'] . '" />	<time pubdate="pubdate" datetime="' . $comm['time'] . '">' . date::timeSt2normal(
                        $comm['time'],
                        1
                    ) . '</time>
                                    </header>
                                    <div class="text" itemprop="reviewBody">' . $comm['text'] . '</div>';
                if ($u_id) {
                    if ($comm['u_id'] == $u_id && $comm['score'] && !$comm['answer_time']) {
                        $msgs .= '
                                            <footer>
                                                    <div class="opin-menu">
                                                            <a href="#" class="delete"><i class="material-icons small">delete</i>Удалить</a>
                                                    </div>
                                            </footer>';
                    }
                    if ($vuz['u_id'] == $u_id && !$comm['answer_time'] && $comm['score'] && strtotime(
                            $comm['time']
                        ) > (time() - 14 * 86400)) {
                        $msgs .= '
                                            <footer>
                                                    <div class="opin-menu">
                                                            <a href="#" class="reply">
                                                                    <i class="material-icons small">reply</i>Ответить на этот отзыв от имени вуза
                                                            </a>
                                                    </div>
                                            </footer>';
                    }
                }
                if ($comm['answer_approved']) {
                    $msgs .= '
                    <section class="answer" id="' . $comm['id'] . '">
                            <header class="truncate">
                                    Официальный ответ <time pubdate="pubdate" datetime="' . $comm['time'] . '">' . date::timeSt2normal(
                            $comm['answer_time'],
                            1
                        ) . '</time>
                            </header>
                            <div class="text">' . $comm['answer_text'] . '</div>
                    </section>';
                }
                $msgs .= '</section>';
            }
        } else {
            $msgs = '<p id="no-opinions">Отзывов пока нет, ваш может стать первым</p>';
        }

        if ($total) {
            $ar = '
            <div id="sumOpins" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                    <meta itemprop="itemReviewed" content="' . $vuz['name'] . '" />
                                    <meta itemprop="worstRating" content="1" />
                                    <meta itemprop="bestRating" content="5" />
                                    <meta itemprop="ratingValue" content="' . (round($sum / $total * 2) / 2) . '" />
                                    <div id="sumScore"><span class="hide-on-small-only">Совокупная оценка </span><div class="star-rate"></div> исходя из <b itemprop="reviewCount">' . $cnt . '</b> отзывов</div>
                                    <div class="row">
                                            <div class="col s3" id="goodOpins"><a href="#">Положительных</a>' . $good . '</div>
                                            <div class="col s3" id="neutOpins"><a href="#">Нейтральных</a>' . $neut . '</div>
                                            <div class="col s3" id="badOpins"><a href="#">Отрицательных</a>' . $bad . '</div>
                                            <div class="col s3" id="nullOpins"><a href="#">Аннулированных</a>' . $nulled . '</div>
                                    </div>
                            </div>';
        } else {
            $ar = '';
        }

        $title = 'Отзывы о ' . $vuz['abrev'] . '. ' . $vuz['name'];
        $desc  = $vuz['name'] . ' (' . $vuz['abrev'] . '). Отзывы абитуриентов, студентов, сотрудников вуза';
        $kw    = $vuz['abrev'] . ', ' . $vuz['name'] . ', отзывы, оставить отзыв';

        $tpl->start("tpl/opinions.html");
        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[state]' => $state,

            '[navbar]' => $head['nav'],
            '[header]' => $head['header'],
            '[ar]'     => $ar,

            '[nav]' => $nav,

            '[id]'   => $id,
            '[msgs]' => $msgs,

            '[ylead]' => vuz::leads_form($vuz['id']),

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

    static function replyAdd()
    {
        global $db;
        $v_id   =& $_POST['unit_id'];
        $p_id   =& $_POST['p_id'];
        $score  =& $_POST['score'];
        $finger =& $_POST['fp'];

        if (!preg_match('/^\d+$/', $v_id)) {
            myErr::err500(true);
        }
        if (!preg_match('/^\d+$/', $p_id)) {
            myErr::err500(true);
        }

        if ($score && !preg_match('/^[\d\-]+$/', $score)) {
            myErr::err500(true);
        }

        if (!$finger || !preg_match('/^[\-0-9]+$/', $finger)) {
            $finger = null;
        }

        $db->query('SELECT 1 FROM `vuz`.`vuzes` WHERE `id`=?', $v_id);
        if (!$db->num_rows()) {
            myErr::err500(true);
        }

        if ($score && $score < 1 || $score > 5) {
            myErr::err500(true);
        }

        $anonym = (($_POST['anonym'] === '1') ? ('1') : ('0'));

        $text = strip_tags(trim($_POST['text']));
        $text = preg_replace('/\n{2,}/', '', $text);
        $text = '<p>' . str_replace("\n", '</p><p>', $text) . '</p>';

        if ($u_id = user::check_session()) {
            $db->query('SELECT 1 FROM `vuz`.`vuzes` WHERE `id`=?', $v_id);
            if (!$db->num_rows()) {
                myErr::err500(true);
            }

            if ($p_id) { // ans
                $db->query(
                    'SELECT IFNULL(`score`, 0) AS score FROM `vuz`.`opinions` WHERE `id`=? AND `vuz_id`=?',
                    $p_id,
                    $v_id
                );
                if (!$db->num_rows()) {
                    myErr::err500(true);
                }

                $db->query('SELECT 1 FROM `vuz`.`user2vuz` WHERE `u_id`=? AND `vuz_id`=?', $u_id, $v_id);
                if (!$db->num_rows()) {
                    myErr::err500(true);
                }

                $db->query('UPDATE `vuz`.`opinions` SET `answer_time`=NOW(), answer_text=? WHERE `id`=?', $text, $p_id);
            } else { // opin
                $db->query('SELECT `vuz_id` FROM `vuz`.`opinions` WHERE `u_id`=? AND `score` IS NOT NULL', $u_id);
                if ($db->num_rows() < 3) {
                    while ($op = $db->get_row()) {
                        if ($op['vuz_id'] == $v_id) {
                            myErr::err500(true);
                            break;
                        }
                    }
                } else {
                    myErr::err500(true);
                }


                $db->query(
                    '
                            INSERT INTO `vuz`.`opinions`(
                                    `u_id`, `vuz_id`, `anonym`, `text`, `score`, `ip`, `headers`, `finger`
                            ) VALUES(
                                    ?, ?, ?, ?, ?, ?, ?, ?)',
                    $u_id,
                    $v_id,
                    $anonym,
                    $text,
                    $score,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT'],
                    $finger
                );
            }
            die('success');
        }
        die('err');
    }

    static function replyDel()
    {
        global $db;
        $id =& $_POST['id']; // Comment ID

        if (!preg_match('/^\d+$/', $id)) {
            myErr::err500(true);
        }

        if ($u_id = user::check_session()) {
            $db->query(
                'SELECT `time` FROM `vuz`.`opinions` WHERE `id`=? AND `answer_time` IS NULL AND `u_id`=?',
                $id,
                $u_id
            );
            if (!$db->num_rows()) {
                myErr::err500(true);
            }
            $t = $db->get_row();
            if (date::mysql2time($t['time']) < (time() - 7 * 86400)) {
                myErr::err500(true);
            }
            $db->query('UPDATE `vuz`.`opinions` SET `approved`="0" WHERE `id`=?', $id);
            die('success');
        }
        die('Вы неавторизованы, обновите страницу');
    }

    static function data($u_id)
    {
        global $db, $tpl;

        $id =& $_GET['vuz'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::err404();
        }
        $id = (int)$id;

        if ($u_id) {
            $db->query(
                'SELECT `dirs`, `forms`, `mag`, `free`, `studs`, `shtat`, `uchen`, `age`, `inoPrep`, `egeFree`, `egePay`, `cost`, `place`, `pc`, `hos` FROM `e_plus` WHERE `vuz_id`=?',
                $id
            );
        } else {
            $db->query('SELECT `dirs`, `forms`, `mag`, `free`, `studs`, `shtat` FROM `e_plus` WHERE `vuz_id`=?', $id);
        }
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $E = $db->get_row();

        $db->query(
            '
			SELECT 
				a.*, 
				`dir2specs`.`id` AS dir_id, `dir2specs`.`name` AS dir_name,
				`subjects`.`name` as subject, `subjects`.`rp` as subj_rp,
				`cities`.`name` as city, `cities`.`rp` as city_rp, `cities`.`type`, `metros`.`name` AS metro,
				IF(`user2vuz`.`u_id`>1000, `user2vuz`.`u_id`, NULL) AS u_id,
				(SELECT 1 FROM `vuz`.`specs` WHERE a.`id`=`specs`.`vuz_id` LIMIT 1) AS spec,
				(SELECT COUNT(*) FROM `vuz`.`opinions` WHERE `opinions`.`vuz_id`=a.`id` AND `approved`="1") AS opins
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
						`vuzes`.`noAbitur`, `vuzes`.`esi`
					FROM `vuz`.`vuzes` WHERE `vuzes`.`id`=?
				) a LEFT JOIN
				`general`.`subjects` ON a.`subj_id`=`subjects`.`id` LEFT JOIN
				`general`.`cities` ON a.`city_id`=`cities`.`id` LEFT JOIN
				`general`.`metros` ON a.`metro_id`=`metros`.`id` LEFT JOIN
				`vuz`.`vuz2direct` ON a.`id`=`vuz2direct`.`vuz_id` LEFT JOIN
				`vuz`.`user2vuz` ON a.`id`=`user2vuz`.`vuz_id` LEFT JOIN
				`vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`',
            $id
        );
        if (!$db->num_rows()) {
            myErr::err404();
        }
        $vuz  = $db->get_row();
        $head = vuz::get_head($vuz, true);
        $nav  = vuz::get_nav($vuz, 'data');

        if ($E['dirs']) {
            $t         = explode("\n", $E['dirs']);
            $cnt       = sizeof($t);
            $E['dirs'] = '';
            for ($i = 0; $i < $cnt; $i++) {
                $t1        = explode('|', $t[$i]);
                $E['dirs'] .= '<p data-val="' . $t1[1] . '" style="width:1%">' . $t1[0] . '</p>';
            }
        }

        if ($u_id) {
            if ($E['egeFree']) {
                $E['egeFree'] = explode("|", $E['egeFree']);
                $E['egeFree'] = '
				<p class="value">' . $E['egeFree'][0] . '</p>
				<p>средний балл ЕГЭ<br />на бюджет очно</p>
				<p class="hint"><b>' . $E['egeFree'][1] . '</b> место из <b>' . $E['egeFree'][2] . '</b> в регионе</p>';
            } else {
                $E['egeFree'] = '<p class="value">Нет</p><p>бюджетных мест<br />на очное отделение</p>';
            }

            $a = false;
            if ($E['egePay']) {
                $a           = true;
                $E['egePay'] = explode("|", $E['egePay']);
                $E['egePay'] = '
				<p class="value">' . $E['egePay'][0] . '</p>
				<p>средний балл ЕГЭ<br />на платное очно</p>
				<p class="hint"><b>' . $E['egePay'][1] . '</b> место из <b>' . $E['egePay'][2] . '</b> в регионе</p>';
            } else {
                $E['egePay'] = '<p class="value">Нет</p><p>коммерческих мест<br />на очное отделение</p>';
                $E['cost']   = '<p class="value">Нет</p><p>коммерческих мест<br />на очное отделение</p>';
            }

            if ($E['cost']) {
                if (strpos($E['cost'], '|')) {
                    $E['cost'] = explode("|", $E['cost']);
                    $E['cost'] = '
						<p class="value">' . number_format($E['cost'][0], 0, ',', '&thinsp;') . ' ₽</p>
						<p>средняя стоимость обучения<br />в год на очной форме</p>
						<p class="hint"><b>' . $E['cost'][1] . '</b> место из <b>' . $E['cost'][2] . '</b> в регионе</p>';
                } else {
                    $E['cost'] = '
						<p class="value">' . number_format($E['cost'], 0, ',', '&thinsp;') . ' ₽</p>
						<p>средняя стоимость обучения<br />в год на очной форме</p>';
                }
            } elseif ($a) {
                $E['cost'] = '<p class="value">Нет</p><p>данных о стоимости<br />коммерческого обучения</p>';
            }

            if ($E['place']) {
                $E['place'] = explode("|", $E['place']);
                $E['place'] = '
				<p class="value">' . $E['place'][0] . ' м<sup>2</sup></p>
				<p>площади<br />на студента</p>
				<p class="hint"><b>' . $E['place'][1] . '</b> место из <b>' . $E['place'][2] . '</b> в регионе</p>';
            } else {
                if (!$E['o'] && !$E['oz']) {
                    $E['place'] = '<p class="value">—</p><p>нет очного<br />и очно-заочного отделения</p>';
                } else {
                    $E['place'] = '<p class="value">Нет</p><p>данных по площади<br />распологаемой вузом</p>';
                }
            }

            if ($E['pc']) {
                $E['pc'] = explode("|", $E['pc']);
                $E['pc'] = '
				<p class="value">' . $E['pc'][0] . '</p>
				<p>компьютера<br />на студента</p>
				<p class="hint"><b>' . $E['pc'][1] . '</b> место из <b>' . $E['pc'][2] . '</b> в регионе</p>';
            } else {
                $E['egeFree'] = '<p class="value">Нет</p><p>бюджетных мест<br />на очное отделение</p>';
            }

            if ($E['hos']) {
                $E['hos'] = explode("|", $E['hos']);
                $E['hos'] = '
				<p class="value">' . $E['hos'][0] . '%</p>
				<p>нуждающихся<br />обеспечены общежитием</p>
				<p class="hint"><b>' . $E['hos'][1] . '</b> место из <b>' . $E['hos'][2] . '</b> в регионе</p>';
            } else {
                $E['hos'] = '<p class="value">Нет</p><p>общежития<br />для студентов</p>';
            }
        } else {
            $E['egeFree'] = $E['egePay'] = $E['cost'] = $E['place'] = $E['pc'] = $E['hos'] = $E['uchen'] = $E['age'] = $E['inoPrep'] = '';
        }

        $ads = [1 => '', 2 => '', 3 => '', 5 => '', 6 => '', 7 => ''];
        if (!$vuz['packet']) {
            ads::get($ads, $vuz['subj_id']);
        }

        $h2    = 'Edunetwork&plus; ' . $vuz['abrev'] . ' в цифрах';
        $title = 'EduNetwork&plus; статистика ' . $vuz['abrev'];
        $desc  = 'EduNetwork&plus; ' . $vuz['abrev'] . ', статистика ' . $vuz['abrev'] . ' из 15 показателей в удобном формате на одной странице. Место ' . $vuz['abrev'] . ' в регионе по ЕГЭ и стоимости обучения, материально-техническое оснащение';
        $kw    = 'EduNetwork&plus; ' . $vuz['abrev'] . ' ' . $vuz['name'] . ' мониторинг специальности формы обучения студенты преподаватели место стоимость ЕГЭ ';

        $tpl->start("tpl/unit-data.html");
        $tpl->replace([
            '[head]'            => get_head(
                $title,
                $desc,
                $kw,
                '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>'
            ),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[navbar]' => $head['nav'],
            '[header]' => $head['header'],

            '[nav]' => $nav,
            '[h2]'  => $h2,

            '[dirs]'  => $E['dirs'],
            '[forms]' => $E['forms'],
            '[mag]'   => $E['mag'],

            '[free]'  => $E['free'],
            '[studs]' => $E['studs'],
            '[shtat]' => $E['shtat'],

            '[egeFree]' => $E['egeFree'],
            '[egePay]'  => $E['egePay'],
            '[cost]'    => $E['cost'],

            '[place]' => $E['place'],
            '[pc]'    => $E['pc'],
            '[hos]'   => $E['hos'],

            '[uchen]' => $E['uchen'],
            '[age]'   => $E['age'],
            '[prep]'  => $E['inoPrep'],

            '[ylead]' => vuz::leads_form($vuz['id']),

            '[ads1]'   => $ads[1],
            '[ads2]'   => $ads[2],
            '[ads3]'   => $ads[3],
            '[ads5]'   => $ads[5],
            '[ads6]'   => $ads[6],
            '[ads7]'   => $ads[7],
            '[quiz]'   => file_get_contents('tpl/quiz.html'),
            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
    }

}

?>